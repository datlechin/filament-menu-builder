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

### Fixed
- Fixed wire:click Alpine expression error and bound record to edit action
