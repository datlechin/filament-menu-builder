<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Team;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->acme = Team::create(['name' => 'Acme', 'slug' => 'acme']);
    $this->globex = Team::create(['name' => 'Globex', 'slug' => 'globex']);
});

function teamMenu(Team $team, string $name, array $locations = []): Menu
{
    $menu = Menu::factory()->create(['name' => $name, 'team_id' => $team->id]);
    $menu->syncLocations($locations);

    return $menu;
}

it('assigns each location once per tenant', function () {
    $acme = teamMenu($this->acme, 'Acme header', ['header']);
    $globex = teamMenu($this->globex, 'Globex header', ['header']);

    expect(MenuLocation::where('location', 'header')->pluck('menu_id', 'team_id')->all())
        ->toBe([$this->acme->id => $acme->id, $this->globex->id => $globex->id])
        ->and(Menu::location('header', $this->acme)?->is($acme))->toBeTrue()
        ->and(Menu::location('header', $this->globex->id)?->is($globex))->toBeTrue()
        ->and(Menu::location('header'))->toBeNull();
});

it('keeps the location cache of each tenant apart', function () {
    $acme = teamMenu($this->acme, 'Acme header', ['header']);
    teamMenu($this->globex, 'Globex header', ['header']);

    Menu::location('header', $this->acme);
    Menu::location('header', $this->globex);

    $acme->update(['is_visible' => false]);

    expect(Menu::location('header', $this->acme))->toBeNull()
        ->and(Menu::location('header', $this->globex))->not->toBeNull();
});

it('renders the menu of a tenant', function () {
    $menu = teamMenu($this->acme, 'Acme header', ['header']);
    MenuItem::factory()->for($menu)->create(['title' => 'Acme home']);

    expect(Blade::render('<x-filament-menu-builder::menu location="header" :tenant="$team" />', ['team' => $this->acme]))
        ->toContain('Acme home');
});

describe('in a tenant panel', function () {
    // Filament assigns the current tenant to every menu created in the panel, so records are created before it boots.
    function bootTenantPanel(Team $tenant): void
    {
        test()->actingAs(User::factory()->create());
        Filament::setCurrentPanel('app');
        Filament::setTenant($tenant);
        Filament::bootCurrentPanel();
    }

    it('lists the menus of the current tenant only', function () {
        $acme = teamMenu($this->acme, 'Acme header');
        $globex = teamMenu($this->globex, 'Globex header');

        bootTenantPanel($this->acme);

        livewire(ListMenus::class)
            ->assertCanSeeTableRecords([$acme])
            ->assertCanNotSeeTableRecords([$globex]);
    });

    it('creates menus for the current tenant', function () {
        bootTenantPanel($this->acme);

        livewire(ListMenus::class)
            ->callAction('create', data: ['name' => 'Footer', 'locations' => ['footer']])
            ->assertHasNoErrors();

        $menu = Menu::firstWhere('name', 'Footer');

        expect($menu->team_id)->toBe($this->acme->id)
            ->and($menu->locations->first()->team_id)->toBe($this->acme->id);
    });

    it('only describes locations taken within the tenant', function () {
        teamMenu($this->globex, 'Globex header', ['header']);
        $menu = teamMenu($this->acme, 'Acme footer');

        bootTenantPanel($this->acme);

        livewire(EditMenu::class, ['record' => $menu->getRouteKey()])
            ->assertDontSee('Used by Globex header');
    });
});
