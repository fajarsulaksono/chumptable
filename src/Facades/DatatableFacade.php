<?php

namespace Chumptable\Datatable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Chumptable\Datatable\Engines\QueryEngine query($query)
 * @method static \Chumptable\Datatable\Engines\CollectionEngine collection($collection)
 * @method static \Chumptable\Datatable\Table table()
 * @method static bool shouldHandle()
 *
 * @see \Chumptable\Datatable\Datatable
 */
class DatatableFacade extends Facade
{
    /**
     * Get the registered name of the component in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'datatable';
    }
}
