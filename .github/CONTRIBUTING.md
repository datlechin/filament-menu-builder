# Contributing

Bug fixes, translations and new features are welcome. For a larger change, please open a [discussion](https://github.com/datlechin/filament-menu-builder/discussions) first so we can agree on the approach before you write the code.

## Setup

```shell
git clone https://github.com/datlechin/filament-menu-builder.git
cd filament-menu-builder
composer install
npm install
```

## Tests

Every change should come with tests. You may run the test suite using Composer:

```shell
composer test
```

The suite runs against Filament 5 by default. Since the plugin also supports Filament 4, you may run it against Filament 4 as well:

```shell
composer require "filament/filament:^4.0" --no-update
composer update
composer test
```

## Code Style

The plugin follows the Laravel coding style, enforced by [Pint](https://laravel.com/docs/pint). You may format your changes using:

```shell
composer format
```

## JavaScript

The drag and drop behavior lives in `resources/js/index.js`. After changing it, rebuild the bundle and commit the result in `resources/dist`:

```shell
npm run build
```

## Translations

To add a language, copy `resources/lang/en/menu-builder.php` to a new folder named after the locale and translate every key.

## Pull Requests

- Keep each pull request to one change.
- Update the [README](../README.md) when you change how the plugin is used, and the [upgrade guide](../UPGRADE.md) when you change its public API.
- The plugin follows [Semantic Versioning](https://semver.org). Breaking changes target the next major version.
