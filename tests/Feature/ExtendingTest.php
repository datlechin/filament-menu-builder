<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

class PlainLinkItemType extends LinkItemType
{
    public function getAdvancedSchema(): array
    {
        return [];
    }
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('evaluates labels in the locale of the request', function () {
    $plugin = FilamentMenuBuilderPlugin::make()
        ->addLocation('header', fn (): string => app()->getLocale() === 'vi' ? 'Đầu trang' : 'Header');
    $type = LinkItemType::make()->label(fn (): string => app()->getLocale() === 'vi' ? 'Liên kết' : 'Link');

    app()->setLocale('vi');

    expect($type->getLabel())->toBe('Liên kết')
        ->and($plugin->getLocations())->toBe(['header' => 'Đầu trang']);
});

it('offers an item type only to the menus it is visible for', function () {
    FilamentMenuBuilderPlugin::get()->itemTypes([
        LinkItemType::make(),
        TextItemType::make()->visible(fn (Menu $menu): bool => $menu->name !== 'Social'),
    ]);

    $social = Menu::factory()->create(['name' => 'Social']);
    $heading = MenuItem::factory()->for($social)->text()->create();

    livewire(MenuItems::class, ['menu' => $social])
        ->assertActionVisible(TestAction::make('createItem')->arguments(['type' => 'link']))
        ->assertActionHidden(TestAction::make('createItem')->arguments(['type' => 'text']))
        ->assertActionVisible(TestAction::make('editItem')->arguments(['item' => $heading->id]));

    livewire(MenuItems::class, ['menu' => Menu::factory()->create(['name' => 'Header'])])
        ->assertActionVisible(TestAction::make('createItem')->arguments(['type' => 'text']));
});

it('lets an item type replace the built-in fields', function () {
    FilamentMenuBuilderPlugin::get()->itemTypes([PlainLinkItemType::make()]);

    $item = MenuItem::factory()->for($menu = Menu::factory()->create())->create();

    livewire(MenuItems::class, ['menu' => $menu])
        ->mountAction(TestAction::make('editItem')->arguments(['item' => $item->id]))
        ->assertFormFieldExists('url')
        ->assertFormFieldDoesNotExist('icon')
        ->assertFormFieldDoesNotExist('classes');
});
