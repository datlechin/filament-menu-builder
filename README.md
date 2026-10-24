# Filament Menu Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datlechin/filament-menu-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datlechin/filament-menu-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datlechin/filament-menu-builder.svg?style=flat-square)](https://packagist.org/packages/datlechin/filament-menu-builder)

<img class="filament-hidden" src="https://github.com/datlechin/filament-menu-builder/raw/main/art/menu-builder.jpg" alt="Filament Menu Builder">

- [Introduction](#introduction)
- [Installation](#installation)
    - [Configuration](#configuration)
    - [Upgrading](#upgrading)
- [Locations](#locations)
- [Item Types](#item-types)
    - [Linking To Routes](#linking-to-routes)
    - [Linking To Models](#linking-to-models)
    - [Restricting Item Types](#restricting-item-types)
    - [Customizing The Item Form](#customizing-the-item-form)
    - [Custom Item Types](#custom-item-types)
- [Custom Fields](#custom-fields)
- [Nesting](#nesting)
- [Rendering Menus](#rendering-menus)
    - [Blade Component](#blade-component)
    - [Menu Trees](#menu-trees)
    - [Active Items](#active-items)
    - [Inertia And APIs](#inertia-and-apis)
    - [Caching](#caching)
- [Translations](#translations)
- [Tenancy](#tenancy)
- [Authorization](#authorization)
- [Customizing The Resource](#customizing-the-resource)
- [Testing](#testing)

## Introduction

Filament Menu Builder lets you manage your site's menus from a Filament panel and render them anywhere in your Laravel application. Menus may contain links, named routes, Eloquent records and headings, nested as deep as you allow, and can be assigned to locations such as a header or a footer.

## Installation

You may install the plugin using Composer. It supports Filament 4 and 5 on PHP 8.3 or newer:

```shell
composer require datlechin/filament-menu-builder
```

Next, publish and run the migrations:

```shell
php artisan vendor:publish --tag="filament-menu-builder-migrations"
php artisan migrate
```

Finally, register the plugin in your panel provider:

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

The plugin's stylesheet and script are published with Filament's other assets when you run `php artisan filament:assets`. A custom theme is not required.

### Configuration

The configuration file holds the models, table names, translations, tenancy and cache settings. If you plan to use [translations](#translations) or [tenancy](#tenancy), publish it before running the migrations, since both change the database tables:

```shell
php artisan vendor:publish --tag="filament-menu-builder-config"
```

### Upgrading

When upgrading to a new major version, please review the [upgrade guide](UPGRADE.md).

## Locations

Locations are the places in your application where a menu appears. You may register them on the plugin:

```php
FilamentMenuBuilderPlugin::make()
    ->addLocations([
        'header' => 'Header',
        'footer' => 'Footer',
    ])
```

Each location shows one menu, which you choose in the menu's form. The plugin is configured before the request's locale is set, so pass a closure when a label should be translated:

```php
FilamentMenuBuilderPlugin::make()
    ->addLocation('header', fn () => __('Header'))
```

## Item Types

Every menu item has a type, which defines the item's form fields and where the item points to. Links and text items are offered by default. To change the list, pass every type you would like to offer to the `itemTypes` method:

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
        ]),
        ModelItemType::make(Page::class)->titleAttribute('title'),
    ])
```

| Type | Points To |
|---|---|
| `LinkItemType` | A URL, path, `#anchor`, `mailto:` or `tel:` link. URLs that run scripts are rejected. |
| `TextItemType` | Nothing. Use it for headings and group labels. |
| `RouteItemType` | A named route of your application. |
| `ModelItemType` | An Eloquent record, such as a page or a category. |

Editors pick a type from the "Add item" menu above the menu tree:

<img src="https://github.com/datlechin/filament-menu-builder/raw/main/art/add-item.png" alt="The Add item menu listing the item types" width="800">

You may change the label and icon of any type. As with locations, pass a closure when the label should be translated:

```php
LinkItemType::make()
    ->label(fn () => __('External link'))
    ->icon('heroicon-o-arrow-top-right-on-square')
```

Items whose route or record no longer exists are hidden from your site and marked as broken in the panel.

### Linking To Routes

Route items store the route name and its parameters, and generate the URL when the menu is rendered. The URL follows changes to your routes and uses the domain of the current request. Pass the routes editors may choose from, keyed by route name:

```php
RouteItemType::make()->routes([
    'home' => 'Home',
    'products.index' => 'Shop',
    'products.show' => 'Product',
])
```

When a route has parameters, such as `{product}`, the form asks for their values.

### Linking To Models

To link to a model, implement the `MenuLinkable` interface on it. The `HasMenuItems` trait refreshes cached menus whenever the record is saved or deleted:

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

Editors may add several records at once. The `titleAttribute` method makes the record picker search that column in the database, which you should use for large tables. Without it, every record is loaded into the picker. You may limit the records with the `modifyQueryUsing` method:

```php
ModelItemType::make(Page::class)
    ->titleAttribute('title')
    ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true))
```

### Restricting Item Types

The `visible` method decides which menus offer a type. The closure receives the `$menu` being edited, and may also check the current user:

```php
TextItemType::make()->visible(fn (Menu $menu): bool => $menu->name !== 'Social'),

LinkItemType::make()->visible(fn (): bool => auth()->user()->isAdmin()),
```

Existing items of a type that is not offered may still be edited.

### Customizing The Item Form

Each type builds the item form in its `getFormSchema` method. The icon, CSS classes and `rel` fields come from the `getAdvancedSchema` method, so you may remove them by extending the type:

```php
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;

class SimpleLinkItemType extends LinkItemType
{
    public function getAdvancedSchema(): array
    {
        return [];
    }
}
```

Then register your type in place of the original:

```php
FilamentMenuBuilderPlugin::make()
    ->itemTypes([
        SimpleLinkItemType::make(),
    ])
```

### Custom Item Types

You may create your own item type by extending the `ItemType` class. A type defines its name, its label and the fields that decide where the item points to. Those fields should fill the `url`, `route` and `route_parameters`, or `linkable_type` and `linkable_id` columns:

```php
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Filament\Forms\Components\Select;

class CategoryFilterItemType extends ItemType
{
    public static function make(): static
    {
        return new static;
    }

    public function getName(): string
    {
        return 'category_filter';
    }

    protected function getDefaultLabel(): string
    {
        return 'Category filter';
    }

    public function getDestinationSchema(): array
    {
        return [
            Select::make('url')
                ->label('Category')
                ->options(fn () => Category::pluck('name', 'slug')->mapWithKeys(
                    fn (string $name, string $slug) => ["/products?category={$slug}" => $name],
                ))
                ->required(),
        ];
    }
}
```

To add several items from one form, as `ModelItemType` does, override the `getCreateSchema` and `getItemsToCreate` methods.

## Custom Fields

You may add fields to the menu form and to the item form. Name item fields after a key of the `data` column, such as `data.badge`, and their values are included in the [menu tree](#menu-trees):

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

To vary the item fields by type, pass a closure. It receives the `$type` of the item being edited:

```php
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Filament\Forms\Components\Toggle;

FilamentMenuBuilderPlugin::make()
    ->addMenuItemFields(fn (ItemType $type): array => $type instanceof TextItemType
        ? [Toggle::make('data.mega_menu')]
        : [TextInput::make('data.badge')])
```

## Nesting

Editors may nest and reorder items by dragging them, or from each item's menu:

<img src="https://github.com/datlechin/filament-menu-builder/raw/main/art/item-actions.png" alt="An item's menu with actions to add a sub-item, duplicate, move and indent it" width="800">

Items may be nested as deep as you like. To limit the depth, use the `maxDepth` method. Root items have a depth of `0`, so `maxDepth(0)` keeps a menu flat. You may also pass a closure, which receives the `$menu` being edited:

```php
FilamentMenuBuilderPlugin::make()
    ->maxDepth(fn (Menu $menu): int => $menu->locations->contains('location', 'footer') ? 0 : 2)
```

The `allowNesting` method controls which items may be nested inside which. The closure receives the `$item` being placed, its new `$parent`, which is `null` at the root, and the `$menu`. For example, to keep headings at the root with links inside them:

```php
FilamentMenuBuilderPlugin::make()
    ->allowNesting(fn (MenuItem $item, ?MenuItem $parent): bool => $parent === null
        || ($parent->type === 'text' && $item->type !== 'text'))
```

Passing `false` keeps every item at the root. Both rules apply to dragging, to the item actions and to new items. Items may always be reordered under their current parent, so existing menus stay editable after you add a rule.

## Rendering Menus

### Blade Component

The quickest way to render a menu is the Blade component:

```blade
<x-filament-menu-builder::menu location="header" label="Main" class="main-nav" />
```

It renders an unstyled `<nav>` element with nested lists. The link of the current page receives `aria-current="page"`, and every list item receives `data-depth`, `data-active` and `data-active-trail` attributes you may style. If the location has no menu, nothing is rendered. To change the markup, publish the views:

```shell
php artisan vendor:publish --tag="filament-menu-builder-views"
```

### Menu Trees

For full control over the markup, retrieve the menu of a location and render its tree. The tree only contains visible items whose link still works:

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;

$tree = Menu::location('header')?->tree();
```

```blade
@foreach ($tree ?? [] as $node)
    <a href="{{ $node->url }}" @if ($node->target) target="{{ $node->target }}" @endif>
        {{ $node->title }}
    </a>
@endforeach
```

Each node provides the following properties:

| Property | Description |
|---|---|
| `title` | The title in the current locale. |
| `url` | The URL, or `null` for text items. |
| `target` | `'_blank'` or `null`. |
| `rel` | The `rel` attribute. |
| `icon` | The icon name. |
| `classes` | The CSS classes. |
| `type` | The item type, such as `link`, `text`, `route` or `model`. |
| `data` | The values of your custom fields. |
| `depth` | The depth of the item, starting at `0`. |
| `children` | The child nodes. |

You may filter a tree at every depth. For example, to hide items marked as members only from guests:

```php
use Datlechin\FilamentMenuBuilder\Support\MenuNode;

$tree = $tree->filter(fn (MenuNode $node): bool => auth()->check() || ! ($node->data['members_only'] ?? false));
```

### Active Items

The `isActive` method returns `true` when the node links to the current page. The `isActiveTrail` method also returns `true` when one of its descendants is active, or when the current page is below its path, so a "Blog" item stays active on every post:

```blade
<a href="{{ $node->url }}" @class(['active' => $node->isActiveTrail()])>
```

The `activeTrail` method returns the nodes from the root to the current page, which you may use for breadcrumbs:

```blade
@foreach ($tree->activeTrail() as $node)
    <a href="{{ $node->url }}">{{ $node->title }}</a>
@endforeach
```

### Inertia And APIs

Trees serialize to plain arrays, including the active state of the current request, so you may share them with Inertia or return them from a route:

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

### Caching

Trees are cached per menu, locale and domain, and refreshed when the menu, its items, its locations or a linked record changes. Linked records are only tracked when their model uses the `HasMenuItems` trait. Since route URLs are generated when the tree is built, you should clear the cache after changing your routes. You may change the cache store, or disable caching, in the configuration file. The `cache.ttl` option only decides how long entries that are no longer used stay in the cache.

## Translations

To translate menus, list your locales and the attributes to translate in the configuration file before running the migrations:

```php
'translations' => [
    'locales' => ['en', 'vi'],
    'attributes' => [
        'menu' => ['name'],
        'menu_item' => ['title', 'url'],
    ],
],
```

The translated fields receive a tab for each locale. Only the first locale is required. Missing translations fall back to your application's fallback locale, then to the first translation available. Values are stored as JSON in the format used by [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable), so a custom model using its `HasTranslations` trait also works.

To translate an existing installation, publish and run the migration that converts the columns to JSON. Existing values are kept under your application's locale:

```shell
php artisan vendor:publish --tag="filament-menu-builder-translatable-migrations"
php artisan migrate
```

## Tenancy

When your panel uses Filament's tenancy, menus may belong to a tenant. Set the tenant model in the configuration file before running the migrations, which add the foreign key to the tables:

```php
'tenant_model' => App\Models\Team::class,
```

The resource then lists the menus of the current tenant only, and each tenant assigns its own menus to locations. Pass the tenant when rendering a menu:

```php
Menu::location('header', $team)?->tree();
```

```blade
<x-filament-menu-builder::menu location="header" :tenant="$team" />
```

## Authorization

The resource and the item actions follow your `Menu` model's policy. Editing a menu's items requires the `update` ability on the menu.

## Customizing The Resource

You may customize the resource's navigation, or replace the resource with your own class extending `MenuResource`:

```php
FilamentMenuBuilderPlugin::make()
    ->navigationLabel('Menus')
    ->navigationGroup('Content')
    ->navigationIcon('heroicon-o-bars-3')
    ->navigationSort(3)
    ->usingResource(MenuResource::class)
```

The models and table names may be changed in the configuration file. Custom models should extend the plugin's models.

## Testing

The plugin ships factories you may use in your application's tests:

```php
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;

$menu = Menu::factory()->create();

MenuItem::factory()->for($menu)->route('home')->create();
MenuItem::factory()->for($menu)->linkedTo($page)->create();
MenuItem::factory()->for($menu)->text()->create();
```

To run the plugin's own test suite:

```shell
composer test
```

## Changelog

Please see the [changelog](CHANGELOG.md) for recent changes.

## Contributing

Please see the [contribution guide](.github/CONTRIBUTING.md).

## Security Vulnerabilities

Please review the [security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## License

Filament Menu Builder is open-sourced software licensed under the [MIT license](LICENSE.md).
