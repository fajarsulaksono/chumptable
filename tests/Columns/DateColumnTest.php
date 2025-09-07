<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Tests\Columns;

use Carbon\Carbon;
use Chumptable\Datatable\Columns\DateColumn;
use Orchestra\Testbench\TestCase;

class DateColumnTest extends TestCase
{
    /** @test */
    public function it_returns_date_string_from_carbon_instance()
    {
        $col = new DateColumn('created_at', DateColumn::DATE);

        $model = ['created_at' => Carbon::create(2023, 1, 15, 10, 30, 45)];

        $this->assertEquals('2023-01-15', $col->run($model));
    }

    /** @test */
    public function it_returns_time_string_from_carbon_instance()
    {
        $col = new DateColumn('created_at', DateColumn::TIME);

        $model = ['created_at' => Carbon::create(2023, 1, 15, 10, 30, 45)];

        $this->assertEquals('10:30:45', $col->run($model));
    }

    /** @test */
    public function it_returns_datetime_string_from_carbon_instance()
    {
        $col = new DateColumn('created_at', DateColumn::DATE_TIME);

        $model = ['created_at' => Carbon::create(2023, 1, 15, 10, 30, 45)];

        $this->assertEquals('2023-01-15 10:30:45', $col->run($model));
    }

    /** @test */
    public function it_returns_custom_format()
    {
        $col = new DateColumn('created_at', DateColumn::CUSTOM, 'd/m/Y');

        $model = ['created_at' => Carbon::create(2023, 1, 15, 10, 30, 45)];

        $this->assertEquals('15/01/2023', $col->run($model));
    }

    /** @test */
    public function it_returns_raw_value_if_string_given()
    {
        $col = new DateColumn('created_at', DateColumn::DATE);

        $model = ['created_at' => '2023-01-15 10:30:45'];

        $this->assertEquals('2023-01-15 10:30:45', $col->run($model));
    }
}
