<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DataTables protocol version
    |--------------------------------------------------------------------------
    |
    | Supported: "1.x", "2.x"
    |
    | "1.x" keeps the legacy server-side protocol (sEcho, iDisplayStart,
    | aaData, ...) and legacy client options.
    |
    | "2.x" enables the DataTables 1.10+/2.x protocol (draw, start, length,
    | recordsTotal, recordsFiltered, data, ...) and translates the legacy
    | client option names below to their modern equivalents.
    |
    */

    'version' => '1.x',

    /*
    |--------------------------------------------------------------------------
    | Table specific configuration options.
    |--------------------------------------------------------------------------
    |
    */

    'table' => [

        /*
        |--------------------------------------------------------------------------
        | Table class
        |--------------------------------------------------------------------------
        |
        | Class(es) added to the table
        | Supported: string
        |
        */

        'class' => 'table table-bordered',

        /*
        |--------------------------------------------------------------------------
        | Table ID
        |--------------------------------------------------------------------------
        |
        | ID given to the table. Used for connecting the table and the Datatables
        | jQuery plugin. If left empty a random ID will be generated.
        | Supported: string
        |
        */

        'id' => '',

        /*
        |--------------------------------------------------------------------------
        | DataTable options
        |--------------------------------------------------------------------------
        |
        | jQuery dataTable plugin options. The array will be json_encoded and
        | passed through to the plugin. See https://datatables.net/usage/options
        | for more information.
        | Supported: array
        |
        */

        'options' => [

            'sPaginationType' => 'full_numbers',

            'bProcessing' => false,

        ],

        /*
        |--------------------------------------------------------------------------
        | DataTable callbacks
        |--------------------------------------------------------------------------
        |
        | jQuery dataTable plugin callbacks. The array will be json_encoded and
        | passed through to the plugin. See https://datatables.net/usage/callbacks
        | for more information.
        | Supported: array
        |
        */

        'callbacks' => [],

        /*
        |--------------------------------------------------------------------------
        | Skip javascript in table template
        |--------------------------------------------------------------------------
        |
        | Determines if the template should echo the javascript
        | Supported: boolean
        |
        */

        'noScript' => false,

        /*
        |--------------------------------------------------------------------------
        | Table view
        |--------------------------------------------------------------------------
        |
        | Template used to render the table
        | Supported: string
        |
        */

        'table_view' => 'chumptable::template',

        /*
        |--------------------------------------------------------------------------
        | Script view
        |--------------------------------------------------------------------------
        |
        | Template used to render the javascript
        | Supported: string
        |
        */

        'script_view' => 'chumptable::javascript',

    ],

    /*
    |--------------------------------------------------------------------------
    | Engine specific configuration options.
    |--------------------------------------------------------------------------
    |
    */

    'engine' => [

        /*
        |--------------------------------------------------------------------------
        | Search for exact words
        |--------------------------------------------------------------------------
        |
        | If the search should be done with exact matching
        | Supported: boolean
        |
        */

        'exactWordSearch' => false,

        /*
        |--------------------------------------------------------------------------
        | Enable to display all records.
        |--------------------------------------------------------------------------
        |
        | Be careful! It may be overloaded with large record.
        | Supported: boolean
        |
        */
        'enableDisplayAll' => false,
        /*
        |--------------------------------------------------------------------------
        | Limit display when iDisplayLength invaild
        |--------------------------------------------------------------------------
        */
        'defaultDisplayLength' => 10,
    ],

];
