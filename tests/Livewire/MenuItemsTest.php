<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Js;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::create(['name' => 'Test Menu']);
});

it('can render without items', function () {
    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertSuccessful();
});

it('can reorder items', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'A', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'B', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$item2->id, $item1->id]);

    expect($item2->fresh()->order)->toBe(1)
        ->and($item1->fresh()->order)->toBe(2);
});

it('can indent an item', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('indent', $item2->id);

    expect($item2->fresh()->parent_id)->toBe($item1->id);
});

it('can unindent an item', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('unindent', $child->id);

    expect($child->fresh()->parent_id)->toBeNull();
});

it('can check indent capability', function () {
    $item1 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $item2 = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    $component = livewire(MenuItems::class, ['menu' => $this->menu]);
    $instance = $component->instance();

    expect($instance->canIndent($item1->id))->toBeFalse()
        ->and($instance->canIndent($item2->id))->toBeTrue();
});

it('can check unindent capability', function () {
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);
    $child = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Child',
        'order' => 1,
        'parent_id' => $parent->id,
    ]);

    $component = livewire(MenuItems::class, ['menu' => $this->menu]);
    $instance = $component->instance();

    expect($instance->canUnindent($parent->id))->toBeFalse()
        ->and($instance->canUnindent($child->id))->toBeTrue();
});

it('can mount edit action', function () {
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Editable', 'url' => '/edit-me', 'order' => 1]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('mountAction', 'edit', ['id' => $item->id, 'title' => $item->title])
        ->assertSuccessful();
});

it('can update icon and classes via edit action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'Home',
        'url' => '/',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('edit', data: [
            'title' => 'Home',
            'url' => '/',
            'icon' => 'heroicon-o-home',
            'classes' => 'font-bold',
            'target' => '_self',
        ], arguments: ['id' => $item->id, 'title' => $item->title]);

    $item->refresh();

    expect($item->icon)->toBe('heroicon-o-home')
        ->and($item->classes)->toBe('font-bold');
});

it('can delete a menu item via action', function () {
    $item = MenuItem::create([
        'menu_id' => $this->menu->id,
        'title' => 'To Delete',
        'url' => '/delete',
        'order' => 1,
    ]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('delete', arguments: ['id' => $item->id, 'title' => $item->title]);

    expect(MenuItem::find($item->id))->toBeNull();
});

function hierarchyActionHandler(string $action, int | string $itemId): string
{
    return "mountAction('{$action}', " . Js::from(['id' => $itemId]);
}

it('renders indent actions only where they can be applied', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $sibling = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Sibling', 'order' => 2, 'parent_id' => $root->id]);
    $second = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertDontSeeHtml(hierarchyActionHandler('indent', $sibling->id))
        ->assertSeeHtml(hierarchyActionHandler('unindent', $sibling->id))
        ->assertDontSeeHtml(hierarchyActionHandler('indent', $root->id))
        ->assertDontSeeHtml(hierarchyActionHandler('unindent', $root->id))
        ->assertSeeHtml(hierarchyActionHandler('indent', $second->id));
});

it('does not render indent actions when they are disabled', function () {
    FilamentMenuBuilderPlugin::get()->enableIndentActions(false);

    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $second = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertDontSeeHtml(hierarchyActionHandler('indent', $second->id));
});

it('indents an item through the indent action and re-renders its actions', function () {
    $first = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'First', 'order' => 1]);
    $second = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Second', 'order' => 2]);
    $third = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Third', 'order' => 3]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->callAction('indent', arguments: ['id' => $second->id])
        ->assertSeeHtml(hierarchyActionHandler('unindent', $second->id))
        ->assertDontSeeHtml(hierarchyActionHandler('indent', $second->id))
        ->assertSeeHtml(hierarchyActionHandler('indent', $third->id))
        ->assertDontSeeHtml(hierarchyActionHandler('unindent', $third->id));

    expect($second->fresh()->parent_id)->toBe($first->id);
});

it('shows no depth explanation when a move is rejected for another reason', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $otherMenu = Menu::create(['name' => 'Other Menu']);
    $foreign = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Foreign', 'order' => 1]);
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$foreign->id], $parent->id)
        ->assertNotified(
            Notification::make()
                ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
                ->danger(),
        );
});

it('rejects a drop beyond the max depth and notifies the user', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(1);

    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    $child = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);
    $item = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Item', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$item->id], $child->id)
        ->assertNotified(
            Notification::make()
                ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
                ->body(trans_choice('filament-menu-builder::menu-builder.notifications.move_rejected.body', 1, ['depth' => 1]))
                ->danger(),
        );

    expect($item->fresh()->parent_id)->toBeNull();
});

it('does not reorder items of another menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    $foreign = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Foreign', 'order' => 1]);
    $parent = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Parent', 'order' => 1]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('reorder', [$foreign->id], $parent->id);

    expect($foreign->fresh()->parent_id)->toBeNull();
});

it('does not indent items of another menu', function () {
    $otherMenu = Menu::create(['name' => 'Other Menu']);
    MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'First', 'order' => 1]);
    $foreign = MenuItem::create(['menu_id' => $otherMenu->id, 'title' => 'Second', 'order' => 2]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->call('indent', $foreign->id);

    expect($foreign->fresh()->parent_id)->toBeNull();
});

it('exposes the max depth and subtree heights to the drag and drop guard', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(2);

    $root = MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Root', 'order' => 1]);
    MenuItem::create(['menu_id' => $this->menu->id, 'title' => 'Child', 'order' => 1, 'parent_id' => $root->id]);

    livewire(MenuItems::class, ['menu' => $this->menu])
        ->assertSeeHtml('maxDepth: 2')
        ->assertSeeHtml('data-sortable-height="1"')
        ->assertSeeHtml('data-sortable-depth="1"');
});
