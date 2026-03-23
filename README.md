# Filament Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)

![Filament Menu Builder](https://github.com/datlechin/filament-menu-builder/raw/main/art/menu-builder.jpg)

A [Filament](https://filamentphp.com) plugin for building menus with drag-and-drop ordering, nesting, custom links, and dynamic panels.

## Requirements

- PHP 8.3+
- Filament 5.0+
- Laravel 12+

## Upgrading

### From v0.7.x (Filament v3) to v1.x (Filament v5)

1. Update your `composer.json`:

```bash
composer require datlechin/filament-menu-builder:^1.0
```

2. Publish and run the new migration to add the `panel`, `icon`, and `classes` columns:

```bash
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

The upgrade migration checks for existing columns before adding them, so it's safe on fresh installs too.

3. Re-publish the config file if you published it previously:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config" --force
```

## Installation

Install via Composer:

```bash
composer require datlechin/filament-menu-builder
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

Optionally, publish the config file:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config"
```

Or use the install command:

```bash
php artisan filament-menu-builder:install
```

You will need to set up a Filament [custom theme](https://filamentphp.com/docs/5.x/styling/overview#creating-a-custom-theme)

If you don't yet have a custom theme, run the following command:

```bash
php artisan make:filament-theme
```

Next, open up the theme.css file for the custom theme and add the following line:

```css
@import "../../../../vendor/datlechin/filament-menu-builder/resources/css/index.css";
@source "../../../../vendor/datlechin/filament-menu-builder/resources/**/*.blade.php";
```

## Usage

Register the plugin in your panel provider:

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

### Locations

Locations define where menus appear in your application:

```php
FilamentMenuBuilderPlugin::make()
    ->addLocations([
        'header' => 'Header',
        'footer' => 'Footer',
    ])
```

### Menu Panels

Panels provide item sources for menus, either from Eloquent models or static lists.

#### Model Panel

Implement `MenuPanelable` on your model:

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

Then register it:

```php
use Datlechin\FilamentMenuBuilder\MenuPanel\ModelMenuPanel;

FilamentMenuBuilderPlugin::make()
    ->addMenuPanels([
        ModelMenuPanel::make()
            ->model(Page::class),
    ])
```

#### Static Panel

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

`add()` also accepts `target`, `icon`, and `classes`:

```php
StaticMenuPanel::make()
    ->name('social')
    ->add('GitHub', 'https://github.com', target: '_blank', icon: 'heroicon-o-code-bracket')
    ->add('Twitter', 'https://twitter.com', target: '_blank', classes: 'text-blue-500')
```

### Custom Link & Custom Text Panels

The custom link panel is shown by default. The custom text panel (for non-link items like headings) is opt-in:

```php
FilamentMenuBuilderPlugin::make()
    ->showCustomLinkPanel(true)
    ->showCustomTextPanel(true)
```

### Custom Fields

Add extra fields to the menu or menu item forms:

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

Singular methods work too:

```php
FilamentMenuBuilderPlugin::make()
    ->addMenuField(TextInput::make('description'))
    ->addMenuItemField(TextInput::make('badge'))
```

Multiple calls are merged, so fields registered from different service providers won't overwrite each other.

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

Nesting via indent/unindent actions is enabled by default:

```php
FilamentMenuBuilderPlugin::make()
    ->enableIndentActions(true)
```

### Translatable Menus

Built-in multilingual support with no extra packages required. Translatable fields are stored as JSON with locale tabs in the form UI.

#### Setup

1. Enable translatable with your locales:

```php
FilamentMenuBuilderPlugin::make()
    ->translatable(['en', 'nl', 'vi'])
```

2. Publish and run the migration to convert columns from `string` to `json`:

```bash
php artisan vendor:publish --tag="filament-menu-builder-translatable-migrations"
php artisan migrate
```

Existing string data is wrapped in the default locale (`en`). Edit `$defaultLocale` in the published migration to change this.

#### Configuring Translatable Fields

Only `MenuItem.title` is translatable by default:

```php
FilamentMenuBuilderPlugin::make()
    ->translatable(['en', 'nl', 'vi'])
    ->translatableMenuItemFields(['title'])  // default
    ->translatableMenuFields(['name'])       // opt-in: make Menu name translatable too
```

#### Rendering Translated Titles

Use `resolveLocale()` in Blade to display titles in the current locale:

```blade
@foreach($menu->menuItems as $item)
    <a href="{{ $item->url }}">
        {{ $item->resolveLocale($item->title) }}
    </a>
@endforeach
```

`resolveLocale()` returns the translation for `app()->getLocale()`, falls back to the first available translation, or returns the raw string for non-translatable setups.

#### Spatie Translatable Compatibility

The JSON format is compatible with [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable). If you add `HasTranslations` to a custom model, the plugin detects it and defers to Spatie's mutators.

```php
use Spatie\Translatable\HasTranslations;

class CustomMenuItem extends MenuItem
{
    use HasTranslations;

    public array $translatable = ['title'];
}
```

### Custom Models

Replace the default models with your own:

```php
FilamentMenuBuilderPlugin::make()
    ->usingMenuModel(CustomMenu::class)
    ->usingMenuItemModel(CustomMenuItem::class)
    ->usingMenuLocationModel(CustomMenuLocation::class)
```

### Rendering Menus

Retrieve a menu by location. Results are cached and automatically busted on changes:

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;

$menu = Menu::location('header');
```

Render menu items:

```blade
@if($menu)
    <nav>
        <ul>
            @foreach($menu->menuItems as $item)
                <li class="{{ $item->classes }} {{ $item->isActive() ? 'active' : '' }}">
                    @if($item->url)
                        <a href="{{ $item->url }}" target="{{ $item->target }}" @if($item->rel) rel="{{ $item->rel }}" @endif>
                            {{ $item->resolveLocale($item->title) }}
                        </a>
                    @else
                        <span>{{ $item->resolveLocale($item->title) }}</span>
                    @endif

                    @if($item->children->isNotEmpty())
                        <ul>
                            @foreach($item->children as $child)
                                <li>
                                    <a href="{{ $child->url }}">{{ $child->resolveLocale($child->title) }}</a>
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

Check if a menu item matches the current URL:

```php
$item->isActive();                 // exact URL match
$item->isActiveOrHasActiveChild(); // matches self or any descendant
```

### MenuItem Properties

| Property   | Type     | Description                           |
|------------|----------|---------------------------------------|
| `title`    | string\|array | The display title (array when translatable) |
| `url`      | ?string  | The URL (null for text-only items)    |
| `target`   | string   | Link target (`_self`, `_blank`, etc.) |
| `icon`     | ?string  | Icon identifier (e.g. `heroicon-o-home`) |
| `classes`  | ?string  | CSS classes for the item              |
| `rel`      | ?string  | Link rel attribute (e.g. `nofollow noopener`) |
| `type`     | string   | Panel name / source type (accessor)   |
| `children` | Collection | Nested child items                  |

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

MIT License. See [LICENSE.md](LICENSE.md).
