<?php

namespace Chumptable\Datatable\Engines;

use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryEngine extends BaseEngine
{
    /**
     * @var QueryBuilder|EloquentBuilder
     */
    public $builder;

    /**
     * @var QueryBuilder|EloquentBuilder
     */
    public $originalBuilder;

    /**
     * @var Collection
     */
    private $resultCollection;

    /**
     * @var Collection|null
     */
    private $collection = null;

    /**
     * @var array
     */
    private $options = [
        'searchOperator'     => 'LIKE',
        'searchWithAlias'    => false,
        'orderOrder'         => null,
        'counter'            => 0,
        'noGroupByOnCount'   => false,
        'distinctCountGroup' => false,
        'emptyAtEnd'         => false,
        'returnQuery'        => false,
        'queryKeepsLimits'   => false,
    ];

    public function __construct($builder)
    {
        parent::__construct();

        if ($builder instanceof Relation) {
            $this->builder         = $builder->getBaseQuery();
            $this->originalBuilder = clone $builder->getBaseQuery();
        } else {
            $this->builder         = $builder;
            $this->originalBuilder = clone $builder;
        }
    }

    public function count(): int
    {
        return $this->options['counter'];
    }

    public function totalCount(): int
    {
        $countBuilder = clone $this->originalBuilder;

        if ($this->options['distinctCountGroup'] && $this->hasSingleGroup($countBuilder)) {
            $countBuilder->groups = null;
        }

        if ($this->options['searchWithAlias']) {
            return $countBuilder->get()->count();
        }

        if ($this->options['noGroupByOnCount']) {
            $countBuilder->groups = null;
        }

        return $countBuilder->count();
    }

    public function getArray(): array
    {
        return $this->getCollection($this->builder)->toArray();
    }

    public function reset(): self
    {
        $this->builder = clone $this->originalBuilder;
        return $this;
    }

    // ---------------- Options ----------------

    public function setSearchOperator(string $value = "LIKE"): self
    {
        $this->options['searchOperator'] = $value;
        return $this;
    }

    public function setSearchWithAlias(bool $value = true): self
    {
        $this->options['searchWithAlias'] = $value;
        return $this;
    }

    public function setEmptyAtEnd(bool $value = true): self
    {
        $this->options['emptyAtEnd'] = $value;
        return $this;
    }

    public function setNoGroupByOnCount(bool $value = true): self
    {
        $this->options['noGroupByOnCount'] = $value;
        return $this;
    }

    public function setDistinctCountGroup(bool $value = true): self
    {
        $this->options['distinctCountGroup'] = $value;
        return $this;
    }

    public function setReturnQuery(bool $value = true): self
    {
        $this->options['returnQuery'] = $value;
        return $this;
    }

    public function setOptions(array $options = []): self
    {
        foreach ($options as $key => $val) {
            if (!array_key_exists($key, $this->options)) {
                throw new Exception("The option $key is not valid.");
            }
            if (is_bool($this->options[$key])) {
                $val = (bool) $val;
            }
            $this->options[$key] = $val;
        }
        return $this;
    }

    public function setQueryKeepsLimits(bool $value = true): self
    {
        $this->options['queryKeepsLimits'] = $value;
        return $this;
    }

    public function getQueryBuilder()
    {
        $this->prepareEngine();
        $this->setReturnQuery();
        return $this->internalMake($this->columns, $this->searchColumns);
    }

    // ---------------- Core ----------------

    protected function internalMake(Collection $columns, array $searchColumns = [])
    {
        $builder      = clone $this->builder;
        $countBuilder = clone $this->builder;

        $builder      = $this->doInternalSearch($builder, $searchColumns);
        $countBuilder = $this->doInternalSearch($countBuilder, $searchColumns);

        // Count
        if ($this->options['distinctCountGroup'] && $this->hasSingleGroup($countBuilder)) {
            $groupCol = $countBuilder->groups[0];
            $countBuilder->select(DB::raw("COUNT(DISTINCT `$groupCol`) as total"));
            $countBuilder->groups = null;
            $result = $countBuilder->first();
            $this->options['counter'] = $result ? (int) $result->total : 0;
        } elseif ($this->options['searchWithAlias']) {
            $this->options['counter'] = $countBuilder->get()->count();
        } else {
            if ($this->options['noGroupByOnCount']) {
                $countBuilder->groups = null;
            }
            $this->options['counter'] = $countBuilder->count();
        }

        $builder = $this->doInternalOrder($builder, $columns);

        // Return raw query builder
        if ($this->options['returnQuery']) {
            return $this->options['queryKeepsLimits']
                ? $this->getQuery($builder)
                : $builder;
        }

        // Return compiled collection
        return $this->compile($builder, $columns);
    }

    private function getQuery($builder)
    {
        if (is_null($this->collection)) {
            if ($this->skip > 0) {
                $builder = $builder->skip($this->skip);
            }
            if ($this->limit > 0) {
                $builder = $builder->take($this->limit);
            }
        }
        return $builder;
    }

    private function getCollection($builder): Collection
    {
        $builder = $this->getQuery($builder);

        if (is_null($this->collection)) {
            $this->collection = $builder->get();
            if (is_array($this->collection)) {
                $this->collection = collect($this->collection);
            }
        }

        return $this->collection;
    }

    private function doInternalSearch($builder, array $columns)
    {
        if (!empty($this->search)) {
            $builder = $this->buildSearchQuery($builder, $columns);
        }

        if (!empty($this->columnSearches)) {
            $builder = $this->buildSingleColumnSearches($builder);
        }

        return $builder;
    }

    private function buildSearchQuery($builder, array $columns)
    {
        $like   = $this->options['searchOperator'];
        $search = $this->search;
        $exact  = $this->exactWordSearch;

        $builder->where(function ($query) use ($columns, $search, $like, $exact) {
            foreach ($columns as $c) {
                if (strrpos($c, ':')) {
                    $parts = explode(':', $c);
                    if (isset($parts[2])) {
                        $parts[1] .= "({$parts[2]})";
                    }
                    $query->orWhereRaw("CAST($parts[0] AS $parts[1]) $like ?", [$exact ? $search : "%$search%"]);
                } else {
                    $query->orWhere($c, $like, $exact ? $search : "%$search%");
                }
            }
        });

        return $builder;
    }

    private function buildSingleColumnSearches($builder)
    {
        foreach ($this->columnSearches as $index => $searchValue) {
            $field = $this->fieldSearches[$index] ?? null;
            if (!$field) continue;

            if (isset($this->columnSearchExact[$field]) && $this->columnSearchExact[$field]) {
                $builder->where($field, '=', $searchValue);
            } else {
                $builder->where($field, $this->options['searchOperator'], "%$searchValue%");
            }
        }
        return $builder;
    }

    private function compile($builder, Collection $columns): Collection
    {
        $this->resultCollection = $this->getCollection($builder);

        $self = $this;
        return $this->resultCollection->map(function ($row) use ($columns, $self) {
            $entry = [];

            if (is_callable($self->getRowClass())) {
                $entry['DT_RowClass'] = call_user_func($self->getRowClass(), $row);
            }
            if (is_callable($self->getRowId())) {
                $entry['DT_RowId'] = call_user_func($self->getRowId(), $row);
            }
            if (is_callable($self->getRowData())) {
                $entry['DT_RowData'] = call_user_func($self->getRowData(), $row);
            }

            $i = 0;
            foreach ($columns as $col) {
                $value = $col->run($row);
                if ($self->getAliasMapping()) {
                    $entry[$col->getName()] = $value;
                } else {
                    $entry[$i] = $value;
                }
                $i++;
            }

            return $entry;
        });
    }

    private function doInternalOrder($builder, Collection $columns)
    {
        if (!is_null($this->orderColumn)) {
            foreach ($this->orderColumn as $ordCol) {
                if (strrpos($ordCol[1], ':')) {
                    $c = explode(':', $ordCol[1]);
                    if (isset($c[2])) {
                        $c[1] .= "({$c[2]})";
                    }
                    $prefix = $this->options['emptyAtEnd'] ? "ISNULL({$c[0]}) asc," : '';
                    $builder->orderByRaw($prefix . " CAST($c[0] AS $c[1]) " . $this->orderDirection[$ordCol[0]]);
                } else {
                    $prefix = $this->options['emptyAtEnd'] ? "ISNULL({$ordCol[1]}) asc," : '';
                    $builder->orderByRaw($prefix . $ordCol[1] . ' ' . $this->orderDirection[$ordCol[0]]);
                }
            }
        }
        return $builder;
    }

    private function hasSingleGroup($builder): bool
    {
        return isset($builder->groups) && is_array($builder->groups) && count($builder->groups) === 1;
    }
}
