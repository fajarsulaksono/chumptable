<?php

namespace Chumptable\Datatable;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Chumptable\Datatable\Engines\QueryEngine;
use Chumptable\Datatable\Engines\CollectionEngine;

/**
 * Class Datatable
 * @package Chumptable\Datatable
 */
class Datatable
{

    protected $outputFormat = 'legacy'; // 'legacy'

    /**
     * @param $query
     * @return QueryEngine
     */
    public function query($query)
    {
        return new QueryEngine($query);
    }

    /**
     * @param $collection
     * @return CollectionEngine
     */
    public function collection($collection)
    {
        return new CollectionEngine($collection);
    }

    /**
     * @return Table
     */
    public function table()
    {
        return new Table;
    }

    /**
     * @return bool True if the plugin should handle this request, false otherwise
     */
    public function shouldHandle()
    {
        $echo = Request::input('sEcho', null);

        if (/* request()->ajax() && */!is_null($echo) && is_numeric($echo)) {
            return true;
        }

        return false;
    }

    /**
     * Set output format
     */
    public function setOutputFormat($format)
    {
        if (!in_array($format, ['legacy', 'modern'])) {
            throw new \InvalidArgumentException("Invalid format: $format");
        }
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * Get output format
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

/**
     * Create a new datatable instance from a source.
     *
     * @param mixed $source
     * @return \Chumptable\Datatable\Engines\BaseEngine
     * @throws \InvalidArgumentException
     */
    public static function of($source)
    {
        if ($source instanceof QueryBuilder || $source instanceof EloquentBuilder) {
            return new QueryEngine($source);
        }

        if ($source instanceof Collection || is_array($source)) {
            return new CollectionEngine(collect($source));
        }

        throw new \InvalidArgumentException(
            'Datatable::of() only accepts QueryBuilder, EloquentBuilder, Collection, or array.'
        );
    }
}
