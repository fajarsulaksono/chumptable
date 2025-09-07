<?php

namespace Chumptable\Datatable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class Table
 * @package Chumptable\Datatable
 */
class Table
{
    private array $config = [];
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

        $this->setId($this->config['id'] ?? Str::random(8));
        $this->setClass($this->config['class'] ?? '');
        $this->setOptions($this->config['options'] ?? []);
        $this->setCallbacks($this->config['callbacks'] ?? []);

        $this->noScript    = $this->config['noScript'] ?? false;
        $this->table_view  = $this->config['table_view'] ?? 'datatable::table';
        $this->script_view = $this->config['script_view'] ?? 'datatable::script';
    }

    public function addColumn(...$titles): self
    {
        foreach ($titles as $title) {
            if (is_array($title)) {
                foreach ($title as $mapping => $arrayTitle) {
                    $this->columns[]      = $arrayTitle;
                    $this->aliasColumns[] = $mapping;
                    if (is_string($mapping)) {
                        $this->createdMapping = false;
                    }
                }
            } else {
                $this->columns[]      = $title;
                $this->aliasColumns[] = count($this->aliasColumns) + 1;
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
        unset($this->options[$key]);
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setOptions(...$args): self
    {
        if (count($args) === 2) {
            $this->options[$args[0]] = $args[1];
        } elseif (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $option) {
                $this->options[$key] = $option;
            }
        } else {
            throw new InvalidArgumentException('Invalid number of options provided for "setOptions".');
        }
        return $this;
    }

    public function setOrder(array $order = []): self
    {
        $orders = [];
        foreach ($order as $number => $sort) {
            $orders[] = "[ {$number}, \"{$sort}\" ]";
        }

        $this->callbacks['aaSorting'] = '[' . implode(', ', $orders) . ']';
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setCallbacks(...$args): self
    {
        if (count($args) === 2) {
            $this->callbacks[$args[0]] = $args[1];
        } elseif (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->callbacks[$key] = $value;
            }
        } else {
            throw new InvalidArgumentException('Invalid number of callbacks provided for "setCallbacks".');
        }

        return $this;
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

        if (!isset($this->options['sAjaxSource']) && !isset($this->options['ajax'])) {
            $this->setUrl(Request::url());
        }

        if (!$this->createdMapping) {
            $this->createMapping();
        }

        $vars = [
            'options'    => $this->options,
            'callbacks'  => $this->callbacks,
            'values'     => $this->customValues,
            'data'       => $this->data,
            'columns'    => array_combine($this->aliasColumns, $this->columns),
            'noScript'   => $this->noScript,
            'id'         => $this->idName,
            'class'      => $this->className,
            'footerMode' => $this->footerMode,
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

        if (!$this->createdMapping) {
            $this->createMapping();
        }

        return View::make($this->script_view, [
            'options'   => $this->options,
            'callbacks' => $this->callbacks,
            'id'        => $this->idName,
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
        $this->createdMapping = !$value;
        return $this;
    }

    private function createMapping(): void
    {
        if (!array_key_exists('aoColumns', $this->options)) {
            $this->options['aoColumns'] = [];
        }

        foreach ($this->aliasColumns as $i => $name) {
            if (array_key_exists($i, $this->options['aoColumns'])) {
                $this->options['aoColumns'][$i] = array_merge(
                    $this->options['aoColumns'][$i],
                    ['mData' => $name]
                );
            } else {
                $this->options['aoColumns'][$i] = ['mData' => $name];
            }
        }

        $this->createdMapping = true;
    }
}
