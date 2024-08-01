# Create and manage menus and menu items

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require datlechin/filament-menu-builder
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-menu-builder-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentMenuBuilder = new Datlechin\FilamentMenuBuilder();
echo $filamentMenuBuilder->echoPhrase('Hello, Datlechin!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ngo Quoc Dat](https://github.com/datlechin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
