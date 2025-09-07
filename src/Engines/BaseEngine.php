<?php

namespace Chumptable\Datatable\Engines;

use Exception;
use Chumptable\Datatable\Columns\DateColumn;
use Chumptable\Datatable\Columns\FunctionColumn;
use Chumptable\Datatable\Columns\TextColumn;
use Illuminate\Support\Collection;

/**
 * Class BaseEngine
 * @package Chumptable\Datatable\Engines
 */
abstract class BaseEngine
{
    const ORDER_ASC  = 'asc';
    const ORDER_DESC = 'desc';

    /**
     * Output format: 'legacy' (aaData) atau 'modern' (data)
     * @var string
     */
    protected $outputFormat = 'legacy';

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var mixed
     */
    protected $rowClass = null;

    /**
     * @var mixed
     */
    protected $rowId = null;

    /**
     * @var mixed
     */
    protected $rowData = null;

    /**
     * @var array
     */
    protected $columnSearches = [];

    /**
     * @var array
     * support for DB::raw fields on where
     */
    protected $fieldSearches = [];

    /**
     * @var array
     * support for DB::raw fields on where
     * sburkett - added for column-based exact matching
     */
    protected $columnSearchExact = [];

    /**
     * @var mixed
     */
    protected $sEcho;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $columns;

    /**
     * @var array
     */
    protected $searchColumns = [];

    /**
     * @var array
     */
    protected $showColumns = [];

    /**
     * @var array
     */
    protected $orderColumns = [];

    /**
     * @var int
     */
    protected $skip = 0;

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var string|null
     */
    protected $search = null;

    /**
     * @var array|null
     */
    protected $orderColumn = null;

    /**
     * @var string
     */
    protected $orderDirection = self::ORDER_ASC;

    /**
     * @var bool If the return should be alias mapped
     */
    protected $aliasMapping = false;

    /**
     * @var bool If the search should be done with exact matching
     */
    protected $exactWordSearch = false;

    /**
     * @var bool If you need to display all records.
     */
    protected $enableDisplayAll = false;

    /**
     * @var mixed Additional data which passed from server to client.
     */
    protected $additionalData = null;

    public function __construct()
    {
        $this->columns = collect();
        $this->config  = config('datatable.engine', []);

        $this->setExactWordSearch($this->config['exactWordSearch'] ?? false);
        $this->setEnableDisplayAll($this->config['enableDisplayAll'] ?? false);
    }

    /**
     * Set output format
     *
     * @param string $format
     * @return $this
     */
    public function setOutputFormat(string $format)
    {
        if (!in_array($format, ['legacy', 'modern'])) {
            throw new \InvalidArgumentException("Invalid output format: $format");
        }
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function addColumn()
    {
        $argc = func_num_args();

        if ($argc !== 1 && $argc !== 2) {
            throw new Exception('Invalid number of arguments');
        }

        if ($argc === 1) {
            $this->columns->put(func_get_arg(0)->getName(), func_get_arg(0));
        } elseif (is_callable(func_get_arg(1))) {
            $this->columns->put(func_get_arg(0), new FunctionColumn(func_get_arg(0), func_get_arg(1)));
        } else {
            $this->columns->put(func_get_arg(0), new TextColumn(func_get_arg(0), func_get_arg(1)));
        }

        return $this;
    }

    public function getColumn($name)
    {
        return $this->columns->get($name, null);
    }

    public function getOrder()
    {
        return array_keys($this->columns->toArray());
    }

    public function getOrderingColumns()
    {
        return $this->orderColumns;
    }

    public function getSearchingColumns()
    {
        return $this->searchColumns;
    }

    public function clearColumns()
    {
        $this->columns = collect();
        return $this;
    }

    public function showColumns($cols)
    {
        if (!is_array($cols)) {
            $cols = func_get_args();
        }

        foreach ($cols as $property) {
            if (in_array($property, ['created_at', 'updated_at'])) {
                $this->columns->put($property, new DateColumn($property, DateColumn::DAY_DATE));
            } else {
                $this->columns->put($property, new FunctionColumn($property, function ($model) use ($property) {
                    try {
                        return is_array($model) ? $model[$property] : $model->$property;
                    } catch (Exception $e) {
                        return null;
                    }
                }));
            }
            $this->showColumns[] = $property;
        }

        return $this;
    }

    protected function prepareEngine()
    {
        $this->handleInputs();
        $this->prepareSearchColumns();
    }

    /**
     * Return JSON response for DataTables
     */
    public function make()
    {
        return response()->json($this->output());
    }


    public function searchColumns($cols)
    {
        if (!is_array($cols)) {
            $cols = func_get_args();
        }

        $this->searchColumns = $cols;
        return $this;
    }

    public function orderColumns($cols)
    {
        if (!is_array($cols)) {
            $cols = func_get_args();
        }

        $this->orderColumns = $cols;
        return $this;
    }

    public function setRowClass($function)
    {
        $this->rowClass = $function;
        return $this;
    }

    public function setRowId($function)
    {
        $this->rowId = $function;
        return $this;
    }

    public function setRowData($function)
    {
        $this->rowData = $function;
        return $this;
    }

    public function setAliasMapping($value = true)
    {
        $this->aliasMapping = $value;
        return $this;
    }

    public function setExactWordSearch($value = true)
    {
        $this->exactWordSearch = $value;
        return $this;
    }

    public function setEnableDisplayAll($value = true)
    {
        $this->enableDisplayAll = $value;
        return $this;
    }

    public function setExactMatchColumns($columnNames)
    {
        foreach ($columnNames as $columnIndex) {
            $this->columnSearchExact[$columnIndex] = true;
        }
        return $this;
    }

    public function setAdditionalData($data)
    {
        $this->additionalData = $data;
        return $this;
    }

    public function getRowClass()
    {
        return $this->rowClass;
    }

    public function getRowId()
    {
        return $this->rowId;
    }

    public function getRowData()
    {
        return $this->rowData;
    }

    public function getAliasMapping()
    {
        return $this->aliasMapping;
    }

    public function getEnableDisplayAll()
    {
        return $this->enableDisplayAll;
    }

    // ---------------- Protected Handlers -------------------

    protected function handleiDisplayStart($value)
    {
        $this->skip($value);
    }

    protected function handleiDisplayLength($value)
    {
        if (is_numeric($value)) {
            if ($value > -1) {
                $this->take($value);
                return;
            } elseif ($value == -1 && $this->enableDisplayAll) {
                return; // Display all
            }
        }

        $this->take($this->config['defaultDisplayLength'] ?? 10);
    }

    protected function handlesEcho($value)
    {
        $this->sEcho = $value;
    }

    protected function handlesSearch($value)
    {
        $this->search($value);
    }

    protected function handleiSortCol_0($value)
    {
        $direction = [];

        if (request()->input('sSortDir_0') === 'desc') {
            $direction[$value] = self::ORDER_DESC;
        } else {
            $direction[$value] = self::ORDER_ASC;
        }

        $columns = [];
        if (empty($this->orderColumns)) {
            $columns[] = [0 => $value, 1 => '`' . $this->getNameByIndex($value) . '`'];
            $this->order($columns, $direction);
            return;
        }

        $cleanNames = [];
        foreach ($this->orderColumns as $c) {
            $cleanNames[] = strpos($c, ':') !== false ? substr($c, 0, strpos($c, ':')) : $c;
        }

        $iSortingCols = request()->input('iSortingCols');
        $sortingCols  = [$value];
        for ($i = 1; $i < $iSortingCols; $i++) {
            $isc          = request()->input('iSortCol_' . $i);
            $sortingCols[] = $isc;
            $direction[$isc] = request()->input('sSortDir_' . $i);
        }

        $allColumns = array_keys($this->columns->all());
        foreach ($sortingCols as $num) {
            if (isset($allColumns[$num]) && in_array($allColumns[$num], $cleanNames)) {
                $columns[] = [0 => $num, 1 => '`' . $this->orderColumns[array_search($allColumns[$num], $cleanNames)] . '`'];
            }
        }

        $this->order($columns, $direction);
    }

    protected function handleSingleColumnSearch($columnIndex, $searchValue)
    {
        if (!isset($this->searchColumns[$columnIndex])) return;
        if ($searchValue === '' && $searchValue !== '0') return;

        $columnName = $this->searchColumns[$columnIndex];
        $this->searchOnColumn($columnName, $searchValue);
    }

    protected function handleInputs()
    {
        foreach (request()->all() as $key => $input) {
            if ($this->isParameterForSingleColumnSearch($key)) {
                $columnIndex = str_replace('sSearch_', '', $key);
                $this->handleSingleColumnSearch($columnIndex, $input);
                continue;
            }

            if (method_exists($this, $function = 'handle' . $key)) {
                $this->$function($input);
            }
        }
    }

    protected function isParameterForSingleColumnSearch($parameterName)
    {
        return strpos($parameterName, 'sSearch_') === 0;
    }

    protected function prepareSearchColumns()
    {
        if (count($this->searchColumns) === 0) {
            $this->searchColumns = $this->showColumns;
        }
    }

    protected function order($column, $order = self::ORDER_ASC)
    {
        $this->orderColumn    = $column;
        $this->orderDirection = $order;
    }

    protected function search($value)
    {
        $this->search = $value;
    }

    protected function searchOnColumn($columnName, $value)
    {
        $this->fieldSearches[]  = $columnName;
        $this->columnSearches[] = $value;
    }

    protected function skip($value)
    {
        $this->skip = $value;
    }

    protected function take($value)
    {
        $this->limit = $value;
    }

    public function getNameByIndex($index)
    {
        $i = 0;
        foreach ($this->columns as $name => $col) {
            if ($index == $i) {
                return $name;
            }
            $i++;
        }
        return null;
    }

    public function getExactWordSearch()
    {
        return $this->exactWordSearch;
    }

    // di class BaseEngine

    /**
     * Return output array tanpa Laravel Response.
     * Berguna untuk unit testing agar tidak perlu container/response binding.
     */
    public function toArray()
    {
        // kalau memang ada method internal generate output
        // ganti sesuai implementasi asli (biasanya protected output())
        return $this->outputArray();
    }

    /**
     * Pastikan ada method ini yang menghasilkan array raw
     * Kalau di versi kamu ada `output()` protected, cukup panggil itu
     */
    protected function outputArray()
    {
        return $this->output(); // gunakan yang sudah ada
    }

    /**
     * Generate output array.
     * Bisa pilih legacy atau modern.
     *
     * @return array
     */
    public function output()
    {
        $this->prepareEngine();

        $data = $this->internalMake($this->columns, $this->searchColumns)->toArray();
        $total = $this->totalCount();
        $filtered = $this->count();

        if ($this->outputFormat === 'modern') {
            // Format modern sesuai DataTables 1.10+
            return [
                "draw"            => intval($this->sEcho), // alias draw
                "recordsTotal"    => $total,
                "recordsFiltered" => $filtered,
                "data"            => $data,
                "additional"      => $this->additionalData,
            ];
        }

        // Default = legacy format
        return [
            "aaData"               => $data,
            "sEcho"                => intval($this->sEcho),
            "iTotalRecords"        => $total,
            "iTotalDisplayRecords" => $filtered,
            "aaAdditional"         => $this->additionalData,
        ];
    }

    abstract protected function totalCount();
    abstract protected function count();
    abstract protected function internalMake(Collection $columns, array $searchColumns = []);
}
