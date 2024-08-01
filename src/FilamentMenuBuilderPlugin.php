<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Closure;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMenuBuilderPlugin implements Plugin
{
    protected array $locations = [];

    /**
     * @var array<MenuPanel>
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

    public function location(string $key, string $label): static
    {
        $this->locations[$key] = $label;

        return $this;
    }

    public function menuPanel(Closure $callback): static
    {
        $panel = value($callback);

        if ($panel instanceof StaticMenu) {
            if (! $panel->getItems()) {
                return $this;
            }

            $this->menuPanels[] = MenuPanel::make()
                ->heading('Liên kết cố định')
                ->addItems($panel->getItems());
        } else {
            $this->menuPanels[] = $panel;
        }

        return $this;
    }

    /**
     * @return MenuPanel[]
     */
    public function getMenuPanels(): array
    {
        return $this->menuPanels;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }
}
