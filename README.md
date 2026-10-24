# Filament Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)

![Filament Menu Builder](https://github.com/datlechin/filament-menu-builder/raw/main/art/menu-builder.jpg)

Build nested menus in [Filament](https://filamentphp.com) and render them anywhere in your app: links, named routes, Eloquent records and headings, with drag and drop, locations, translations and tenancy.

## Requirements

- PHP 8.3+
- Filament 4 or 5

## Installation

```bash
composer require datlechin/filament-menu-builder

php artisan vendor:publish --tag="filament-menu-builder-config"
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

Set up [translations](#translations) and [tenancy](#tenancy) in the config before you migrate if you need them. The plugin's stylesheet and script are published with Filament's other assets by `php artisan filament:assets`.

Register the plugin in your panel:

```php
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;

$panel->plugins([
    FilamentMenuBuilderPlugin::make()
        ->addLocations([
            'header' => 'Header',
            'footer' => 'Footer',
        ]),
]);
```

Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Item types

Every menu item has a type. Each type adds its own form fields and decides where the item points to. Links and text items are available by default; pass the full list to `itemTypes()` to change it:

```php
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\ModelItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\RouteItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;

FilamentMenuBuilderPlugin::make()
    ->itemTypes([
        LinkItemType::make(),
        TextItemType::make(),
        RouteItemType::make()->routes([
            'home' => 'Home',
            'blog.index' => 'Blog',
            'products.show' => 'Product',
        ]),
        ModelItemType::make(Page::class)->titleAttribute('title'),
        ModelItemType::make(Category::class)->label('Categories'),
    ])
```

| Type | Points to | Notes |
|---|---|---|
| `LinkItemType` | A URL | Absolute URLs, paths, `#anchors`, `mailto:` and `tel:`. Script URLs are rejected. |
| `TextItemType` | Nothing | Headings or group labels. |
| `RouteItemType` | A named route | The URL is generated when the menu is rendered, so it follows route changes and the current domain. Parameters are filled in the form. |
| `ModelItemType` | An Eloquent record | The URL comes from the record when the menu is rendered. Several records can be added at once. |

Every type can be renamed with `->label()` and given an `->icon()`.

### Linking to models

Implement `MenuLinkable` on the model and add the `HasMenuItems` trait, which refreshes cached menus when the record changes:

```php
use Datlechin\FilamentMenuBuilder\Concerns\HasMenuItems;
use Datlechin\FilamentMenuBuilder\Contracts\MenuLinkable;

class Page extends Model implements MenuLinkable
{
    use HasMenuItems;

    public function getMenuTitle(): string
    {
        return $this->title;
    }

    public function getMenuUrl(): string
    {
        return route('pages.show', $this);
    }
}
```

`titleAttribute()` makes the record picker search that column in the database, which you want for large tables. Without it every record is loaded into the picker. Use `modifyQueryUsing()` to limit the records:

```php
ModelItemType::make(Page::class)
    ->titleAttribute('title')
    ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true))
```

Items whose record is deleted, soft deleted, or whose route no longer resolves are hidden on your site and flagged as broken in the panel.

### Custom item types

Extend `ItemType` to add your own. A type defines its form fields and the attributes it stores; where the item points to is stored in the standard `url`, `route` and `linkable` columns:

```php
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;

class ProductCategoryItemType extends ItemType
{
    public static function make(): static
    {
        return new static;
    }

    public function getName(): string
    {
        return 'product_category';
    }

    protected function getDefaultLabel(): string
    {
        return 'Product filter';
    }

    public function getDestinationSchema(): array
    {
        return [
            Select::make('url')
                ->label('Category')
                ->options(fn () => ProductCategory::pluck('name', 'slug')->mapWithKeys(fn ($name, $slug) => ["/products?category={$slug}" => $name]))
                ->required(),
        ];
    }
}
```

Override `getCreateSchema()` and `getItemsToCreate()` to add several items from one form, as `ModelItemType` does.

## Custom fields

Add fields to the menu form or the item form. Item fields are stored in the item's `data` column, or in your own columns if you add them:

```php
use Filament\Forms\Components\TextInput;

FilamentMenuBuilderPlugin::make()
    ->addMenuFields([
        TextInput::make('description'),
    ])
    ->addMenuItemFields([
        TextInput::make('data.badge'),
    ])
```

Pass a closure to vary the fields per item type:

```php
->addMenuItemFields(fn (ItemType $type): array => $type instanceof TextItemType
    ? [Toggle::make('data.mega_menu')]
    : [TextInput::make('data.badge')])
```

## Nesting

Limit how deep items may be nested. Root items have a depth of `0`, so `maxDepth(0)` keeps a menu flat. A closure receives the `$menu` being edited:

```php
FilamentMenuBuilderPlugin::make()
    ->maxDepth(fn (Menu $menu): int => $menu->locations->contains('location', 'footer') ? 0 : 2)
```

Control which items may be nested inside which with `allowNesting()`. The closure receives the `$item` being placed, its new `$parent` (`null` at the root level) and the `$menu`. This keeps headings at the root with links inside them:

```php
FilamentMenuBuilderPlugin::make()
    ->allowNesting(fn (MenuItem $item, ?MenuItem $parent): bool => $parent === null || ($parent->type === 'text' && $item->type !== 'text'))
```

Pass `false` to keep every item at the root. Both rules apply to drag and drop, the item actions and new items. Items can always be reordered under their current parent, so existing menus stay editable after a rule is added.

## Rendering menus

### Blade component

```blade
<x-filament-menu-builder::menu location="header" label="Main" class="main-nav" />
```

It renders an unstyled `<nav>` with nested lists. The current page gets `aria-current="page"`, and every item has `data-depth`, `data-active` and `data-active-trail` attributes to style against. Publish the views to change the markup:

```bash
php artisan vendor:publish --tag="filament-menu-builder-views"
```

### Menu tree

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;

$tree = Menu::location('header')?->tree();

foreach ($tree as $node) {
    $node->title;           // Translated for the current locale
    $node->url;             // null for text items
    $node->target;          // '_blank' or null
    $node->rel;
    $node->icon;
    $node->classes;
    $node->type;            // 'link', 'text', 'route', 'model' or your own
    $node->data;            // Custom field values
    $node->depth;
    $node->children;
    $node->isActive();      // This page
    $node->isActiveTrail(); // This page, a page below it, or a descendant of it
}
```

Only visible items with a working destination are included. The tree is cached and refreshed when the menu, its items, its locations or linked records change.

Filter it, for example to hide items from guests:

```php
use Datlechin\FilamentMenuBuilder\Support\MenuNode;

$tree = Menu::location('header')?->tree()->filter(fn (MenuNode $node): bool => auth()->check() || ! ($node->data['members_only'] ?? false));
```

`activeTrail()` returns the nodes from the root to the current page, for breadcrumbs:

```php
@foreach (Menu::location('header')?->tree()->activeTrail() ?? [] as $node)
    <a href="{{ $node->url }}">{{ $node->title }}</a>
@endforeach
```

### Inertia and JSON

The tree serializes to plain arrays, including `is_active` and `is_active_trail` for the current request:

```php
Inertia::share('menu', fn () => Menu::location('header')?->tree());
```

```json
[
    {
        "id": 1,
        "type": "route",
        "title": "Home",
        "url": "https://example.com",
        "target": null,
        "rel": null,
        "icon": null,
        "classes": null,
        "data": [],
        "depth": 0,
        "children": [],
        "is_active": true,
        "is_active_trail": true
    }
]
```

## Translations

List your locales and the attributes to translate in the config, before running the migrations:

```php
'translations' => [
    'locales' => ['en', 'vi'],
    'attributes' => [
        'menu' => ['name'],
        'menu_item' => ['title', 'url'],
    ],
],
```

The fields get a tab per locale. Only the first locale is required; the others fall back to it. Values are stored as JSON in the same format as [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable), and a custom model using its `HasTranslations` trait works too.

To translate an existing install, publish and run the migration that converts the columns to JSON. Existing values are kept under `app.locale`:

```bash
php artisan vendor:publish --tag="filament-menu-builder-translatable-migrations"
php artisan migrate
```

## Tenancy

Menus can belong to the tenant of a panel that uses Filament tenancy. Set the tenant model in the config before running the migrations, which then add the foreign key:

```php
'tenant_model' => App\Models\Team::class,
```

The resource only lists the menus of the current tenant, and each tenant assigns its own menus to locations. Pass the tenant when rendering:

```php
Menu::location('header', $team)?->tree();
```

```blade
<x-filament-menu-builder::menu location="header" :tenant="$team" />
```

## Authorization

The resource follows your `Menu` policy. Users who can't `update` a menu see its items but can't change them.

## Customizing

```php
FilamentMenuBuilderPlugin::make()
    ->navigationLabel('Menus')
    ->navigationGroup('Content')
    ->navigationIcon('heroicon-o-bars-3')
    ->navigationSort(3)
    ->usingResource(MenuResource::class)
```

Swap the models, table names or cache store in `config/filament-menu-builder.php`. Custom models must extend the package models.

## Testing

Factories are included for your own tests:

```php
$menu = Menu::factory()->create();
MenuItem::factory()->for($menu)->route('home')->create();
MenuItem::factory()->for($menu)->linkedTo($page)->create();
```

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## License

MIT License. See [LICENSE.md](LICENSE.md).
