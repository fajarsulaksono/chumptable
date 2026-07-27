Chumptable
=========

# ChumpTable (Laravel Datatable Fork)

Chumptable adalah paket Laravel untuk membantu pembuatan server-side dan client-side DataTables (http://datatables.net/).

Ringkasan
---------

Paket ini merupakan fork yang tetap dipelihara dari Chumper/Datatable, diperbarui untuk mendukung PHP >= 8.0 dan Laravel 9 ke atas.

Persyaratan
-----------

- PHP 8.0+
- Laravel 9, 10, 11, 12 atau 13 (lihat `composer.json`)

Instalasi
---------

Jalankan:

```bash
composer require fajarsulaksono/chumptable
```

Package discovery Laravel akan mendaftarkan service provider dan alias secara otomatis. Untuk mempublikasikan konfigurasi (opsional):

```bash
php artisan vendor:publish --provider="Chumptable\Datatable\DatatableServiceProvider" --tag=config
```

Fitur utama
-----------

- Engine untuk berbagai sumber data: Query (Eloquent/QueryBuilder) dan Collection.
- Column helpers: `TextColumn`, `DateColumn`, `FunctionColumn`.
- View template untuk tabel dan skrip DataTables, dapat di-override melalui konfigurasi.
- Facade `Datatable` untuk API yang nyaman di controller/route.

Komponen yang tersedia
----------------------

- Engines: `QueryEngine`, `CollectionEngine`, `BaseEngine`.
- Columns: `TextColumn`, `DateColumn`, `FunctionColumn`.
- Service provider: `DatatableServiceProvider` (mendaftarkan binding `datatable`).

Contoh penggunaan singkat
-------------------------

1) Dari query (server-side / Eloquent / QueryBuilder):

```php
use Datatable; // alias Facade

$response = Datatable::of($query)
	->showColumns('id', 'name', 'email')
	->searchColumns('name', 'email')
	->make(); // mengembalikan JSON Response untuk DataTables
```

2) Dari collection (client-side):

```php
use Datatable;

$response = Datatable::collection($collection)
	->showColumns('id', 'title')
	->make();
```

3) Membuat table HTML + script menggunakan helper `table()`:

```php
use Datatable;

$tableHtml = Datatable::table()
	->addColumn('Name', 'Email')
	->setUrl(route('my.datatable.route'))
	->render();

// lalu tampung $tableHtml di view
```

Konfigurasi & views
-------------------

Konfigurasi default dan view bawaan dapat dipublikasikan dan disesuaikan. Lokasi konfigurasi bawaan berada pada paket dan dapat dipublish ke `config/chumptable.php`. Template view paket tersedia di namespace view paket.

Contributing & Testing
-----------------------

Tes unit menggunakan `orchestra/testbench` dan `phpunit` (lihat `composer.json`). Jika ingin menjalankan tes lokal:

```bash
composer install --dev
./vendor/bin/phpunit
```

Lisensi
------

Paket ini berlisensi MIT. Lihat `composer.json` untuk detail.

