<?php

namespace Chumptable\Datatable\Tests;

use Chumptable\Datatable\Datatable;
use Chumptable\Datatable\Engines\CollectionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DataTables2Test extends TestCase
{
    /**
     * Bind a request containing DataTables 1.10+/2.x server-side parameters.
     */
    protected function withRequest(array $parameters): void
    {
        $this->app->instance('request', Request::create('/datatable', 'GET', $parameters));
    }

    protected function makeEngine(array $rows): CollectionEngine
    {
        $engine = new CollectionEngine(new Collection($rows));
        $engine->showColumns('id', 'name');
        $engine->searchColumns('name');
        $engine->setAliasMapping(true);
        $engine->setDatatablesVersion('2.x');

        return $engine;
    }

    public function test_it_handles_datatables_2_request_parameters(): void
    {
        $this->withRequest([
            'draw' => 3,
            'start' => 1,
            'length' => 1,
            'search' => ['value' => '', 'regex' => false],
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]],
            ],
        ]);

        $output = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ])->toArray();

        $this->assertSame(3, $output['draw']);
        $this->assertSame(3, $output['recordsTotal']);
        $this->assertSame(3, $output['recordsFiltered']);
        $this->assertSame([['id' => 2, 'name' => 'Bob']], $output['data']);
    }

    public function test_it_handles_datatables_2_per_column_search(): void
    {
        $this->withRequest([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [],
            'columns' => [
                ['data' => 'id', 'name' => 'id', 'searchable' => true, 'search' => ['value' => '', 'regex' => false]],
                ['data' => 'name', 'name' => 'name', 'searchable' => true, 'search' => ['value' => 'Bob', 'regex' => false]],
            ],
        ]);

        $output = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ])->toArray();

        $this->assertSame(2, $output['recordsTotal']);
        $this->assertSame(1, $output['recordsFiltered']);
        $this->assertSame([['id' => 2, 'name' => 'Bob']], $output['data']);
    }

    public function test_it_handles_datatables_2_global_search(): void
    {
        $this->withRequest([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'char', 'regex' => false],
            'order' => [],
            'columns' => [],
        ]);

        $output = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ])->toArray();

        $this->assertSame(1, $output['recordsFiltered']);
        $this->assertSame([['id' => 3, 'name' => 'Charlie']], $output['data']);
    }

    public function test_it_auto_detects_the_modern_response_format(): void
    {
        $this->withRequest([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => false],
            'order' => [],
            'columns' => [],
        ]);

        $engine = new CollectionEngine(new Collection([
            ['id' => 1, 'name' => 'Alice'],
        ]));
        $engine->showColumns('id', 'name');
        $engine->setAliasMapping(true);

        $output = $engine->toArray();

        $this->assertArrayHasKey('data', $output);
        $this->assertArrayNotHasKey('aaData', $output);
        $this->assertSame(1, $output['draw']);
    }

    public function test_it_uses_the_modern_format_when_version_2_is_configured(): void
    {
        config(['datatable.version' => '2.x']);

        $engine = new CollectionEngine(new Collection([
            ['id' => 1, 'name' => 'Alice'],
        ]));
        $engine->showColumns('id', 'name');

        $output = $engine->toArray();

        $this->assertArrayHasKey('data', $output);
        $this->assertArrayNotHasKey('aaData', $output);
    }

    public function test_it_keeps_legacy_datatables_1_protocol_working(): void
    {
        $this->withRequest([
            'sEcho' => 2,
            'iDisplayStart' => 0,
            'iDisplayLength' => 10,
            'sSearch' => '',
            'iSortCol_0' => 0,
            'sSortDir_0' => 'asc',
            'iSortingCols' => 1,
        ]);

        $engine = new CollectionEngine(new Collection([
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 1, 'name' => 'Alice'],
        ]));
        $engine->showColumns('id', 'name');
        $engine->setDatatablesVersion('1.x');

        $output = $engine->toArray();

        $this->assertArrayHasKey('aaData', $output);
        $this->assertSame(2, $output['sEcho']);
        $this->assertSame([[1, 'Alice'], [2, 'Bob']], $output['aaData']);
    }

    public function test_it_builds_datatables_2_table_options(): void
    {
        $table = (new Datatable)->setDatatablesVersion('2.x')->table();
        $table->addColumn(['name' => 'Name', 'email' => 'Email'])
            ->setUrl('/datatable/data')
            ->setOrder([0 => 'desc']);

        $table->script();
        $options = $table->getOptions();

        $this->assertSame('/datatable/data', $options['ajax']);
        $this->assertTrue($options['serverSide']);
        $this->assertSame([[0, 'desc']], $options['order']);
        $this->assertSame(['data' => 'name'], $options['columns'][0]);
        $this->assertSame(['data' => 'email'], $options['columns'][1]);
        $this->assertArrayNotHasKey('sAjaxSource', $options);
        $this->assertArrayNotHasKey('aoColumns', $options);
    }

    public function test_it_maps_mixed_numeric_and_alias_columns_with_zero_based_indexes(): void
    {
        $table = (new Datatable)->setDatatablesVersion('2.x')->table();
        $table->addColumn('Name', ['email' => 'Email'])->setAliasMapping();
        $table->script();

        $columns = $table->getOptions()['columns'];

        $this->assertSame(['data' => 0], $columns[0]);
        $this->assertSame(['data' => 'email'], $columns[1]);
    }

    public function test_it_renders_datatables_2_table_markup(): void
    {
        $html = (new Datatable)->setDatatablesVersion('2.x')->table()
            ->addColumn('Name', 'Email')
            ->setUrl('/datatable/data')
            ->render();

        $this->assertStringContainsString("$('#", $html);
        $this->assertStringContainsString('.DataTable({', $html);
        $this->assertStringContainsString('"ajax"', $html);
        $this->assertStringContainsString('"serverSide"', $html);
        $this->assertStringContainsString('"pagingType"', $html);
        $this->assertStringNotContainsString('"sAjaxSource"', $html);
    }

    public function test_it_translates_legacy_table_options_for_datatables_2(): void
    {
        config([
            'datatable.version' => '2.x',
            'datatable.table.options' => [
                'sPaginationType' => 'simple',
                'bProcessing' => true,
                'bServerSide' => true,
                'sAjaxSource' => '/legacy',
                'oSearch' => ['sSearch' => 'foo', 'bRegex' => false],
            ],
        ]);

        $options = (new Datatable)->table()->getOptions();

        $this->assertSame('simple', $options['pagingType']);
        $this->assertTrue($options['processing']);
        $this->assertTrue($options['serverSide']);
        $this->assertSame('/legacy', $options['ajax']);
        $this->assertSame(['value' => 'foo', 'regex' => false], $options['search']);
    }

    public function test_it_keeps_legacy_table_options_for_datatables_1(): void
    {
        config(['datatable.version' => '1.x']);

        $table = (new Datatable)->table();
        $table->setUrl('/legacy')->setOrder([0 => 'asc']);
        $options = $table->getOptions();

        $this->assertSame('/legacy', $options['sAjaxSource']);
        $this->assertTrue($options['bServerSide']);
        $this->assertSame('[[ 0, "asc" ]]', $table->getCallbacks()['aaSorting']);
    }

    public function test_should_handle_accepts_the_datatables_2_draw_parameter(): void
    {
        $this->withRequest(['draw' => 1]);

        $this->assertTrue((new Datatable)->shouldHandle());
    }
}
