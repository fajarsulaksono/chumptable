<?php

namespace Chumptable\Datatable\Tests;

use Chumptable\Datatable\DatatableServiceProvider;
use Chumptable\Datatable\Facades\DatatableFacade;
use Orchestra\Testbench\TestCase as BaseTestCase;

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
            'Datatable' => DatatableFacade::class,
        ];
    }
}
