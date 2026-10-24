<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\ReadOnlyMenuPolicy;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->menu = Menu::factory()->create();
});

function menuItems(): mixed
{
    return livewire(MenuItems::class, ['menu' => test()->menu]);
}

function createItem(string $type, int | string | null $parent = null): TestAction
{
    return TestAction::make('createItem')->arguments(['type' => $type, 'parent' => $parent]);
}

function onItem(string $action, MenuItem $item): TestAction
{
    return TestAction::make($action)->arguments(['item' => $item->getKey()]);
}

it('shows an empty state with an action per item type', function () {
    menuItems()
        ->assertSee(__('filament-menu-builder::menu-builder.items.empty.heading'))
        ->assertActionVisible(createItem('link'))
        ->assertActionVisible(createItem('text'))
        ->assertActionVisible(createItem('route'))
        ->assertActionVisible(createItem('model:' . Page::class));
});

it('lists items with their URL and state', function () {
    MenuItem::factory()->for($this->menu)->create(['title' => 'Docs', 'url' => '/docs', 'is_visible' => false]);
    MenuItem::factory()->for($this->menu)->route('missing')->create(['title' => 'Gone']);

    menuItems()
        ->assertSee('Docs')
        ->assertSee('/docs')
        ->assertSee(__('filament-menu-builder::menu-builder.items.hidden'))
        ->assertSee(__('filament-menu-builder::menu-builder.items.broken'));
});

it('adds a link', function () {
    menuItems()
        ->callAction(createItem('link'), data: ['title' => 'Docs', 'url' => 'https://docs.example.com', 'target' => true])
        ->assertHasNoErrors()
        ->assertSee('Docs');

    expect(MenuItem::first())
        ->type->toBe('link')
        ->url->toBe('https://docs.example.com')
        ->target->toBe('_blank')
        ->is_visible->toBeTrue();
});

it('rejects a link that runs code', function () {
    menuItems()
        ->callAction(createItem('link'), data: ['title' => 'Bad', 'url' => 'javascript:alert(1)'])
        ->assertHasActionErrors(['url']);

    expect(MenuItem::count())->toBe(0);
});

it('adds a route with its parameters', function () {
    menuItems()
        ->callAction(createItem('route'), data: ['title' => 'FAQ', 'route' => 'pages.show', 'route_parameters' => ['page' => 'faq']])
        ->assertHasNoErrors();

    expect(MenuItem::first()->getUrl())->toBe('http://localhost/pages/faq');
});

it('adds an item for every selected record', function () {
    $about = Page::create(['title' => 'About', 'slug' => 'about']);
    $team = Page::create(['title' => 'Team', 'slug' => 'team']);

    menuItems()
        ->callAction(createItem('model:' . Page::class), data: ['records' => [$about->id, $team->id]])
        ->assertHasNoErrors();

    expect(MenuItem::orderBy('order')->pluck('title')->all())->toBe(['About', 'Team']);
});

it('adds a sub-item', function () {
    $parent = MenuItem::factory()->for($this->menu)->text()->create();

    menuItems()
        ->callAction(createItem('link', $parent->id), data: ['title' => 'Child', 'url' => '/child'])
        ->assertHasNoErrors();

    expect(MenuItem::where('title', 'Child')->value('parent_id'))->toBe($parent->id);
});

it('hides adding sub-items where the max depth is reached', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(0);
    $item = MenuItem::factory()->for($this->menu)->create();

    menuItems()->assertActionHidden(createItem('link', $item->id));
});

it('notifies when a new item may not go under its parent', function () {
    FilamentMenuBuilderPlugin::get()->allowNesting(fn (?MenuItem $parent): bool => $parent === null);
    $parent = MenuItem::factory()->for($this->menu)->create();

    menuItems()
        ->callAction(createItem('text', $parent->id), data: ['title' => 'Child'])
        ->assertNotified(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'));

    expect(MenuItem::count())->toBe(1);
});

it('edits an item', function () {
    $item = MenuItem::factory()->for($this->menu)->create(['title' => 'Old']);

    menuItems()
        ->callAction(onItem('editItem', $item), data: ['title' => 'New', 'icon' => 'heroicon-o-home', 'classes' => 'font-bold', 'is_visible' => false])
        ->assertHasNoErrors()
        ->assertSee('New');

    expect($item->fresh())
        ->title->toBe('New')
        ->icon->toBe('heroicon-o-home')
        ->classes->toBe('font-bold')
        ->is_visible->toBeFalse();
});

it('changes the record a model item links to', function () {
    $about = Page::create(['title' => 'About', 'slug' => 'about']);
    $team = Page::create(['title' => 'Team', 'slug' => 'team']);
    $item = MenuItem::factory()->for($this->menu)->linkedTo($about)->create();

    menuItems()
        ->callAction(onItem('editItem', $item), data: ['linkable_id' => $team->id])
        ->assertHasNoErrors();

    expect($item->fresh()->getUrl())->toBe('http://localhost/pages/team');
});

it('deletes an item with its sub-items', function () {
    $item = MenuItem::factory()->for($this->menu)->create();
    MenuItem::factory()->childOf($item)->create();

    menuItems()->callAction(onItem('deleteItem', $item));

    expect(MenuItem::count())->toBe(0);
});

it('duplicates, moves and nests items through their actions', function () {
    $first = MenuItem::factory()->for($this->menu)->create(['title' => 'First', 'order' => 1]);
    $second = MenuItem::factory()->for($this->menu)->create(['title' => 'Second', 'order' => 2]);

    menuItems()
        ->assertActionHidden(onItem('moveUp', $first))
        ->assertActionHidden(onItem('indent', $first))
        ->callAction(onItem('moveUp', $second))
        ->callAction(onItem('indent', $first))
        ->callAction(onItem('duplicateItem', $second));

    expect(MenuItem::whereNull('parent_id')->orderBy('order')->pluck('title')->all())->toBe(['Second', 'Second'])
        ->and($first->fresh()->parent_id)->toBe($second->id);
});

it('reorders items dropped in the tree', function () {
    $first = MenuItem::factory()->for($this->menu)->create(['order' => 1]);
    $second = MenuItem::factory()->for($this->menu)->create(['order' => 2]);

    menuItems()->call('reorder', [$second->id], $first->id)->assertNotNotified();

    expect($second->fresh()->parent_id)->toBe($first->id);
});

it('rejects a drop beyond the max depth with an explanation', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(0);
    $first = MenuItem::factory()->for($this->menu)->create(['order' => 1]);
    $second = MenuItem::factory()->for($this->menu)->create(['order' => 2]);

    menuItems()
        ->call('reorder', [$second->id], $first->id)
        ->assertNotified(
            Notification::make()
                ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
                ->body(trans_choice('filament-menu-builder::menu-builder.notifications.move_rejected.body', 0, ['depth' => 0]))
                ->danger(),
        );

    expect($second->fresh()->parent_id)->toBeNull();
});

it('rejects a drop the nesting restriction disallows', function () {
    FilamentMenuBuilderPlugin::get()->allowNesting(fn (?MenuItem $parent): bool => $parent === null || $parent->type === 'text');
    $link = MenuItem::factory()->for($this->menu)->create(['order' => 1]);
    $other = MenuItem::factory()->for($this->menu)->create(['order' => 2]);

    menuItems()
        ->call('reorder', [$other->id], $link->id)
        ->assertNotified(
            Notification::make()
                ->title(__('filament-menu-builder::menu-builder.notifications.move_rejected.title'))
                ->body(__('filament-menu-builder::menu-builder.notifications.move_rejected.nesting'))
                ->danger(),
        );
});

it('does not act on items of another menu', function () {
    $foreign = MenuItem::factory()->create();

    menuItems()
        ->assertActionHidden(onItem('editItem', $foreign))
        ->assertActionHidden(onItem('deleteItem', $foreign))
        ->assertActionHidden(onItem('duplicateItem', $foreign));
});

it('is read only for users who cannot update the menu', function () {
    Gate::policy(Menu::class, ReadOnlyMenuPolicy::class);
    $item = MenuItem::factory()->for($this->menu)->create(['order' => 1]);
    $other = MenuItem::factory()->for($this->menu)->create(['order' => 2]);

    menuItems()
        ->assertActionHidden(createItem('link'))
        ->assertActionHidden(onItem('editItem', $item))
        ->assertActionHidden(onItem('deleteItem', $item))
        ->call('reorder', [$other->id], $item->id)
        ->assertForbidden();
});

it('exposes the max depth and subtree heights to the drag and drop guard', function () {
    FilamentMenuBuilderPlugin::get()->maxDepth(2);
    $parent = MenuItem::factory()->for($this->menu)->create();
    MenuItem::factory()->childOf($parent)->create();

    menuItems()
        ->assertSeeHtml('maxDepth: 2')
        ->assertSeeHtml('data-sortable-height="1"');
});

it('fills the title from the chosen route', function () {
    menuItems()
        ->mountAction(createItem('route'))
        ->fillForm(['route' => 'pages.show'])
        ->assertFormSet(['title' => 'Page', 'route_parameters' => ['page' => null]], 'mountedActionSchema0');
});

it('gives the edit button of each item an icon', function () {
    $item = MenuItem::factory()->for($this->menu)->create();

    menuItems()->assertActionHasIcon(onItem('editItem', $item), 'heroicon-m-pencil-square');
});

it('does not bind a keyboard shortcut to deleting items', function () {
    MenuItem::factory()->for($this->menu)->count(2)->create();

    menuItems()->assertDontSeeHtml('x-mousetrap');
});

it('shows the path of links to the same site', function () {
    MenuItem::factory()->for($this->menu)->route('pages.show', ['page' => 'about'])->create();
    MenuItem::factory()->for($this->menu)->route('home')->create();
    MenuItem::factory()->for($this->menu)->create(['url' => 'https://docs.example.com/start']);

    menuItems()
        ->assertSeeHtml('<span class="fi-menu-builder-item-url">/pages/about</span>')
        ->assertSeeHtml('<span class="fi-menu-builder-item-url">/</span>')
        ->assertSeeHtml('<span class="fi-menu-builder-item-url">https://docs.example.com/start</span>');
});
