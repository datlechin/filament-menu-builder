<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Closure;
use Datlechin\FilamentMenuBuilder\Contracts\MenuPanel;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;

class FilamentMenuBuilderPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string $resource = MenuResource::class;

    protected string $menuModel = Menu::class;

    protected string $menuItemModel = MenuItem::class;

    protected string $menuLocationModel = MenuLocation::class;

    protected array $locations = [];

    protected array | Closure $menuFields = [];

    protected array | Closure $menuItemFields = [];

    protected string | Closure | null $navigationLabel = null;

    protected string | Closure | null $navigationGroup = null;

    protected string | Closure | null $navigationIcon = 'heroicon-o-bars-3';

    protected int | Closure | null $navigationSort = null;

    protected bool $navigationCountBadge = false;

    /**
     * @var MenuPanel[]
     */
    protected array $menuPanels = [];

    protected bool $showCustomLinkPanel = true;

    protected bool $showCustomTextPanel = false;

    protected bool $enableIndentActions = true;

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
        return filament(app(static::class)->getId());
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

    public function showCustomLinkPanel(bool $show = true): static
    {
        $this->showCustomLinkPanel = $show;

        return $this;
    }

    public function showCustomTextPanel(bool $show = true): static
    {
        $this->showCustomTextPanel = $show;

        return $this;
    }

    public function enableIndentActions(bool $enable = true): static
    {
        $this->enableIndentActions = $enable;

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

    public function navigationLabel(string | Closure | null $label = null): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationGroup(string | Closure | null $group = null): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationIcon(string | Closure $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(int | Closure $order): static
    {
        $this->navigationSort = $order;

        return $this;
    }

    public function navigationCountBadge(bool $show = true): static
    {
        $this->navigationCountBadge = $show;

        return $this;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @template TModel of Model
     *
     * @return class-string<TModel>
     */
    public function getMenuModel(): string
    {
        return $this->menuModel;
    }

    /**
     * @template TModel of Model
     *
     * @return class-string<TModel>
     */
    public function getMenuItemModel(): string
    {
        return $this->menuItemModel;
    }

    /**
     * @template TModel of Model
     *
     * @return class-string<TModel>
     */
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

    public function isShowCustomLinkPanel(): bool
    {
        return $this->showCustomLinkPanel;
    }

    public function isShowCustomTextPanel(): bool
    {
        return $this->showCustomTextPanel;
    }

    public function isIndentActionsEnabled(): bool
    {
        return $this->enableIndentActions;
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

    public function getNavigationGroup(): ?string
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function getNavigationLabel(): ?string
    {
        return $this->evaluate($this->navigationLabel);
    }

    public function getNavigationIcon(): ?string
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    public function getNavigationCountBadge(): bool
    {
        return $this->navigationCountBadge;
    }
}
