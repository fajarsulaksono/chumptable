<?php

namespace Chumptable\Datatable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class Table
 */
class Table
{
    const DATATABLES_VERSION_1 = '1.x';

    const DATATABLES_VERSION_2 = '2.x';

    private array $config = [];

    protected string $datatablesVersion = self::DATATABLES_VERSION_1;

    private array $columns = [];

    private array $options = [];

    private array $callbacks = [];

    private array $customValues = [];

    private array $data = [];

    private bool $noScript = false;

    protected string $idName;

    protected string $className;

    protected string $footerMode = 'hidden';

    protected string $table_view;

    protected string $script_view;

    private bool $createdMapping = true;

    private array $aliasColumns = [];

    public function __construct()
    {
        $this->config = Config::get('datatable.table', []);

        $version = $this->config['version'] ?? Config::get('datatable.version', self::DATATABLES_VERSION_1);

        $this->datatablesVersion = $this->normalizeVersion(
            is_scalar($version) ? (string) $version : self::DATATABLES_VERSION_1
        );

        $this->setId($this->config['id'] ?? Str::random(8));
        $this->setClass($this->config['class'] ?? '');
        $this->setOptions($this->config['options'] ?? []);
        $this->setCallbacks($this->config['callbacks'] ?? []);

        $this->noScript = $this->config['noScript'] ?? false;
        $this->table_view = $this->config['table_view'] ?? 'chumptable::template';
        $this->script_view = $this->config['script_view'] ?? 'chumptable::javascript';
    }

    /**
     * Set the DataTables protocol version used for the generated markup.
     *
     * @return $this
     */
    public function setDatatablesVersion(string $version): self
    {
        $this->datatablesVersion = $this->normalizeVersion($version);

        if ($this->isDatatables2()) {
            $this->options = $this->normalizeOptions($this->options);
            $this->callbacks = $this->normalizeCallbacks($this->callbacks);
        }

        return $this;
    }

    public function getDatatablesVersion(): string
    {
        return $this->datatablesVersion;
    }

    public function isDatatables2(): bool
    {
        return $this->datatablesVersion === self::DATATABLES_VERSION_2;
    }

    private function normalizeVersion(string $version): string
    {
        return str_starts_with(strtolower(trim($version)), '2')
            ? self::DATATABLES_VERSION_2
            : self::DATATABLES_VERSION_1;
    }

    public function addColumn(...$titles): self
    {
        foreach ($titles as $title) {
            if (is_array($title)) {
                foreach ($title as $mapping => $arrayTitle) {
                    $this->columns[] = $arrayTitle;
                    $this->aliasColumns[] = $mapping;
                    if (is_string($mapping)) {
                        $this->createdMapping = false;
                    }
                }
            } else {
                $this->columns[] = $title;
                // Numeric columns are addressed by their zero-based index.
                $this->aliasColumns[] = count($this->aliasColumns);
            }
        }

        return $this;
    }

    public function countColumns(): int
    {
        return count($this->columns);
    }

    public function removeOption(string $key): self
    {
        if ($this->isDatatables2()) {
            [$key] = $this->normalizeOption($key, null);
        }

        unset($this->options[$key]);

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setOptions(...$args): self
    {
        if (count($args) === 2) {
            $this->applyOption($args[0], $args[1]);
        } elseif (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $option) {
                $this->applyOption($key, $option);
            }
        } else {
            throw new InvalidArgumentException('Invalid number of options provided for "setOptions".');
        }

        return $this;
    }

    public function setOrder(array $order = []): self
    {
        if ($this->isDatatables2()) {
            $orders = [];
            foreach ($order as $number => $sort) {
                $orders[] = [(int) $number, $sort];
            }

            $this->options['order'] = $orders;
            unset($this->callbacks['aaSorting']);

            return $this;
        }

        $orders = [];
        foreach ($order as $number => $sort) {
            $orders[] = "[ {$number}, \"{$sort}\" ]";
        }

        $this->callbacks['aaSorting'] = '['.implode(', ', $orders).']';

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setCallbacks(...$args): self
    {
        if (count($args) === 2) {
            $this->applyCallback($args[0], $args[1]);
        } elseif (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->applyCallback($key, $value);
            }
        } else {
            throw new InvalidArgumentException('Invalid number of callbacks provided for "setCallbacks".');
        }

        return $this;
    }

    /**
     * Add an option, translating legacy DataTables 1.x names when needed.
     */
    private function applyOption(string $key, $value): void
    {
        if ($this->isDatatables2()) {
            [$key, $value] = $this->normalizeOption($key, $value);
        }

        $this->options[$key] = $value;
    }

    /**
     * Add a callback, translating legacy DataTables 1.x names when needed.
     */
    private function applyCallback(string $key, $value): void
    {
        if ($this->isDatatables2()) {
            if ($key === 'aaSorting') {
                $this->options['order'] = $value;

                return;
            }

            $key = $this->callbackName($key);
        }

        $this->callbacks[$key] = $value;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setCustomValues(...$args): self
    {
        if (count($args) === 2) {
            $this->customValues[$args[0]] = $args[1];
        } elseif (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->customValues[$key] = $value;
            }
        } else {
            throw new InvalidArgumentException('Invalid number of custom values provided for "setCustomValues".');
        }

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setUrl(string $url): self
    {
        if ($this->isDatatables2()) {
            $this->options['ajax'] = $url;
            $this->options['serverSide'] = true;

            return $this;
        }

        $this->options['sAjaxSource'] = $url;
        $this->options['bServerSide'] = true;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function getCustomValues(): array
    {
        return $this->customValues;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(?string $view = null, array $additional = []): string
    {
        if ($view !== null) {
            $this->table_view = $view;
        }

        if (! isset($this->options['sAjaxSource']) && ! isset($this->options['ajax'])) {
            $this->setUrl(Request::url());
        }

        if (! $this->createdMapping) {
            $this->createMapping();
        }

        $vars = [
            'options' => $this->options,
            'callbacks' => $this->callbacks,
            'values' => $this->customValues,
            'data' => $this->data,
            'columns' => array_combine($this->aliasColumns, $this->columns),
            'noScript' => $this->noScript,
            'id' => $this->idName,
            'class' => $this->className,
            'footerMode' => $this->footerMode,
            'script_view' => $this->script_view,
        ];

        return View::make($this->table_view, $vars + $additional)->render();
    }

    public function noScript(): self
    {
        $this->noScript = true;

        return $this;
    }

    public function script(?string $view = null): string
    {
        if ($view !== null) {
            $this->script_view = $view;
        }

        if (! $this->createdMapping) {
            $this->createMapping();
        }

        return View::make($this->script_view, [
            'options' => $this->options,
            'callbacks' => $this->callbacks,
            'id' => $this->idName,
        ])->render();
    }

    public function getId(): string
    {
        return $this->idName;
    }

    public function setId(string $id = ''): self
    {
        $this->idName = $id ?: Str::random(8);

        return $this;
    }

    public function getClass(): string
    {
        return $this->className;
    }

    public function setClass(string $class): self
    {
        $this->className = $class;

        return $this;
    }

    public function showFooter(string $value = 'columns'): self
    {
        $this->footerMode = $value;

        return $this;
    }

    public function setAliasMapping(bool $value = true): self
    {
        $this->createdMapping = ! $value;

        return $this;
    }

    private function createMapping(): void
    {
        $columnKey = $this->isDatatables2() ? 'columns' : 'aoColumns';
        $dataKey = $this->isDatatables2() ? 'data' : 'mData';

        if (! array_key_exists($columnKey, $this->options) || ! is_array($this->options[$columnKey])) {
            $this->options[$columnKey] = [];
        }

        foreach ($this->aliasColumns as $i => $name) {
            if (array_key_exists($i, $this->options[$columnKey])) {
                $this->options[$columnKey][$i] = array_merge(
                    (array) $this->options[$columnKey][$i],
                    [$dataKey => $name]
                );
            } else {
                $this->options[$columnKey][$i] = [$dataKey => $name];
            }
        }

        $this->createdMapping = true;
    }

    /**
     * Translate legacy DataTables 1.x option names and structures to the
     * DataTables 2.x equivalents.
     *
     * @return array{0: string, 1: mixed}
     */
    private function normalizeOption(string $key, $value): array
    {
        $map = [
            'sPaginationType' => 'pagingType',
            'bProcessing' => 'processing',
            'bServerSide' => 'serverSide',
            'sAjaxSource' => 'ajax',
            'aaSorting' => 'order',
            'aoColumns' => 'columns',
            'mData' => 'data',
            'bSortable' => 'orderable',
            'bSearchable' => 'searchable',
            'bVisible' => 'visible',
            'sClass' => 'className',
            'sTitle' => 'title',
            'sName' => 'name',
            'sWidth' => 'width',
            'sDefaultContent' => 'defaultContent',
            'bAutoWidth' => 'autoWidth',
            'bDeferRender' => 'deferRender',
            'bDestroy' => 'destroy',
            'bFilter' => 'searching',
            'bInfo' => 'info',
            'bLengthChange' => 'lengthChange',
            'bPaginate' => 'paging',
            'bSort' => 'ordering',
            'bStateSave' => 'stateSave',
            'iDisplayLength' => 'pageLength',
            'iDisplayStart' => 'displayStart',
            'oSearch' => 'search',
            'iTabIndex' => 'tabIndex',
        ];

        $key = $map[$key] ?? $key;
        $value = $this->normalizeOptionValue($key, $value);

        return [$key, $value];
    }

    private function normalizeOptionValue(string $key, $value)
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($key === 'columns' || $key === 'aoColumns') {
            foreach ($value as $index => $column) {
                if (is_array($column)) {
                    $value[$index] = $this->normalizeColumnDefinition($column);
                }
            }

            return $value;
        }

        if ($key === 'search') {
            $searchMap = [
                'sSearch' => 'value',
                'bRegex' => 'regex',
                'bSmart' => 'smart',
            ];

            $normalized = [];
            foreach ($value as $searchKey => $searchValue) {
                $normalized[$searchMap[$searchKey] ?? $searchKey] = $searchValue;
            }

            return $normalized;
        }

        if ($key === 'ajax' && is_array($value)) {
            $ajaxMap = [
                'sUrl' => 'url',
                'sType' => 'type',
                'sData' => 'data',
                'sDataType' => 'dataType',
                'sDataSrc' => 'dataSrc',
            ];

            $normalized = [];
            foreach ($value as $ajaxKey => $ajaxValue) {
                $normalized[$ajaxMap[$ajaxKey] ?? $ajaxKey] = $ajaxValue;
            }

            return $normalized;
        }

        return $value;
    }

    private function normalizeColumnDefinition(array $column): array
    {
        $map = [
            'mData' => 'data',
            'bSortable' => 'orderable',
            'bSearchable' => 'searchable',
            'bVisible' => 'visible',
            'sClass' => 'className',
            'sTitle' => 'title',
            'sName' => 'name',
            'sWidth' => 'width',
            'sDefaultContent' => 'defaultContent',
            'sType' => 'type',
        ];

        $normalized = [];
        foreach ($column as $key => $value) {
            if ($key === 'search' && is_array($value)) {
                $searchMap = ['sSearch' => 'value', 'bRegex' => 'regex', 'bSmart' => 'smart'];
                $search = [];
                foreach ($value as $searchKey => $searchValue) {
                    $search[$searchMap[$searchKey] ?? $searchKey] = $searchValue;
                }
                $normalized['search'] = $search;

                continue;
            }

            $normalized[$map[$key] ?? $key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $key => $value) {
            [$key, $value] = $this->normalizeOption($key, $value);
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $callbacks
     * @return array<string, mixed>
     */
    private function normalizeCallbacks(array $callbacks): array
    {
        $normalized = [];
        foreach ($callbacks as $key => $value) {
            if ($key === 'aaSorting') {
                $this->options['order'] = $value;

                continue;
            }

            $normalized[$this->callbackName($key)] = $value;
        }

        return $normalized;
    }

    private function callbackName(string $key): string
    {
        $map = [
            'fnDrawCallback' => 'drawCallback',
            'fnInitComplete' => 'initComplete',
            'fnPreDrawCallback' => 'preDrawCallback',
            'fnHeaderCallback' => 'headerCallback',
            'fnFooterCallback' => 'footerCallback',
            'fnInfoCallback' => 'infoCallback',
            'fnCreatedRow' => 'createdRow',
            'fnStateSaveParams' => 'stateSaveParams',
            'fnStateLoadParams' => 'stateLoadParams',
            'fnStateLoaded' => 'stateLoaded',
            'fnStateSave' => 'stateSave',
            'fnStateLoad' => 'stateLoad',
        ];

        return $map[$key] ?? $key;
    }
}
