<?php

declare(strict_types=1);

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Support\MenuNode;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->menu = Menu::factory()->create();
    $this->menu->syncLocations(['header']);

    $products = MenuItem::factory()->for($this->menu)->create(['title' => 'Products', 'url' => '/products', 'order' => 1]);
    MenuItem::factory()->childOf($products)->create(['title' => 'Shoes', 'url' => '/products/shoes', 'order' => 1, 'data' => ['badge' => 'New']]);
    MenuItem::factory()->childOf($products)->text()->create(['title' => 'Sale', 'order' => 2]);
    MenuItem::factory()->for($this->menu)->create(['title' => 'Docs', 'url' => 'https://docs.example.com', 'target' => '_blank', 'rel' => 'noopener', 'order' => 2]);
});

it('filters nodes at every depth', function () {
    $tree = $this->menu->tree()->filter(fn (MenuNode $node): bool => $node->type !== 'text');

    expect($tree)->toHaveCount(2)
        ->and($tree->nodes()[0]->children)->toHaveCount(1);
});

it('returns the active trail from the root', function () {
    $trail = $this->menu->tree()->activeTrail('http://localhost/products/shoes');

    expect(array_map(fn (MenuNode $node): string => $node->title, $trail))->toBe(['Products', 'Shoes']);
});

it('serializes to plain arrays for JSON responses', function () {
    $this->get('/products/shoes');

    $json = json_decode(json_encode($this->menu->tree()), true);

    expect($json[0])->toMatchArray(['title' => 'Products', 'url' => '/products', 'depth' => 0, 'is_active' => false, 'is_active_trail' => true])
        ->and($json[0]['children'][0])->toMatchArray(['title' => 'Shoes', 'is_active' => true, 'data' => ['badge' => 'New']]);
});

it('renders a menu by location with the Blade component', function () {
    $this->get('/products/shoes');

    $html = Blade::render('<x-filament-menu-builder::menu location="header" label="Main" class="main-nav" />');

    expect($html)
        ->toContain('<nav aria-label="Main" class="main-nav">')
        ->toContain('<a href="/products/shoes" aria-current="page">Shoes</a>')
        ->toContain('<span>Sale</span>')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener"')
        ->toContain('data-active-trail');
});

it('renders nothing for a location without a menu', function () {
    expect(trim(Blade::render('<x-filament-menu-builder::menu location="footer" />')))->toBe('');
});

it('renders a tree it is given', function () {
    $tree = $this->menu->tree()->filter(fn (MenuNode $node): bool => $node->title === 'Docs');

    expect(Blade::render('<x-filament-menu-builder::menu :tree="$tree" />', ['tree' => $tree]))
        ->toContain('Docs')
        ->not->toContain('Products');
});
