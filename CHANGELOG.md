# Changelog

All notable changes to `filament-menu-builder` will be documented in this file.

## v2.0.0 - Unreleased

See [UPGRADE.md](UPGRADE.md) for the steps from 1.x.

### Added
- Item types: links, text, named routes and Eloquent records, plus your own by extending `ItemType`. Route and record URLs are generated when the menu is rendered.
- `Menu::tree()` returns the rendered menu as `MenuNode`s with resolved titles and URLs, `isActive()` / `isActiveTrail()`, `filter()`, `activeTrail()` for breadcrumbs, and JSON output for Inertia and APIs. The tree is cached and refreshed when the menu or a linked record changes.
- `<x-filament-menu-builder::menu>` Blade component.
- Filament tenancy support (closes discussion #143).
- Filament 4 support.
- Per item visibility, a `data` column for custom fields, and custom fields on new items as well as edited ones.
- Item actions: add a sub-item, duplicate, move up and down, indent and outdent, next to drag and drop.
- Duplicate a menu with its items.
- Items whose record or route is gone are hidden on the site and flagged in the panel.
- Locations are assigned in the menu form.
- Factories for `Menu` and `MenuItem`.

### Changed
- Models, table names, translations, tenancy and caching are configured in `config/filament-menu-builder.php`, so models work outside a panel.
- Menu panels are replaced by item types, and `MenuPanelable` by `MenuLinkable`.
- Items are added from an "Add item" menu above the tree instead of side panels.
- The stylesheet ships with the plugin; a custom theme is no longer needed.
- The link target is stored as `_blank` or `null`.

### Removed
- `StaticMenuPanel`, `ModelMenuPanel`, `HasMenuPanel`, `LinkTarget`, the custom link and text Livewire components and the locations modal.
- `showCustomLinkPanel()`, `showCustomTextPanel()`, `enableIndentActions()`, `navigationCountBadge()`, `usingMenuModel()`, `usingMenuItemModel()`, `usingMenuLocationModel()` and the `translatable*()` plugin methods.

### Fixed
- Item actions only act on items of the menu being edited and check the menu policy.
- URLs that run scripts (`javascript:`, `data:`) are rejected.
- Text items no longer show link fields (closes #95).
- A model panel no longer loads every record into the page.
- The translatable migration keeps menu items on SQLite.

## v1.2.0

### Added
- `FilamentMenuBuilderPlugin::allowNesting()` to restrict which items may be nested inside which (#178).

## v1.1.1 - 2026-10-18

### Fixed
- The menu items list no longer overflows its section on narrow screens or with long titles and URLs (closes #176). Rebuild your Filament theme after upgrading to pick up the updated stylesheet.
- Edit and delete buttons are real Filament actions with an accessible label and a tooltip.

## v1.1.0 - 2026-09-28

### Added
- `FilamentMenuBuilderPlugin::maxDepth()` to limit how deep menu items can be nested, as an `int` or a per-menu closure (closes #173).

### Fixed
- Indent and unindent buttons are real Filament actions and are hidden when they cannot be applied.
- Reordering no longer accepts items from another menu, a parent from another menu, or a parent that is one of the moved item's own descendants.
- Indenting or unindenting a root item no longer renumbers root items of every other menu.

### Changed
- `MenuItemService::indent()`, `unindent()` and `updateOrder()` take the `Menu` as their first argument and return `false` when a move is rejected.
- `MenuItemService::getPreviousSibling()`, `canIndent()` and `canUnindent()` were removed in favour of `MenuItemService::hierarchy($menu)`.

## v1.0.4 - 2026-07-21

### Added
- Russian translation (#171).

### Changed
- `MenuPanelable` uses `getMenuPanelTitle()` and `getMenuPanelUrl()` instead of `getMenuPanelTitleColumn()` and `getMenuPanelUrlUsing()`, and the `HasMenuPanel` trait provides `getMenuPanelQuery()` (#168).

## v1.0.3 - 2026-04-30

### Fixed
- `Menu::location()` no longer throws `__PHP_Incomplete_Class` on cache HIT when the cache driver serializes (file / database / redis). The cached payload is now the menu id (`int`) instead of a full Eloquent graph with polymorphic relations, eliminating the failure class entirely.
- `Cache::has()` / `Cache::get()` race in `Menu::location()` replaced with atomic `Cache::rememberForever`.
- Driver divergence on cached `null` (redis/memcached vs file/database) eliminated via integer sentinel.
- `MenuItem::menu()` and `MenuLocation::menu()` no longer fail outside a Filament panel context (frontend layouts, queue workers, console commands) — they fall back to the base `Menu` model when the plugin is unavailable.

### Changed
- Cache invalidation on MenuItem save/delete removed; items are no longer cached so no invalidation is needed. MenuItem deletes still cascade to children.
- Cache key prefix bumped to `filament-menu-builder.location.v2.{location}` so existing cached payloads from `1.0.0` are not read after upgrade.
- `Menu::clearLocationCache()` retained but deprecated; the keys-index (`filament-menu-builder.location-keys`) is no longer written.

### Upgrade
Run `php artisan cache:clear` once after upgrading. Old `1.0.0` cache entries are unreachable but leave dead data on file/database drivers until cleared.

## v1.0.0 - 2026-03-07

### Major
- Rewritten for Filament v5
- Require PHP 8.3+

### Added
- Optional translatable support for menu items and menus (closes #31, #42, #55)
- UUID support for menu item keys
- Indent/unindent buttons for menu hierarchy management
- Upgrade migration for v0.7.x users
- Menu caching with automatic cache busting
- Icon support for menu items
- CSS classes per menu item
- Active state detection for menu items
- Panel identifier for static menu items

### Changed
- Updated to use Filament v5 Schema API
- Updated build tooling to use esbuild and Tailwind CSS v4
- `MenuItemService::update()` now uses Eloquent model save instead of query builder
- `addMenuFields()` and `addMenuItemFields()` now merge arrays instead of replacing
- Renamed Livewire event `menu:created` to `menu:changed` for clarity
- Cache now uses per-location keys instead of a single shared key
- `StaticMenuPanel::add()` accepts optional `target`, `icon`, `classes` parameters
- `TranslatableFieldWrapper::wrap()` accepts optional `$primaryLocale` parameter
- Redirect to edit page after menu creation

### Fixed
- Fixed wire:click Alpine expression error and bound record to edit action
- Fixed `isActive()` false positive for text-only menu items (null URL)
- Fixed double query on edit action (`findByIdWithRelations` called twice)
- Fixed children not deleted when parent's children relation was stale
- Fixed cache race condition with concurrent `Menu::location()` calls
- Fixed order collision on concurrent menu item additions (atomic `lockForUpdate`)
- Translated Edit/Delete action labels

### Documentation
- Documented `rel` attribute, `StaticMenuPanel::add()` optional parameters, singular field API, and merge behavior in README

### Architecture
- Extracted `ResolvesLocale` trait from duplicated code in `Menu` and `MenuItem`
- `ManagesMenuItemHierarchy` now resolves `MenuItemService` via IoC container
- Cleaned up `MenuItemService` constructor (removed dead code)
- `HasLocationAction::getMenus()` now selects only `id` and `name` columns
- `max('order')` queries now use DB aggregates instead of loading all items
- Added `addMenuField()` and `addMenuItemField()` singular API methods
- Added `rel` attribute support (migration, form field, model property)
