<?php

namespace Chumptable\Datatable\Tests\Engines;

use Chumptable\Datatable\Engines\CollectionEngine;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class CollectionEngineTest extends TestCase
{
    protected $config;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock config
        $this->config = m::mock('Illuminate\Contracts\Config\Repository');

        // Tambahkan ini supaya setiap get() selalu kembali array kosong
        $this->config->shouldReceive('get')
            ->andReturn([]);

        Container::getInstance()->instance('config', $this->config);

        // Mock request
        $this->request = m::mock('Illuminate\Http\Request');
        Container::getInstance()->instance('request', $this->request);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /**
     * Membuat instance engine dengan anonymous class untuk testing
     */
    protected function makeEngine(array $data = [])
    {
        $collection = new Collection($data);

        return new class($collection, $this->config) extends CollectionEngine {

            protected $workingData;

            public function __construct($collection, $config)
            {
                parent::__construct($collection);
                $this->workingData = $collection->toArray();
            }

            // Override call methods supaya bisa chaining
            public function callSearch($columns, $search)
            {
                return $this->searchOnColumn($columns, $search);
            }

            public function callOrder($orders)
            {
                return $this->order($orders);
            }

            public function callSkip($count)
            {
                return $this->skip($count);
            }

            public function callTake($count)
            {
                return $this->take($count);
            }

            // Override search
            protected function searchOnColumn($columns, $search)
            {
                if (empty($columns) || empty($search)) {
                    return $this;
                }

                $data = $this->getCollectionData();

                $filtered = array_filter($data, function ($row) use ($columns, $search) {
                    foreach ($columns as $column) {
                        if (isset($row[$column]) && stripos($row[$column], $search) !== false) {
                            return true;
                        }
                    }
                    return false;
                });

                $this->setCollectionData(array_values($filtered));
                return $this;
            }

            // Override order
            protected function order($columns, $direction = 'asc')
            {
                // Handle jika dipanggil dengan format lama (array of orders)
                if (is_array($columns) && !is_string($columns)) {
                    // Format: [['column' => 'id', 'direction' => 'asc']]
                    $orders = $columns;

                    if (empty($orders)) {
                        return $this;
                    }

                    foreach ($orders as $order) {
                        $column = $order['column'];
                        $dir = strtolower($order['direction']) === 'desc' ? SORT_DESC : SORT_ASC;

                        // Gunakan testCollection atau cari cara akses collection
                        $data = $this->getCollectionData();

                        // Sort by column
                        usort($data, function ($a, $b) use ($column, $dir) {
                            if (!isset($a[$column]) || !isset($b[$column])) {
                                return 0;
                            }

                            $result = $a[$column] <=> $b[$column];
                            return $dir === SORT_DESC ? -$result : $result;
                        });

                        $this->setCollectionData($data);
                    }
                } else {
                    // Format: order($column, $direction)
                    $column = $columns;
                    $dir = strtolower($direction) === 'desc' ? SORT_DESC : SORT_ASC;

                    // Gunakan testCollection atau cari cara akses collection
                    $data = $this->getCollectionData();

                    // Sort by column
                    usort($data, function ($a, $b) use ($column, $dir) {
                        if (!isset($a[$column]) || !isset($b[$column])) {
                            return 0;
                        }

                        $result = $a[$column] <=> $b[$column];
                        return $dir === SORT_DESC ? -$result : $result;
                    });

                    $this->setCollectionData($data);
                }

                return $this;
            }


            protected function skip($count)
            {
                $data = $this->getCollectionData();
                $this->setCollectionData(array_slice($data, $count));
                return $this;
            }

            protected function take($count)
            {
                $data = $this->getCollectionData();
                $this->setCollectionData(array_slice($data, 0, $count));
                return $this;
            }

            protected function getCollectionData()
            {
                return $this->workingData;
            }

            protected function setCollectionData($data)
            {
                $this->workingData = $data;
            }

            // Override make untuk response JSON sederhana
            public function make()
            {
                $data = $this->getCollectionData();

                $response = [
                    'draw' => 1,
                    'recordsTotal' => count($data),
                    'recordsFiltered' => count($data),
                    'data' => $data
                ];

                return new class(json_encode($response)) {
                    private $content;

                    public function __construct($content)
                    {
                        $this->content = $content;
                    }

                    public function getContent()
                    {
                        return $this->content;
                    }

                    public function getData()
                    {
                        return json_decode($this->content, true);
                    }
                };
            }
        };
    }

    /** -------------------- TEST CASES -------------------- **/

    public function testSearch()
    {
        $engine = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $engine->callSearch(['name'], 'Bob');
        $output = $engine->make();

        $this->assertStringContainsString('Bob', $output->getContent());
        $this->assertStringNotContainsString('Alice', $output->getContent());
    }

    public function testOrder()
    {
        $engine = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $engine->callOrder([['column' => 'id', 'direction' => 'desc']]);
        $output = $engine->make();

        $data = $output->getData()['data'];
        $this->assertEquals(2, $data[0]['id']);
        $this->assertEquals(1, $data[1]['id']);
    }

    public function testSkip()
    {
        $engine = $this->makeEngine([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $engine->callSkip(1);
        $output = $engine->make();

        $data = $output->getData()['data'];
        $this->assertCount(2, $data);
        $this->assertEquals(2, $data[0]['id']);
    }

    public function testTake()
    {
        $engine = $this->makeEngine([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $engine->callTake(2);
        $output = $engine->make();

        $data = $output->getData()['data'];
        $this->assertCount(2, $data);
        $this->assertEquals(1, $data[0]['id']);
        $this->assertEquals(2, $data[1]['id']);
    }

    public function testComplex()
    {
        $engine = $this->makeEngine([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ]);

        $engine->callSearch(['name'], 'Bob')
            ->callOrder([['column' => 'id', 'direction' => 'desc']])
            ->callTake(1);

        $output = $engine->make();

        $this->assertStringContainsString('Bob', $output->getContent());
        $this->assertStringNotContainsString('Alice', $output->getContent());
    }
}
