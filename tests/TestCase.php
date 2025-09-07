<?php

namespace Chumptable\Datatable\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Chumptable\Datatable\DatatableServiceProvider;

abstract class TestCase extends BaseTestCase
{

    protected function getPackageProviders($app)
    {
        return [
            DatatableServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Datatable' => \Chumptable\Datatable\Facades\DatatableFacade::class,
        ];
    }
}
