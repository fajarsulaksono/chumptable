<?php

namespace Chumptable\Datatable;

use Chumptable\Datatable\Engines\BaseEngine;
use Chumptable\Datatable\Engines\CollectionEngine;
use Chumptable\Datatable\Engines\QueryEngine;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

/**
 * Class Datatable
 */
class Datatable
{
    const DATATABLES_VERSION_1 = BaseEngine::DATATABLES_VERSION_1;

    const DATATABLES_VERSION_2 = BaseEngine::DATATABLES_VERSION_2;

    protected $outputFormat = null; // null = follow the configured version

    /**
     * @var string|null
     */
    protected $datatablesVersion = null;

    /**
     * @return QueryEngine
     */
    public function query($query)
    {
        return $this->applyVersion(new QueryEngine($query));
    }

    /**
     * @return CollectionEngine
     */
    public function collection($collection)
    {
        return $this->applyVersion(new CollectionEngine($collection));
    }

    /**
     * @return Table
     */
    public function table()
    {
        $table = new Table;

        if ($this->datatablesVersion !== null) {
            $table->setDatatablesVersion($this->datatablesVersion);
        }

        return $table;
    }

    /**
     * Set the DataTables protocol version for engines and tables created
     * through this instance.
     *
     * @return $this
     */
    public function setDatatablesVersion(string $version)
    {
        $this->datatablesVersion = str_starts_with(strtolower(trim($version)), '2')
            ? self::DATATABLES_VERSION_2
            : self::DATATABLES_VERSION_1;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDatatablesVersion()
    {
        return $this->datatablesVersion;
    }

    /**
     * @param  BaseEngine  $engine
     * @return BaseEngine
     */
    protected function applyVersion($engine)
    {
        if ($this->datatablesVersion !== null) {
            $engine->setDatatablesVersion($this->datatablesVersion);
        }

        if ($this->outputFormat !== null) {
            $engine->setOutputFormat($this->outputFormat);
        }

        return $engine;
    }

    /**
     * @return bool True if the plugin should handle this request, false otherwise
     */
    public function shouldHandle()
    {
        // DataTables 1.9 uses sEcho, DataTables 1.10+/2.x uses draw.
        $echo = Request::input('draw', Request::input('sEcho', null));

        if (/* request()->ajax() && */ ! is_null($echo) && is_numeric($echo)) {
            return true;
        }

        return false;
    }

    /**
     * Set output format
     */
    public function setOutputFormat($format)
    {
        if (! in_array($format, ['legacy', 'modern'])) {
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
        if ($this->outputFormat !== null) {
            return $this->outputFormat;
        }

        return $this->datatablesVersion === self::DATATABLES_VERSION_2 ? 'modern' : 'legacy';
    }

    /**
     * Create a new datatable instance from a source.
     *
     * @param  mixed  $source
     * @return BaseEngine
     *
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
