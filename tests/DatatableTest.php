<?php

declare(strict_types=1);

namespace Chumptable\Datatable\Tests;

use Chumptable\Datatable\Datatable;
use Chumptable\Datatable\Facades\DatatableFacade;
use Orchestra\Testbench\TestCase as BaseTestCase;

class DatatableTest extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Chumptable\Datatable\DatatableServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Datatable' => \Chumptable\Datatable\Facades\DatatableFacade::class,
        ];
    }

    /** @test */
    public function it_can_instantiate_the_datatable_class()
    {
        $datatable = new Datatable();
        $this->assertInstanceOf(Datatable::class, $datatable);
    }

    /** @test */
    public function facade_resolves_to_datatable_instance()
    {
        // pakai global namespace untuk Datatable alias
        $instance = \Datatable::getFacadeRoot();
        $this->assertInstanceOf(Datatable::class, $instance);
    }
}
