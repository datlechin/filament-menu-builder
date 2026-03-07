# Filament Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)

![Filament Menu Builder](https://github.com/datlechin/filament-menu-builder/raw/main/art/menu-builder.jpg)

A [Filament](https://filamentphp.com) plugin for creating and managing menus with drag-and-drop ordering, nested items, custom links, and dynamic menu panels.

## Requirements

- PHP 8.3+
- Filament 5.0+
- Laravel 12+

## Installation

Install the package via Composer:

```bash
composer require datlechin/filament-menu-builder
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config"
```

Or use the install command:

```bash
php artisan filament-menu-builder:install
```

## Usage

Register the plugin in your Filament panel provider:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentMenuBuilderPlugin::make(),
        ]);
}
```

### Defining Locations

Locations define where menus can be displayed in your application (e.g., header, footer, sidebar):

```php
FilamentMenuBuilderPlugin::make()
    ->addLocations([
        'header' => 'Header',
        'footer' => 'Footer',
    ])
```

### Custom Menu Panels

Menu panels are sources for adding items to menus. You can create panels from Eloquent models or static items.

#### Model Panel

To add a panel from an Eloquent model, implement the `MenuPanelable` interface on your model:

```php
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;

class Page extends Model implements MenuPanelable
{
    public function getMenuPanelTitle(): string
    {
        return $this->title;
    }

    public function getMenuPanelUrl(): string
    {
        return route('pages.show', $this);
    }

    public function getMenuPanelName(): string
    {
        return 'Pages';
    }
}
```

Then register it with the plugin:

```php
use Datlechin\FilamentMenuBuilder\MenuPanel\ModelMenuPanel;

FilamentMenuBuilderPlugin::make()
    ->addMenuPanels([
        ModelMenuPanel::make()
            ->model(Page::class),
    ])
```

#### Static Panel

You can also add static items:

```php
use Datlechin\FilamentMenuBuilder\MenuPanel\StaticMenuPanel;

FilamentMenuBuilderPlugin::make()
    ->addMenuPanels([
        StaticMenuPanel::make()
            ->name('pages')
            ->add('Home', '/')
            ->add('About', '/about')
            ->add('Contact', '/contact'),
    ])
```

### Custom Link & Custom Text Panels

The custom link panel is shown by default. You can toggle it and also enable the custom text panel (for non-link items like headings):

```php
FilamentMenuBuilderPlugin::make()
    ->showCustomLinkPanel(true)
    ->showCustomTextPanel(true)
```

### Custom Fields

You can add extra fields to the menu form or the menu item edit form:

```php
use Filament\Forms\Components\TextInput;

FilamentMenuBuilderPlugin::make()
    ->addMenuFields([
        TextInput::make('description'),
    ])
    ->addMenuItemFields([
        TextInput::make('badge'),
    ])
```

### Customizing Navigation

```php
FilamentMenuBuilderPlugin::make()
    ->navigationLabel('Menus')
    ->navigationGroup('Content')
    ->navigationIcon('heroicon-o-bars-3')
    ->navigationSort(3)
    ->navigationCountBadge(true)
```

### Indent / Unindent

Nested menu items are supported via indent/unindent actions. This is enabled by default:

```php
FilamentMenuBuilderPlugin::make()
    ->enableIndentActions(true)
```

### Custom Models

You can replace the default models with your own:

```php
FilamentMenuBuilderPlugin::make()
    ->usingMenuModel(CustomMenu::class)
    ->usingMenuItemModel(CustomMenuItem::class)
    ->usingMenuLocationModel(CustomMenuLocation::class)
```

### Rendering Menus

Retrieve a menu by its location in your views or controllers:

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;

$menu = Menu::location('header');
```

This uses caching under the hood for performance. The cache is automatically busted when menus or menu items are updated.

Loop through menu items:

```blade
@if($menu)
    <nav>
        <ul>
            @foreach($menu->menuItems as $item)
                <li class="{{ $item->classes }} {{ $item->isActive() ? 'active' : '' }}">
                    @if($item->url)
                        <a href="{{ $item->url }}" target="{{ $item->target }}">
                            {{ $item->title }}
                        </a>
                    @else
                        <span>{{ $item->title }}</span>
                    @endif

                    @if($item->children->isNotEmpty())
                        <ul>
                            @foreach($item->children as $child)
                                <li>
                                    <a href="{{ $child->url }}">{{ $child->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
```

#### Active State Detection

Menu items provide methods for checking if they match the current URL:

```php
$item->isActive();                 // exact URL match
$item->isActiveOrHasActiveChild(); // matches self or any descendant
```

### MenuItem Properties

| Property   | Type     | Description                           |
|------------|----------|---------------------------------------|
| `title`    | string   | The display title                     |
| `url`      | ?string  | The URL (null for text-only items)    |
| `target`   | string   | Link target (`_self`, `_blank`, etc.) |
| `icon`     | ?string  | Icon identifier (e.g. `heroicon-o-home`) |
| `classes`  | ?string  | CSS classes for the item              |
| `type`     | string   | Panel name / source type (accessor)   |
| `children` | Collection | Nested child items                  |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
