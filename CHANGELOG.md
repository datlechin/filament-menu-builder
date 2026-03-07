# Changelog

All notable changes to `filament-menu-builder` will be documented in this file.

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
