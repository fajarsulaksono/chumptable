<?php

namespace Tests\Columns;

use Chumptable\Datatable\Columns\TextColumn;
use Orchestra\Testbench\TestCase;

class TextColumnTest extends TestCase
{
    public function testWorking()
    {
        $column = new TextColumn('foo', 'FooBar');

        $this->assertEquals('FooBar', $column->run([]));
    }
}
