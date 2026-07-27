<?php

namespace Tests\Columns;

use Chumptable\Datatable\Columns\FunctionColumn;
use Orchestra\Testbench\TestCase;

class FunctionColumnTest extends TestCase
{
    public function test_simple()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return 'FooBar';
        });

        $this->assertEquals('FooBar', $column->run([]));
    }

    public function test_advanced()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return $model['text'];
        });

        $this->assertEquals('FooBar', $column->run(['text' => 'FooBar']));
    }

    public function test_advanced2()
    {
        $column = new FunctionColumn('foo', function ($model) {
            return $model['text'].'Bar';
        });

        $this->assertEquals('FooBar', $column->run(['text' => 'Foo']));
    }
}
