<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\View\Components;

use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Support\MenuTree;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class Menu extends Component
{
    public ?MenuTree $tree;

    public function __construct(
        ?string $location = null,
        ?MenuTree $tree = null,
        Model | int | string | null $tenant = null,
        public ?string $label = null,
    ) {
        $this->tree = $tree ?? ($location === null ? null : MenuBuilder::menuModel()::location($location, $tenant)?->tree());
    }

    public function shouldRender(): bool
    {
        return $this->tree?->isNotEmpty() ?? false;
    }

    public function render(): View
    {
        return view('filament-menu-builder::components.menu');
    }
}
