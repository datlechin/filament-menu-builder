<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\ModelItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\RouteItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Page;
use Filament\Forms\Components\TextInput;

it('offers links and text by default', function () {
    expect(array_keys(FilamentMenuBuilderPlugin::make()->getItemTypes()))->toBe(['link', 'text']);
});

it('finds the item type of an item', function () {
    $plugin = FilamentMenuBuilderPlugin::make()->itemTypes([
        LinkItemType::make(),
        ModelItemType::make(Page::class),
    ]);
    $page = Page::create(['title' => 'About', 'slug' => 'about']);

    expect($plugin->getItemTypeFor(MenuItem::factory()->linkedTo($page)->make()))->toBeInstanceOf(ModelItemType::class)
        ->and($plugin->getItemTypeFor(MenuItem::factory()->make()))->toBeInstanceOf(LinkItemType::class)
        ->and($plugin->getItemTypeFor(MenuItem::factory()->text()->make()))->toBeNull()
        ->and($plugin->getItemType('model:' . Page::class))->toBeInstanceOf(ModelItemType::class);
});

it('rejects a negative max depth', function () {
    FilamentMenuBuilderPlugin::make()->maxDepth(-1)->getMaxDepth(new Menu);
})->throws(InvalidArgumentException::class);

it('always allows the root when nesting is disallowed', function () {
    $plugin = FilamentMenuBuilderPlugin::make()->allowNesting(false);

    expect($plugin->isNestingRestricted())->toBeTrue()
        ->and($plugin->allowsNesting(new MenuItem, null, new Menu))->toBeTrue()
        ->and($plugin->allowsNesting(new MenuItem, new MenuItem, new Menu))->toBeFalse()
        ->and(FilamentMenuBuilderPlugin::make()->allowNesting(false)->allowNesting()->isNestingRestricted())->toBeFalse();
});

it('injects the item, parent and menu into the nesting callback', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->allowNesting(fn (MenuItem $item, ?MenuItem $parent, Menu $menu): bool => $menu->name !== 'Footer' && $parent?->type === 'text');

    expect($plugin->allowsNesting(new MenuItem, new MenuItem(['type' => 'text']), new Menu(['name' => 'Header'])))->toBeTrue()
        ->and($plugin->allowsNesting(new MenuItem, new MenuItem(['type' => 'link']), new Menu(['name' => 'Header'])))->toBeFalse()
        ->and($plugin->allowsNesting(new MenuItem, new MenuItem(['type' => 'text']), new Menu(['name' => 'Footer'])))->toBeFalse();
});

it('merges custom fields and passes the item type to a closure', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->addMenuItemField(TextInput::make('data.badge'))
        ->addMenuItemFields([TextInput::make('data.note')]);

    expect(array_map(fn (TextInput $field): string => $field->getName(), $plugin->getMenuItemFields(TextItemType::make())))
        ->toBe(['data.badge', 'data.note']);

    $plugin->addMenuItemFields(fn (ItemType $type): array => $type->opensLink() ? [TextInput::make('data.badge')] : []);

    expect($plugin->getMenuItemFields(LinkItemType::make()))->toHaveCount(1)
        ->and($plugin->getMenuItemFields(TextItemType::make()))->toBe([]);
});

it('normalizes the routes of a route item type', function () {
    expect(RouteItemType::make()->routes(['home', 'blog.index' => 'Blog'])->getRoutes())->toBe(['home' => 'Home', 'blog.index' => 'Blog'])
        ->and(RouteItemType::make()->routes(fn (): array => ['home' => 'Start'])->getRoutes())->toBe(['home' => 'Start'])
        ->and(RouteItemType::getParameterNames('pages.show'))->toBe(['page'])
        ->and(RouteItemType::getParameterNames('missing'))->toBe([]);
});

it('labels a model item type after its model', function () {
    expect(ModelItemType::make(Page::class)->getLabel())->toBe('Pages')
        ->and(ModelItemType::make(Page::class)->label('Site pages')->getLabel())->toBe('Site pages')
        ->and(ModelItemType::make(Page::class)->getKey())->toBe('model:' . Page::class);
});

it('limits the records offered by a model item type', function () {
    Page::create(['title' => 'Draft', 'slug' => 'draft']);
    $published = Page::create(['title' => 'Published', 'slug' => 'published']);

    $type = ModelItemType::make(Page::class)->modifyQueryUsing(fn ($query) => $query->where('slug', '!=', 'draft'));

    expect($type->getItemsToCreate(['records' => Page::pluck('id')->all()]))
        ->toBe([['title' => 'Published', 'linkable_id' => $published->id]]);
});
