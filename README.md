Chumptable
=========

# Chumptable (Laravel DataTable Fork)

Chumptable is a Laravel package that simplifies building server-side and client-side DataTables (http://datatables.net/).

Overview
--------

This project is a maintained fork of Chumper/Datatable, updated to support PHP 8.0+ and recent Laravel versions.

Requirements
------------

- PHP 8.0+
- Laravel 9, 10, 11, 12 or 13 (see `composer.json`)

Installation
------------

Install via Composer:

```bash
composer require fajarsulaksono/chumptable
```

Laravel package discovery will register the service provider and alias automatically. To publish the package configuration (optional):

```bash
php artisan vendor:publish --provider="Chumptable\\Datatable\\DatatableServiceProvider" --tag=config
```

This publishes `config/datatable.php`.

Main features
-------------

- Engines for different data sources: Query (Eloquent/QueryBuilder) and Collection.
- Column helpers: `TextColumn`, `DateColumn`, `FunctionColumn`.
- View templates for table HTML and DataTables script; templates are overrideable via configuration.
- Facade `Datatable` for convenient usage in controllers and routes.

Available components
--------------------

- Engines: `QueryEngine`, `CollectionEngine`, `BaseEngine`.
- Columns: `TextColumn`, `DateColumn`, `FunctionColumn`.
- Service provider: `DatatableServiceProvider` (binds `datatable` into the container).

Quick usage examples
--------------------

1) From a query (server-side / Eloquent / QueryBuilder):

```php
use Datatable; // facade alias

$response = Datatable::of($query)
	->showColumns('id', 'name', 'email')
	->searchColumns('name', 'email')
	->make(); // returns a JSON response for DataTables
```

2) From a collection (client-side):

```php
use Datatable;

$response = Datatable::collection($collection)
	->showColumns('id', 'title')
	->make();
```

3) Generate table HTML + script using the `table()` helper:

```php
use Datatable;

$tableHtml = Datatable::table()
	->addColumn('Name', 'Email')
	->setUrl(route('my.datatable.route'))
	->render();

// then place $tableHtml into your view
```

DataTables 2.x support
----------------------

Both the legacy DataTables 1.x protocol and the DataTables 1.10+/2.x protocol are supported. Server-side responses are auto-detected from the request (`draw`, `start`, `length`, `search[...]`, `order[...]`, `columns[...]`), so DataTables 2.x clients receive the modern response format automatically.

Select the protocol for generated table markup and client options in `config/datatable.php`:

```php
return [
    // "1.x" keeps the legacy protocol (sEcho, iDisplayStart, aaData, ...)
    // "2.x" uses draw/start/length/recordsTotal/recordsFiltered/data and
    // modern client option names (ajax, serverSide, columns, order, ...)
    'version' => '2.x',

    // ...
];
```

You can also select the version at runtime:

```php
use Datatable;

$response = Datatable::of($query)
    ->setDatatablesVersion('2.x')
    ->showColumns('id', 'name', 'email')
    ->searchColumns('name', 'email')
    ->make();

// Table HTML + script
$html = Datatable::setDatatablesVersion('2.x')
    ->table()
    ->addColumn('Name', 'Email')
    ->setUrl(route('my.datatable.route'))
    ->render();
```

When `version` is `2.x`, legacy option names passed to `Table::setOptions()`,
`Table::setUrl()`, `Table::setOrder()`, or published configuration (for example
`sAjaxSource`, `bServerSide`, `aoColumns`, `mData`, `aaSorting`,
`sPaginationType`, and `bProcessing`) are translated automatically to their
DataTables 2.x equivalents.

Configuration & views
---------------------

Default configuration and bundled views can be published and customized. The package configuration publishes to `config/datatable.php`. View templates are provided by the package and can be overridden in your application's view namespace.

Contributing & testing
-----------------------

Unit tests use `orchestra/testbench` and `phpunit` (see `composer.json`). To run tests locally:

```bash
composer install --dev
./vendor/bin/phpunit
```

License
-------

This package is licensed under the MIT license. See `composer.json` for details.

