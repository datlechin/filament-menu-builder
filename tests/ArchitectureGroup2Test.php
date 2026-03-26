<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Services\MenuItemService;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

// --- resolveLocale() should come from a shared trait ---

arch('models use ResolvesLocale trait')
    ->expect('Datlechin\FilamentMenuBuilder\Models\Menu')
    ->toUseTrait('Datlechin\FilamentMenuBuilder\Concerns\ResolvesLocale');

arch('menu item model uses ResolvesLocale trait')
    ->expect('Datlechin\FilamentMenuBuilder\Models\MenuItem')
    ->toUseTrait('Datlechin\FilamentMenuBuilder\Concerns\ResolvesLocale');

it('resolveLocale works on Menu via trait', function () {
    $menu = new Menu;

    app()->setLocale('en');
    expect($menu->resolveLocale(['en' => 'Main', 'nl' => 'Hoofd']))->toBe('Main')
        ->and($menu->resolveLocale('Plain string'))->toBe('Plain string')
        ->and($menu->resolveLocale(null))->toBe('');
});

it('resolveLocale works on MenuItem via trait', function () {
    $item = new MenuItem;

    app()->setLocale('nl');
    expect($item->resolveLocale(['en' => 'Home', 'nl' => 'Thuis']))->toBe('Thuis')
        ->and($item->resolveLocale('Simple'))->toBe('Simple');
});

// --- MenuItemService constructor should not have dead code ---

it('can resolve MenuItemService from the container', function () {
    $service = app(MenuItemService::class);

    expect($service)->toBeInstanceOf(MenuItemService::class);
});

// --- ManagesMenuItemHierarchy should use container ---

it('resolves MenuItemService via container in ManagesMenuItemHierarchy', function () {
    $this->actingAs(User::factory()->create());
    $menu = Menu::create(['name' => 'Test']);

    $component = \Pest\Livewire\livewire(MenuItems::class, ['menu' => $menu]);
    $instance = $component->instance();

    $reflection = new ReflectionMethod($instance, 'getMenuItemService');
    $service = $reflection->invoke($instance);

    expect($service)->toBeInstanceOf(MenuItemService::class);

    // Verify the trait source does not contain 'new MenuItemService'
    $traitSource = file_get_contents(__DIR__ . '/../src/Concerns/ManagesMenuItemHierarchy.php');
    expect($traitSource)->not->toContain('new MenuItemService')
        ->and($traitSource)->toContain('app(');
});
