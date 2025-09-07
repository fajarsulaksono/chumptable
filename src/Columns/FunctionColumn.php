<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Columns;

/**
 * Class FunctionColumn
 *
 * Column type that renders a value using a user-defined callable.
 */
class FunctionColumn extends BaseColumn
{
    /**
     * The user-defined callable.
     *
     * @var callable
     */
    private $callable;

    /**
     * FunctionColumn constructor.
     *
     * @param string   $name
     * @param callable $callable
     */
    public function __construct(string $name, callable $callable)
    {
        parent::__construct($name);
        $this->callable = $callable;
    }

    /**
     * Run the column transformation.
     *
     * @param mixed $model
     * @return mixed
     */
    public function run($model)
    {
        return \call_user_func($this->callable, $model);
    }
}
