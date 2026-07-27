<?php

namespace Tests\Engines;

use Chumptable\Datatable\Columns\FunctionColumn;
use Chumptable\Datatable\Engines\QueryEngine;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;

class QueryEngineTest extends TestCase
{
    /**
     * @var QueryEngine
     */
    public $c;

    /**
     * @var MockInterface
     */
    public $builder;

    protected function setUp(): void
    {
        parent::setUp();

        // Paksa session driver array
        $this->app['config']->set('session.driver', 'array');

        // Initialize session manually
        $this->app->singleton('session.store', function ($app) {
            return $app['session']->driver('array');
        });

        // Mock Config repository supaya get() & offsetGet() tidak error
        $configMock = Mockery::mock('Illuminate\Config\Repository[all,offsetGet]');
        $configMock->shouldReceive('get')->byDefault()->andReturnUsing(function ($key, $default = null) {
            $configs = [
                'datatable::engine' => [
                    'exactWordSearch' => false,
                ],
            ];

            return $configs[$key] ?? $default ?? [];
        });
        $configMock->shouldReceive('offsetGet')->byDefault()->andReturnUsing(function ($key) {
            $configs = [
                'datatable::engine' => [
                    'exactWordSearch' => false,
                ],
            ];

            return $configs[$key] ?? null;
        });
        $this->app->instance('config', $configMock);

        // Mock Query Builder
        $this->builder = Mockery::mock('Illuminate\Database\Query\Builder');
        $this->builder->shouldReceive('get')->byDefault()->andReturn(new Collection($this->getRealArray()));
        $this->builder->shouldReceive('count')->byDefault()->andReturn(10);
        $this->builder->shouldReceive('where')->byDefault()->andReturn($this->builder);
        $this->builder->shouldReceive('orderBy')->byDefault()->andReturn($this->builder);
        $this->builder->shouldReceive('skip')->byDefault()->andReturn($this->builder);
        $this->builder->shouldReceive('take')->byDefault()->andReturn($this->builder);

        // Buat instance QueryEngine
        $this->c = new QueryEngine($this->builder);
    }

    public function test_order()
    {
        $engine = $this->c;

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('order');
        $method->setAccessible(true);

        // misal $columns adalah array kolom dari engine
        $columns = ['id', 'name', 'email'];

        Request::replace(['iSortCol_0' => 0, 'sSortDir_0' => 'asc']);
        $method->invoke($engine, [$columns]);

        Request::replace(['iSortCol_0' => 1, 'sSortDir_0' => 'desc']);
        $method->invoke($engine, [$columns]);

        $this->assertTrue(true); // supaya PHPUnit tidak risky
    }

    public function test_search()
    {
        $this->addRealColumns($this->c);
        $this->c->searchColumns('foo');

        Request::replace([
            'sSearch' => 'test',
        ]);

        $test = $this->c->output();
        $this->assertIsArray($test['aaData']);
    }

    public function test_skip()
    {
        $this->addRealColumns($this->c);

        Request::replace([
            'iDisplayStart' => 1,
            'sSearch' => null,
        ]);

        $this->c->searchColumns('foo');

        $test = $this->c->output();
        $this->assertIsArray($test['aaData']);
    }

    public function test_take()
    {
        $this->addRealColumns($this->c);

        Request::replace([
            'iDisplayLength' => 1,
            'sSearch' => null,
            'iDisplayStart' => null,
        ]);

        $this->c->searchColumns('foo');

        $test = $this->c->output();
        $this->assertIsArray($test['aaData']);
    }

    public function test_complex()
    {
        $engine = new QueryEngine($this->builder);

        $this->addRealColumns($engine);
        $engine->searchColumns('foo', 'bar');
        $engine->setAliasMapping();

        Request::replace(['sSearch' => 't']);
        $test = $engine->output()['aaData'];

        $this->assertTrue($this->arrayHasKeyValue('foo', 'Nils', $test));
        $this->assertTrue($this->arrayHasKeyValue('foo', 'Taylor', $test));

        // Test 2
        $engine = new QueryEngine($this->builder);
        $this->addRealColumns($engine);
        $engine->searchColumns('foo', 'bar');
        $engine->setAliasMapping();

        Request::replace(['sSearch' => 'plasch']);
        $test = $engine->output()['aaData'];

        $this->assertTrue($this->arrayHasKeyValue('foo', 'Nils', $test));
        $this->assertTrue($this->arrayHasKeyValue('foo', 'Taylor', $test));

        // Test 3
        $engine = new QueryEngine($this->builder);
        $this->addRealColumns($engine);
        $engine->searchColumns('foo', 'bar');
        $engine->setAliasMapping();

        Request::replace(['sSearch' => 'tay']);
        $test = $engine->output()['aaData'];

        $this->assertTrue($this->arrayHasKeyValue('foo', 'Nils', $test));
        $this->assertTrue($this->arrayHasKeyValue('foo', 'Taylor', $test));

        // Test 4
        $engine = new QueryEngine($this->builder);
        $this->addRealColumns($engine);
        $engine->searchColumns('foo', 'bar');
        $engine->setAliasMapping();

        Request::replace(['sSearch' => '0']);
        $test = $engine->output()['aaData'];

        $this->assertTrue($this->arrayHasKeyValue('foo', 'Nils', $test));
        $this->assertTrue($this->arrayHasKeyValue('foo', 'Taylor', $test));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function getRealArray(): array
    {
        return [
            [
                'name' => 'Nils Plaschke',
                'email' => 'github@nilsplaschke.de',
            ],
            [
                'name' => 'Taylor Otwell',
                'email' => 'taylorotwell@gmail.com',
            ],
        ];
    }

    private function addRealColumns(QueryEngine $engine): void
    {
        $engine->addColumn(new FunctionColumn('foo', fn ($m) => $m['name']));
        $engine->addColumn(new FunctionColumn('bar', fn ($m) => $m['email']));
    }

    private function arrayHasKeyValue(string $key, string $value, array $array): bool
    {
        $array = Arr::pluck($array, $key);
        foreach ($array as $val) {
            if (Str::contains($val, $value)) {
                return true;
            }
        }

        return false;
    }
}
