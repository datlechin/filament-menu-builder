# Plugin Improvement Tracker

## Critical — Bugs / Correctness

- [x] **Double query on edit** — `editAction` calls `findByIdWithRelations()` twice (once for `->record()`, once for `->fillForm()`) — `src/Livewire/MenuItems.php:56-57`
- [x] **`isActive()` false positive for text-only items** — when `url` is null, `url('')` resolves to app root, so text items report active on homepage — `src/Models/MenuItem.php:88-93`
- [x] **Children not deleted on bulk delete** — changed `$menuItem->children->each->delete()` to `$menuItem->children()->each(...)` to always query DB — `src/Models/MenuItem.php`
- [x] **Cache race condition** — replaced shared collection cache with per-location cache keys (`filament-menu-builder.location.{name}`) — `src/Models/Menu.php`
- [x] **Order collision on concurrent adds** — wrapped in `DB::transaction` with `lockForUpdate` for atomic order assignment — `MenuPanel.php`, `CreateCustomLink.php`, `CreateCustomText.php`
- [x] **Edit/Delete labels not translated** — hardcoded `'Edit'` and `'Delete'` strings instead of `__()` calls — `src/Livewire/MenuItems.php:51,64`

## High — Architecture Improvements

- [x] **`resolveLocale()` duplicated verbatim** across `Menu` and `MenuItem` — extracted to `ResolvesLocale` trait
- [x] **`MenuItemService` constructor dead code** — cleaned up constructor, removed unused default parameter
- [x] **Service bypasses IoC** — `ManagesMenuItemHierarchy` now uses `app()` container resolution
- [x] **Coarse-grained cache** — replaced single shared key with per-location cache keys — `Menu.php`, `MenuItem.php`, `MenuLocation.php`
- [x] **`addMenuFields()` is a destructive setter** — now merges arrays; closures still replace — `FilamentMenuBuilderPlugin.php`
- [x] **`HasLocationAction` loads entire tables** — `Model::all()` with no select/limit for menus and locations — `HasLocationAction.php:86-98`
- [x] **`$this->menu->menuItems->max('order')` loads all items** for a single aggregate — should be a DB `max()` query — `MenuPanel.php:79`, `CreateCustomLink.php:47`, `CreateCustomText.php:33`

## Medium — Extensibility

- [ ] **No lifecycle hooks** on menu item create/update/delete — users can't intercept without overriding entire Livewire components — `MenuPanel.php`, `CreateCustomLink.php`, `CreateCustomText.php`
- [ ] **`EditMenu` layout not overridable** — hardcoded grid ratios and component placement — `EditMenu.php:26-74`
- [ ] **No authorization hooks** — no `canEdit`/`canDelete` on menu items; any panel user can modify any item — `MenuItems.php:63-72`
- [x] **`StaticMenuPanel::add()` only supports `title` and `url`** — added optional `target`, `icon`, `classes` params — `StaticMenuPanel.php`
- [x] **`ModelMenuPanel` drops extra model attributes** — verified: maps correctly; SoftDeletes handled via Eloquent global scope — `ModelMenuPanel.php`
- [x] **No `rel` attribute** — added `rel` column, migration, form field, and PHPDoc — `MenuItem.php`, `CreateCustomLink.php`, `MenuItems.php`
- [x] **`TranslatableFieldWrapper` assumes first locale is primary** — added optional `$primaryLocale` parameter — `TranslatableFieldWrapper.php`

## Low — DX / Polish

- [x] **Misleading Livewire event name** — renamed `menu:created` to `menu:changed` — multiple Livewire components
- [x] **API inconsistency** — added `addMenuField()` and `addMenuItemField()` singular methods — `FilamentMenuBuilderPlugin.php`
- [x] **`SoftDeletes` not handled** — verified: Eloquent's global scope handles this automatically — `HasMenuPanel.php`
- [ ] **No menu item duplication** action — missing
- [x] **No redirect to builder after menu creation** — added `successRedirectUrl` to edit page — `ListMenus.php`

## Test Coverage Gaps

- [x] `TranslatableFieldWrapper` tab labels and required-field logic not verified
- [x] `MenuItem::isActive()` with null URL (text items)
- [x] `MenuLocation::saved` cache invalidation
- [x] `ModelMenuPanel` with custom query modifier — tested via Group 7
- [x] `MenuResource::getNavigationBadge`
- [x] Concurrent order collision scenario — tested via Group 8 (lockForUpdate)
- [x] `HasMenuPanel` with soft-deleted models — tested via Group 7
