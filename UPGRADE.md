# Upgrading

## From 1.x to 2.0

### Dependencies

```bash
composer require datlechin/filament-menu-builder:^2.0
```

### Config

The config now holds the models, translations, tenancy and cache settings. Re-publish it, then move your settings over:

```bash
php artisan vendor:publish --tag="filament-menu-builder-config" --force
```

| 1.x plugin method | 2.0 config key |
|---|---|
| `usingMenuModel()`, `usingMenuItemModel()`, `usingMenuLocationModel()` | `models` |
| `translatable(['en', 'vi'])` | `translations.locales` |
| `translatableMenuFields()`, `translatableMenuItemFields()` | `translations.attributes` |

### Database

Publish and run the upgrade migration. It adds the `type`, `route`, `route_parameters`, `data` and `is_visible` columns, sets the type of existing items, stores the `_self` target as `null` and drops the `panel` column. It also adds the `icon`, `classes` and `rel` columns when coming from 0.x.

```bash
php artisan vendor:publish --tag="filament-menu-builder-upgrade-migrations"
php artisan migrate
```

### Theme

Remove the plugin's `@import` and `@source` lines from your Filament theme. The plugin ships its own stylesheet, published with `php artisan filament:assets`.

Published views of 1.x no longer match. Delete `resources/views/vendor/filament-menu-builder` and publish them again if you still need to override them.

### Panels become item types

| 1.x | 2.0 |
|---|---|
| `addMenuPanel(ModelMenuPanel::make()->model(Page::class))` | `itemTypes([..., ModelItemType::make(Page::class)])` |
| `StaticMenuPanel::make()->add('Home', fn () => route('home'))` | `RouteItemType::make()->routes(['home' => 'Home'])` |
| `showCustomLinkPanel()` / `showCustomTextPanel()` | Include `LinkItemType::make()` / `TextItemType::make()` in `itemTypes()` |
| `enableIndentActions()` | Removed, indent and outdent are in every item's menu |
| `navigationCountBadge()` | Removed |

`itemTypes()` replaces the default list, so include every type you want:

```php
FilamentMenuBuilderPlugin::make()
    ->itemTypes([
        LinkItemType::make(),
        TextItemType::make(),
        RouteItemType::make()->routes(['home' => 'Home', 'blog.index' => 'Blog']),
        ModelItemType::make(Page::class)->titleAttribute('title'),
    ])
```

Items added from a static panel stored a fixed URL and stay link items. Re-add them as routes to get URLs that follow route changes.

### Linkable models

```diff
-use Datlechin\FilamentMenuBuilder\Concerns\HasMenuPanel;
-use Datlechin\FilamentMenuBuilder\Contracts\MenuPanelable;
+use Datlechin\FilamentMenuBuilder\Concerns\HasMenuItems;
+use Datlechin\FilamentMenuBuilder\Contracts\MenuLinkable;

-class Page extends Model implements MenuPanelable
+class Page extends Model implements MenuLinkable
 {
-    use HasMenuPanel;
+    use HasMenuItems;

-    public function getMenuPanelTitle(): string
+    public function getMenuTitle(): string
     {
         return $this->title;
     }

-    public function getMenuPanelUrl(): string
+    public function getMenuUrl(): string
     {
         return route('pages.show', $this);
     }

-    public function getMenuPanelName(): string
-    {
-        return 'Pages';
-    }
 }
```

The panel name moves to `ModelItemType::make(Page::class)->label('Pages')`, and `getMenuPanelQuery()` to `->modifyQueryUsing()`.

### Rendering

`$menu->menuItems` and `MenuItem::resolveLocale()` are gone. Render the tree instead, whose nodes have the translated title and the resolved URL:

```diff
-@foreach ($menu->menuItems as $item)
-    <a href="{{ $item->url }}" target="{{ $item->target }}">{{ $item->resolveLocale($item->title) }}</a>
+@foreach ($menu->tree() as $node)
+    <a href="{{ $node->url }}" @if ($node->target) target="{{ $node->target }}" @endif>{{ $node->title }}</a>
 @endforeach
```

Or use the component: `<x-filament-menu-builder::menu location="header" />`.

`MenuItem::isActive()` and `isActiveOrHasActiveChild()` move to `MenuNode::isActive()` and `isActiveTrail()`. The `LinkTarget` enum is removed; `target` is `'_blank'` or `null`.

### Services and components

- `MenuItemService` is created with `MenuItemService::make()` and every method takes the `Menu`. `findById()`, `findByIdWithRelations()`, `update()`, `getSiblings()` and `reorderSiblings()` are removed.
- The `create-custom-link`, `create-custom-text` and `menu-builder-panel` Livewire components are removed.
- Translation keys were reorganised. Re-publish your translation overrides.
