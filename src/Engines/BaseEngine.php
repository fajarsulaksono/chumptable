<?php

namespace Chumptable\Datatable\Engines;

use Chumptable\Datatable\Columns\DateColumn;
use Chumptable\Datatable\Columns\FunctionColumn;
use Chumptable\Datatable\Columns\TextColumn;
use Exception;
use Illuminate\Support\Collection;

/**
 * Class BaseEngine
 */
abstract class BaseEngine
{
    const ORDER_ASC = 'asc';

    const ORDER_DESC = 'desc';

    /**
     * DataTables protocol versions.
     */
    const DATATABLES_VERSION_1 = '1.x';

    const DATATABLES_VERSION_2 = '2.x';

    /**
     * DataTables protocol version used to interpret requests and build responses.
     *
     * @var string
     */
    protected $datatablesVersion = self::DATATABLES_VERSION_1;

    /**
     * Output format: 'legacy' (aaData) atau 'modern' (data).
     *
     * Null means auto-detect from the request/protocol version.
     *
     * @var string|null
     */
    protected $outputFormat = null;

    /**
     * Whether the current request uses the DataTables 1.10+/2.x format.
     *
     * @var bool
     */
    protected $modernRequest = false;

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
     *            support for DB::raw fields on where
     */
    protected $fieldSearches = [];

    /**
     * @var array
     *            support for DB::raw fields on where
     *            sburkett - added for column-based exact matching
     */
    protected $columnSearchExact = [];

    /**
     * @var mixed
     */
    protected $sEcho;

    /**
     * @var Collection
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

    /**
     * Column definitions received from a DataTables 1.10+/2.x request.
     *
     * @var array
     */
    protected $requestColumns = [];

    public function __construct()
    {
        $this->columns = collect();
        $this->config = config('datatable.engine', []);

        $this->setExactWordSearch($this->config['exactWordSearch'] ?? false);
        $this->setEnableDisplayAll($this->config['enableDisplayAll'] ?? false);

        $version = $this->config['version'] ?? config('datatable.version', self::DATATABLES_VERSION_1);

        $this->setDatatablesVersion(
            is_scalar($version) ? (string) $version : self::DATATABLES_VERSION_1
        );
    }

    /**
     * Set the DataTables protocol version.
     *
     * Accepted values: "1", "1.x", "1.10", "2", "2.x", "2.0".
     * Version 2 uses the modern request/response format by default.
     *
     * @return $this
     */
    public function setDatatablesVersion(string $version)
    {
        $this->datatablesVersion = $this->normalizeVersion($version);

        return $this;
    }

    /**
     * Resolve the response format for the current request.
     *
     * An explicit setOutputFormat() call always wins. Otherwise the request
     * format is detected (DataTables 1.10+/2.x), falling back to the
     * configured protocol version.
     *
     * @return string
     */
    protected function resolveOutputFormat()
    {
        if ($this->outputFormat !== null) {
            return $this->outputFormat;
        }

        if ($this->modernRequest) {
            return 'modern';
        }

        return $this->isDatatables2() ? 'modern' : 'legacy';
    }

    /**
     * @return string
     */
    public function getDatatablesVersion()
    {
        return $this->datatablesVersion;
    }

    /**
     * @return bool
     */
    public function isDatatables2()
    {
        return $this->datatablesVersion === self::DATATABLES_VERSION_2;
    }

    /**
     * Normalize a user supplied DataTables version.
     *
     * @return string
     */
    protected function normalizeVersion(string $version)
    {
        $version = strtolower(trim($version));

        if (str_starts_with($version, '2')) {
            return self::DATATABLES_VERSION_2;
        }

        return self::DATATABLES_VERSION_1;
    }

    /**
     * Set output format
     *
     * @return $this
     */
    public function setOutputFormat(string $format)
    {
        if (! in_array($format, ['legacy', 'modern'])) {
            throw new \InvalidArgumentException("Invalid output format: $format");
        }
        $this->outputFormat = $format;

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Exception
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
        if (! is_array($cols)) {
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
        // Resolve the default search columns before reading the request so
        // per-column searches also work when searchColumns() was not called.
        $this->prepareSearchColumns();
        $this->handleInputs();
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
        if (! is_array($cols)) {
            $cols = func_get_args();
        }

        $this->searchColumns = $cols;

        return $this;
    }

    public function orderColumns($cols)
    {
        if (! is_array($cols)) {
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

    // DataTables 1.9 (legacy) parameters

    protected function handleiDisplayStart($value)
    {
        $this->skip($value);
    }

    protected function handleiDisplayLength($value)
    {
        $this->handleLength($value);
    }

    protected function handlesEcho($value)
    {
        $this->sEcho = $value;
    }

    protected function handlesSearch($value)
    {
        $this->handleSearch($value);
    }

    protected function handleSearch($value)
    {
        // DataTables 1.10+/2.x send search as an object: search[value], search[regex].
        if (is_array($value)) {
            $value = $value['value'] ?? null;
        }

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

        $iSortingCols = (int) request()->input('iSortingCols', 1);
        $sortingCols = [$value];
        for ($i = 1; $i < $iSortingCols; $i++) {
            $isc = request()->input('iSortCol_'.$i);
            $sortingCols[] = $isc;
            $direction[$isc] = request()->input('sSortDir_'.$i);
        }

        $this->applyOrderColumns($sortingCols, $direction);
    }

    protected function handleSingleColumnSearch($columnIndex, $searchValue)
    {
        if (! isset($this->searchColumns[$columnIndex])) {
            return;
        }
        if ($searchValue === '' && $searchValue !== '0') {
            return;
        }

        $columnName = $this->searchColumns[$columnIndex];
        $this->searchOnColumn($columnName, $searchValue);
    }

    // DataTables 1.10+/2.x parameters

    protected function handleDraw($value)
    {
        $this->sEcho = $value;
    }

    protected function handleStart($value)
    {
        $this->skip((int) $value);
    }

    protected function handleLength($value)
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

    /**
     * Handle the DataTables 1.10+/2.x columns[] definition.
     *
     * Columns may contain a per-column search value and are also used to
     * resolve column names when the client uses alias mapping.
     */
    protected function handleColumns($value)
    {
        if (! is_array($value)) {
            return;
        }

        $this->requestColumns = $value;

        foreach ($value as $index => $column) {
            if (! is_array($column)) {
                continue;
            }

            if (isset($column['searchable']) && ! filter_var($column['searchable'], FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $searchValue = $column['search']['value'] ?? null;
            if ($searchValue === null || ($searchValue === '' && $searchValue !== '0')) {
                continue;
            }

            $columnName = $this->resolveColumnName($index, $column);
            if ($columnName === null) {
                continue;
            }

            $searchableColumns = ! empty($this->searchColumns)
                ? $this->searchColumns
                : array_keys($this->columns->all());

            if (! in_array($columnName, $searchableColumns, true)) {
                continue;
            }

            $this->searchOnColumn($columnName, $searchValue);
        }
    }

    /**
     * Handle the DataTables 1.10+/2.x order[] definition.
     */
    protected function handleOrder($value)
    {
        if (! is_array($value)) {
            return;
        }

        $direction = [];
        $sortingCols = [];

        foreach ($value as $order) {
            if (! is_array($order) || ! isset($order['column'])) {
                continue;
            }

            $index = (int) $order['column'];
            $sortingCols[] = $index;

            $dir = strtolower((string) ($order['dir'] ?? self::ORDER_ASC));
            $direction[$index] = $dir === self::ORDER_DESC ? self::ORDER_DESC : self::ORDER_ASC;
        }

        if (! empty($sortingCols)) {
            $this->applyOrderColumns($sortingCols, $direction);
        }
    }

    /**
     * Build the engine order definition from column indexes and directions.
     */
    protected function applyOrderColumns(array $sortingCols, array $direction)
    {
        $columns = [];

        if (empty($this->orderColumns)) {
            foreach ($sortingCols as $index) {
                $columnName = $this->resolveColumnName($index, $this->requestColumns[$index] ?? []);
                if ($columnName === null) {
                    continue;
                }

                $columns[] = [0 => $index, 1 => '`'.$columnName.'`'];
            }

            if (! empty($columns)) {
                $this->order($columns, $direction);
            }

            return;
        }

        $cleanNames = [];
        foreach ($this->orderColumns as $c) {
            $cleanNames[] = strpos($c, ':') !== false ? substr($c, 0, strpos($c, ':')) : $c;
        }

        $allColumns = array_keys($this->columns->all());
        foreach ($sortingCols as $num) {
            if (! isset($allColumns[$num])) {
                continue;
            }

            $columnName = $allColumns[$num];
            if (in_array($columnName, $cleanNames)) {
                $columns[] = [0 => $num, 1 => '`'.$this->orderColumns[array_search($columnName, $cleanNames)].'`'];
            }
        }

        if (! empty($columns)) {
            $this->order($columns, $direction);
        }
    }

    /**
     * Resolve the engine column name for a DataTables column definition.
     *
     * @param  int|string  $index
     * @return string|null
     */
    protected function resolveColumnName($index, array $column = [])
    {
        // The engine column order is the most reliable source, since it
        // matches the order in which columns were registered for the table.
        $name = $this->getNameByIndex($index);
        if ($name !== null) {
            return $name;
        }

        foreach (['data', 'name'] as $key) {
            if (isset($column[$key]) && is_string($column[$key]) && $column[$key] !== '' && ! is_numeric($column[$key])) {
                return $column[$key];
            }
        }

        return null;
    }

    protected function handleInputs()
    {
        // DataTables 1.10+/2.x parameters. These are handled explicitly since
        // PHP's method_exists() is case sensitive, while DataTables sends
        // lower camelCase parameter names.
        $modernHandlers = [
            'draw' => 'handleDraw',
            'start' => 'handleStart',
            'length' => 'handleLength',
            'search' => 'handleSearch',
            'order' => 'handleOrder',
            'columns' => 'handleColumns',
        ];

        $input = request()->all();

        $this->modernRequest = array_key_exists('draw', $input) && (
            array_key_exists('start', $input)
            || array_key_exists('length', $input)
            || array_key_exists('columns', $input)
            || array_key_exists('order', $input)
        );

        foreach ($input as $key => $value) {
            if (isset($modernHandlers[$key])) {
                $this->{$modernHandlers[$key]}($value);

                continue;
            }

            if ($this->isParameterForSingleColumnSearch($key)) {
                $columnIndex = str_replace('sSearch_', '', $key);
                $this->handleSingleColumnSearch($columnIndex, $value);

                continue;
            }

            if (method_exists($this, $function = 'handle'.$key)) {
                $this->$function($value);
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
        $this->orderColumn = $column;
        $this->orderDirection = $order;
    }

    protected function search($value)
    {
        $this->search = $value;
    }

    protected function searchOnColumn($columnName, $value)
    {
        $this->fieldSearches[] = $columnName;
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

        if ($this->resolveOutputFormat() === 'modern') {
            // Format modern sesuai DataTables 1.10+/2.x
            return [
                'draw' => intval($this->sEcho), // alias draw
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
                'additional' => $this->additionalData,
            ];
        }

        // Default = legacy format
        return [
            'aaData' => $data,
            'sEcho' => intval($this->sEcho),
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaAdditional' => $this->additionalData,
        ];
    }

    abstract protected function totalCount();

    abstract protected function count();

    abstract protected function internalMake(Collection $columns, array $searchColumns = []);
}
