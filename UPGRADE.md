# Upgrade Guide

- [Upgrading To 2.0 From 1.x](#upgrading-to-20-from-1x)

## High Impact Changes

- [Updating Dependencies](#updating-dependencies)
- [Database Migration](#database-migration)
- [Menu Panels Replaced By Item Types](#menu-panels-replaced-by-item-types)
- [Linkable Models](#linkable-models)
- [Rendering Menus](#rendering-menus)

## Medium Impact Changes

- [Configuration](#configuration)
- [Theme Stylesheet](#theme-stylesheet)
- [Published Views](#published-views)
- [Menu Item Attributes](#menu-item-attributes)

## Low Impact Changes

- [Active State](#active-state)
- [Link Target](#link-target)
- [Translation Keys](#translation-keys)
- [Cache](#cache)
- [Livewire Components](#livewire-components)
- [Menu Item Service](#menu-item-service)

## Upgrading To 2.0 From 1.x

#### Estimated Upgrade Time: 15 Minutes

We attempt to document every possible breaking change. Since some of these changes are in obscure parts of the plugin, only a portion of them may actually affect your application.

### Updating Dependencies

**Likelihood Of Impact: High**

You should update the following dependency in your application's `composer.json` file:

- `datlechin/filament-menu-builder` to `^2.0`

Version 2.0 supports Filament 4 and Filament 5.

### Database

#### Database Migration

**Likelihood Of Impact: High**

Version 2.0 adds the `type`, `route`, `route_parameters`, `data` and `is_visible` columns to the menu items table and drops the `panel` column. You should publish and run the upgrade migration:

```shell
php artisan vendor:publish --tag="filament-menu-builder-upgrade-migrations"
php artisan migrate
```

The migration sets the type of your existing items and stores the `_self` target as `null`. If you are upgrading from 0.x, it also adds the `icon`, `classes` and `rel` columns.

### Configuration

#### Models, Translations And Tenancy

**Likelihood Of Impact: Medium**

The models, table names, translations, tenancy and cache settings now live in the `config/filament-menu-builder.php` file, so the models work outside of a Filament panel. You should publish the new configuration file and move your settings over:

```shell
php artisan vendor:publish --tag="filament-menu-builder-config" --force
```

The following plugin methods have been removed in favor of configuration keys:

| 1.x Plugin Method | 2.0 Configuration Key |
|---|---|
| `usingMenuModel()` | `models.menu` |
| `usingMenuItemModel()` | `models.menu_item` |
| `usingMenuLocationModel()` | `models.menu_location` |
| `translatable()` | `translations.locales` |
| `translatableMenuFields()` | `translations.attributes.menu` |
| `translatableMenuItemFields()` | `translations.attributes.menu_item` |

### Item Types

#### Menu Panels Replaced By Item Types

**Likelihood Of Impact: High**

Menu panels have been replaced by item types. Items are now added from the "Add item" menu above the menu tree instead of panels beside it. The `itemTypes` method replaces the default list of types, so you should include every type you would like to offer:

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

The following methods and classes have been replaced:

| 1.x | 2.0 |
|---|---|
| `addMenuPanel(ModelMenuPanel::make()->model(Page::class))` | `ModelItemType::make(Page::class)` |
| `StaticMenuPanel::make()->add('Home', fn () => route('home'))` | `RouteItemType::make()->routes(['home' => 'Home'])` |
| `showCustomLinkPanel()` | `LinkItemType::make()` |
| `showCustomTextPanel()` | `TextItemType::make()` |

Items added from a static panel stored a fixed URL, so they become link items. You may re-add them as routes so their URLs follow your routes and the current domain.

The `enableIndentActions` and `navigationCountBadge` methods have been removed. Indenting and outdenting are always available from each item's menu.

#### Linkable Models

**Likelihood Of Impact: High**

The `MenuPanelable` interface has been replaced by `MenuLinkable`, and the `HasMenuPanel` trait by `HasMenuItems`. The new trait refreshes cached menus when the model is saved or deleted:

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

The `getMenuPanelName` method has been removed. You may give the item type a label instead, and use `modifyQueryUsing` in place of `getMenuPanelQuery`:

```php
ModelItemType::make(Page::class)
    ->label('Pages')
    ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true))
```

### Menus

#### Rendering Menus

**Likelihood Of Impact: High**

The `menuItems` relationship and the `MenuItem::resolveLocale` method have been removed. You should render the menu tree instead. Its nodes contain the title for the current locale and the resolved URL, and only include visible items whose link still works:

```blade
@foreach (Menu::location('header')?->tree() ?? [] as $node)
    <a href="{{ $node->url }}" @if ($node->target) target="{{ $node->target }}" @endif>
        {{ $node->title }}
    </a>
@endforeach
```

Or, you may use the Blade component:

```blade
<x-filament-menu-builder::menu location="header" />
```

The `items` relationship returns every item of a menu as a flat list, ordered by position.

#### Menu Item Attributes

**Likelihood Of Impact: Medium**

The `url` and `type` attributes of a menu item no longer compute values. The `url` attribute returns the stored URL, which is `null` for route and model items, so you should call the `getUrl` method instead:

```php
// 1.x
$item->url;

// 2.0
$item->getUrl();
```

The `type` attribute is now a column holding the type name, such as `link`, `text`, `route` or `model`, rather than a translated label. Menu items also no longer eager load their `linkable` relationship by default.

#### Active State

**Likelihood Of Impact: Low**

The `isActive` and `isActiveOrHasActiveChild` methods have moved from `MenuItem` to `MenuNode` as `isActive` and `isActiveTrail`. The `isActiveTrail` method is also true on pages below the item's path, so a "Blog" item stays active on every post.

#### Link Target

**Likelihood Of Impact: Low**

The `LinkTarget` enum has been removed. The `target` attribute is now `'_blank'` or `null`, and the form offers an "Open in a new tab" toggle.

### Assets

#### Theme Stylesheet

**Likelihood Of Impact: Medium**

The plugin now ships its own stylesheet, so a custom Filament theme is no longer required. You should remove the plugin's `@import` and `@source` lines from your theme's CSS file, then publish the assets:

```shell
php artisan filament:assets
```

#### Published Views

**Likelihood Of Impact: Medium**

The views have been rewritten. If you published them, you should delete the `resources/views/vendor/filament-menu-builder` directory and publish them again if you still need to customize them.

#### Translation Keys

**Likelihood Of Impact: Low**

The translation keys have been reorganized. If you published the translations, you should publish them again and reapply your changes.

### Cache

**Likelihood Of Impact: Low**

The cache keys have been renamed, and menu trees are now cached in addition to the menu of each location. The deprecated `Menu::clearLocationCache` method has been removed. You may run the `cache:clear` command once after upgrading to remove the entries written by 1.x.

### Livewire Components

**Likelihood Of Impact: Low**

The `create-custom-link`, `create-custom-text` and `menu-builder-panel` Livewire components and the `menu:changed` browser event have been removed. The `menu-builder-items` component now handles adding, editing and ordering items.

### Menu Item Service

**Likelihood Of Impact: Very Low**

The `MenuItemService` is now created with `MenuItemService::make()`, and every method receives the menu. The `findById`, `findByIdWithRelations`, `update`, `getSiblings` and `reorderSiblings` methods have been removed.

### Miscellaneous

We also encourage you to view the changes in the `datlechin/filament-menu-builder` [GitHub repository](https://github.com/datlechin/filament-menu-builder). You can view every change with the [GitHub comparison tool](https://github.com/datlechin/filament-menu-builder/compare/v1.2.0...v2.0.0).
