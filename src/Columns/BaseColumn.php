<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Columns;

/**
 * Class BaseColumn
 *
 * Base class for defining a custom column type in Datatable.
 */
abstract class BaseColumn
{
    /**
     * @var string Name of the column
     */
    protected $name;

    /**
     * BaseColumn constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    /**
     * Run the column transformation.
     *
     * @param mixed $model The data to pass to the column,
     *                     could be a model or an array.
     * @return mixed The return value of the implementation,
     *               should be text in most of the cases.
     */
    abstract public function run($model);

    /**
     * Get the name of the column.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
