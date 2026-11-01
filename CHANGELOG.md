# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-01

### Added

- DataTables 1.10+/2.x server-side protocol support:
  - `draw`, `start`, `length`, `search[value]`, `search[regex]`
  - `order[i][column]`, `order[i][dir]`
  - `columns[i][data]`, `columns[i][name]`, `columns[i][search][value]`
- Automatic response format detection: modern requests (`draw`/`start`/`length`/`columns`/`order`) receive `draw`, `recordsTotal`, `recordsFiltered`, and `data`.
- `datatable.version` configuration (`1.x` or `2.x`).
- Runtime version selection via `setDatatablesVersion()` on the `Datatable` facade, engines, and `Table`.
- Automatic translation of legacy client options for DataTables 2.x:
  `sAjaxSource`, `bServerSide`, `aoColumns`, `mData`, `aaSorting`,
  `sPaginationType`, `bProcessing`, legacy callbacks, and more.
- DataTables 2.x-aware `Table::setUrl()`, `Table::setOrder()`, and column mapping.
- Test coverage for DataTables 2.x request handling, response format, table options, and rendering.

### Changed

- Server-side per-column searches now work through `columns[i][search][value]`.
- `prepareSearchColumns()` runs before request parsing so default search columns are available.
- Configuration is now merged automatically and published to `config/datatable.php`.
- Package views are loaded from the `chumptable` namespace, with `datatable` kept as a backwards-compatible alias.
- `Datatable::shouldHandle()` accepts the DataTables 2.x `draw` parameter.

### Fixed

- Numeric table columns are mapped using zero-based indexes instead of one-based indexes.
- Table script rendering uses the configured/overridden `script_view` instead of reading the wrong config key.

## [1.0.0] - 2025-09-07

### Added

- Initial maintained fork of Chumper/Datatable.
- PHP 8.0+ and Laravel 9+ support.
- Query and Collection engines, column helpers, facades, and Blade views.
