<?php

namespace Tests\Columns;

use Chumptable\Datatable\Columns\FunctionColumn;
use Orchestra\Testbench\TestCase;
class FunctionColumnTest extends TestCase
{
    public function testSimple()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return "FooBar";
        });

        $this->assertEquals('FooBar', $column->run([]));
    }

    public function testAdvanced()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return $model['text'];
        });

        $this->assertEquals('FooBar', $column->run(['text' => 'FooBar']));
    }

    public function testAdvanced2()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return $model['text'] . 'Bar';
        });

        $this->assertEquals('FooBar', $column->run(['text' => 'Foo']));
    }
}
