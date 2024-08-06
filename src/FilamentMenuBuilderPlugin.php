<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMenuBuilderPlugin implements Plugin
{
    protected array $locations = [];

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
        $panel->resources([
            MenuResource::class,
        ]);
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

    public function addLocation(string $key, string $label): static
    {
        $this->locations[$key] = $label;

        return $this;
    }

    public function addMenuPanel(MenuPanel $menuPanel): static
    {
        if ($menuPanel->getItems()) {
            $this->menuPanels[] = $menuPanel;
        }

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
}
