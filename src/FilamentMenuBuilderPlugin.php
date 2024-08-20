<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMenuBuilderPlugin implements Plugin
{
    protected string $resource = MenuResource::class;

    protected string $menuModel = Menu::class;

    protected string $menuItemModel = MenuItem::class;

    protected string $menuLocationModel = MenuLocation::class;

    protected array $locations = [];

    protected array | Closure $menuFields = [];

    protected array | Closure $menuItemFields = [];

    /**
     * @var MenuPanel[]
     */
    protected array $menuPanels = [];

    public function getId(): string
    {
        return 'menu-builder';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([$this->getResource()]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function usingResource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function usingMenuModel(string $model): static
    {
        $this->menuModel = $model;

        return $this;
    }

    public function usingMenuItemModel(string $model): static
    {
        $this->menuItemModel = $model;

        return $this;
    }

    public function usingMenuLocationModel(string $model): static
    {
        $this->menuLocationModel = $model;

        return $this;
    }

    public function addLocation(string $key, string $label): static
    {
        $this->locations[$key] = $label;

        return $this;
    }

    public function addLocations(array $locations): static
    {
        foreach ($locations as $key => $label) {
            $this->addLocation($key, $label);
        }

        return $this;
    }

    public function addMenuPanel(MenuPanel $menuPanel): static
    {
        $this->menuPanels[] = $menuPanel;

        return $this;
    }

    /**
     * @param  array<MenuPanel>  $menuPanels
     */
    public function addMenuPanels(array $menuPanels): static
    {
        foreach ($menuPanels as $menuPanel) {
            $this->addMenuPanel($menuPanel);
        }

        return $this;
    }

    public function addMenuFields(array | Closure $schema): static
    {
        $this->menuFields = $schema;

        return $this;
    }

    public function addMenuItemFields(array | Closure $schema): static
    {
        $this->menuItemFields = $schema;

        return $this;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getMenuModel(): string
    {
        return $this->menuModel;
    }

    public function getMenuItemModel(): string
    {
        return $this->menuItemModel;
    }

    public function getMenuLocationModel(): string
    {
        return $this->menuLocationModel;
    }

    /**
     * @return MenuPanel[]
     */
    public function getMenuPanels(): array
    {
        return collect($this->menuPanels)
            ->sortBy(fn (MenuPanel $menuPanel) => $menuPanel->getSort())
            ->all();
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    public function getMenuFields(): array | Closure
    {
        return $this->menuFields;
    }

    public function getMenuItemFields(): array | Closure
    {
        return $this->menuItemFields;
    }
}
