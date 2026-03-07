<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

// --- Bug: isActive() false positive for text-only items ---

it('does not report text-only items as active on homepage', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Section Header',
        'order' => 1,
        // url is null — this is a text-only item
    ]);

    // Text-only items should never be active
    expect($item->isActive(url('/')))->toBeFalse();
});

it('does not report text-only items as active on any page', function () {
    $menu = Menu::create(['name' => 'Test']);

    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Heading',
        'order' => 1,
    ]);

    expect($item->isActive(url('/about')))->toBeFalse()
        ->and($item->isActive(url('/contact')))->toBeFalse();
});

it('does not report text-only parent as activeOrHasActiveChild when child is on homepage', function () {
    $menu = Menu::create(['name' => 'Test']);

    $parent = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Section',
        'order' => 1,
        // text-only parent, no url
    ]);

    $child = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'About',
        'url' => '/about',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $parent->load('children');

    // Parent itself should not be active on homepage
    expect($parent->isActive(url('/')))->toBeFalse();
    // But should detect active child
    expect($parent->isActiveOrHasActiveChild(url('/about')))->toBeTrue();
});

// --- Bug: Edit/Delete labels not translated ---

it('uses translated label for edit action', function () {
    $this->actingAs(User::factory()->create());
    $menu = Menu::create(['name' => 'Test']);

    $component = livewire(MenuItems::class, ['menu' => $menu]);
    $editAction = $component->instance()->editAction();

    expect($editAction->getLabel())->toBe(__('filament-menu-builder::menu-builder.actions.edit'));
});

it('uses translated label for delete action', function () {
    $this->actingAs(User::factory()->create());
    $menu = Menu::create(['name' => 'Test']);

    $component = livewire(MenuItems::class, ['menu' => $menu]);
    $deleteAction = $component->instance()->deleteAction();

    expect($deleteAction->getLabel())->toBe(__('filament-menu-builder::menu-builder.actions.delete'));
});

// --- Bug: Double query on editAction (fillForm reuses record) ---

it('can mount and fill edit form from record', function () {
    $this->actingAs(User::factory()->create());
    $menu = Menu::create(['name' => 'Test']);
    $item = MenuItem::create([
        'menu_id' => $menu->id,
        'title' => 'Test Item',
        'url' => '/test',
        'order' => 1,
    ]);

    // Verify the edit action mounts correctly and fills form data from the record
    livewire(MenuItems::class, ['menu' => $menu])
        ->call('mountAction', 'edit', ['id' => $item->id, 'title' => $item->title])
        ->assertSuccessful()
        ->assertActionDataSet([
            'title' => 'Test Item',
            'url' => '/test',
        ]);
});
