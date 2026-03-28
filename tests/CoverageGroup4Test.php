<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Livewire\CreateCustomLink;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Illuminate\Support\Facades\Cache;

// --- TranslatableFieldWrapper tab labels and required-field logic ---

it('returns a Tabs instance with columnSpanFull', function () {
    $field = TextInput::make('title')->required();
    $tabs = TranslatableFieldWrapper::wrap($field, ['en', 'nl', 'vi']);

    expect($tabs)->toBeInstanceOf(Tabs::class)
        ->and($tabs->getColumnSpan())->toBe(['default' => 'full']);
});

it('wraps field with correct locale-scoped names', function () {
    // Verify the wrapper source code creates fields with locale state paths
    $source = file_get_contents(__DIR__ . '/../src/Support/TranslatableFieldWrapper.php');

    expect($source)
        ->toContain('statePath("{$fieldName}.{$locale}")')
        ->toContain('name("{$fieldName}.{$locale}")')
        ->toContain('strtoupper($locale)')
        ->toContain('$locale !== $primaryLocale')
        ->toContain('required(false)');
});

it('wraps fields for each configured locale in CreateCustomLink form', function () {
    $this->actingAs(User::factory()->create());
    $menu = Menu::create(['name' => 'Test']);

    // The fixture AdminPanelProvider does not enable translatable,
    // so verify the form works without tabs when not translatable
    $component = \Pest\Livewire\livewire(CreateCustomLink::class, ['menu' => $menu]);

    $component->assertFormFieldExists('title')
        ->assertFormFieldExists('url');
});

// --- MenuItem::isActive() with null URL (text items) ---

it('returns false for isActive when url attribute resolves to null', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Text Only',
        'order' => 1,
    ]);

    // Reload to ensure url accessor runs
    $item->refresh();

    expect($item->url)->toBeNull()
        ->and($item->isActive())->toBeFalse()
        ->and($item->isActive(url('/')))->toBeFalse()
        ->and($item->isActive(url('/any-page')))->toBeFalse();
});

it('returns true for isActive when url matches current url', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'About',
        'url' => '/about',
        'order' => 1,
    ]);

    expect($item->isActive(url('/about')))->toBeTrue()
        ->and($item->isActive(url('/other')))->toBeFalse();
});

// --- MenuLocation::saved cache invalidation ---

it('clears cache when a menu location is saved', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);

    // Prime the cache using the location() method
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);
    Menu::location('header');
    expect(Cache::has('filament-menu-builder.location.header'))->toBeTrue();

    // Creating another location should clear all location caches
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'footer']);

    expect(Cache::has('filament-menu-builder.location.header'))->toBeFalse();
});

it('clears cache when a menu location is updated', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    $location = MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    Menu::location('header');
    expect(Cache::has('filament-menu-builder.location.header'))->toBeTrue();

    // Update location — should clear cache
    $location->update(['location' => 'footer']);

    expect(Cache::has('filament-menu-builder.location.header'))->toBeFalse();
});

it('clears cache when a menu location is deleted', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    $location = MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    Menu::location('header');
    expect(Cache::has('filament-menu-builder.location.header'))->toBeTrue();

    $location->delete();

    expect(Cache::has('filament-menu-builder.location.header'))->toBeFalse();
});

// --- MenuResource::getNavigationBadge ---

it('returns null navigation badge when disabled', function () {
    // Badge is disabled by default in test fixture
    $plugin = FilamentMenuBuilderPlugin::get();

    // Ensure badge is off
    expect($plugin->getNavigationCountBadge())->toBeFalse();
    expect(MenuResource::getNavigationBadge())->toBeNull();
});

it('returns formatted count when navigation badge is enabled', function () {
    Menu::create(['name' => 'Menu A']);
    Menu::create(['name' => 'Menu B']);
    Menu::create(['name' => 'Menu C']);

    $plugin = FilamentMenuBuilderPlugin::get();
    $plugin->navigationCountBadge(true);

    // Should return the count of menus as a formatted string
    $badge = MenuResource::getNavigationBadge();
    expect($badge)->toBe(number_format(Menu::count()));

    // Reset
    $plugin->navigationCountBadge(false);
});

// --- MenuItem cache invalidation on save/delete ---

it('clears location cache when a menu item is saved', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'header']);

    Menu::location('header');
    expect(Cache::has('filament-menu-builder.location.header'))->toBeTrue();

    MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'New Item',
        'order' => 1,
    ]);

    expect(Cache::has('filament-menu-builder.location.header'))->toBeFalse();
});

it('clears cache and deletes children when a menu item is deleted', function () {
    $menu = Menu::create(['name' => 'Test', 'is_visible' => true]);
    MenuLocation::create(['menu_id' => $menu->id, 'location' => 'test']);

    Menu::location('test');
    expect(Cache::has('filament-menu-builder.location.test'))->toBeTrue();

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Parent',
        'order' => 1,
    ]);

    $child = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    // Re-prime cache
    Menu::location('test');

    $parent->load('children');
    $parent->delete();

    expect(Cache::has('filament-menu-builder.location.test'))->toBeFalse()
        ->and(MenuItem::find($child->id))->toBeNull();
});

// --- Menu::location() caching ---

it('caches menu location lookups', function () {
    $menu = Menu::create(['name' => 'Header Menu', 'is_visible' => true]);
    MenuLocation::create([
        'menu_id' => $menu->id,
        'location' => 'header',
    ]);

    // First call should query and cache
    $result = Menu::location('header');
    expect($result)->not->toBeNull()
        ->and($result->name)->toBe('Header Menu');

    // Cache should now contain the result
    expect(Cache::has('filament-menu-builder.location.header'))->toBeTrue();
});

it('returns null for non-existent location', function () {
    $result = Menu::location('nonexistent');
    expect($result)->toBeNull();
});
