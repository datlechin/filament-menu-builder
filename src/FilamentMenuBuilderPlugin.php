<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Closure;
use Datlechin\FilamentMenuBuilder\ItemTypes\ItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FilamentMenuBuilderPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string $resource = MenuResource::class;

    /**
     * @var array<string, string|Closure>
     */
    protected array $locations = [];

    /**
     * @var array<string, ItemType>|null
     */
    protected ?array $itemTypes = null;

    /**
     * @var array<Component>|Closure
     */
    protected array | Closure $menuFields = [];

    /**
     * @var array<Component>|Closure
     */
    protected array | Closure $menuItemFields = [];

    protected int | Closure | null $maxDepth = null;

    protected bool | Closure $allowNesting = true;

    protected string | Closure | null $navigationLabel = null;

    protected string | Closure | null $navigationGroup = null;

    protected string | Closure | null $navigationIcon = 'heroicon-o-bars-3';

    protected int | Closure | null $navigationSort = null;

    public function getId(): string
    {
        return 'menu-builder';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([$this->getResource()]);
    }

    public function boot(Panel $panel): void {}

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

    public function getResource(): string
    {
        return $this->resource;
    }

    public function addLocation(string $key, string | Closure $label): static
    {
        $this->locations[$key] = $label;

        return $this;
    }

    /**
     * @param  array<string, string|Closure>  $locations
     */
    public function addLocations(array $locations): static
    {
        foreach ($locations as $key => $label) {
            $this->addLocation($key, $label);
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLocations(): array
    {
        return array_map(fn (string | Closure $label): string => $this->evaluate($label), $this->locations);
    }

    /**
     * @param  array<ItemType>  $itemTypes
     */
    public function itemTypes(array $itemTypes): static
    {
        $this->itemTypes = collect($itemTypes)
            ->keyBy(fn (ItemType $itemType): string => $itemType->getKey())
            ->all();

        return $this;
    }

    /**
     * @return array<string, ItemType>
     */
    public function getItemTypes(): array
    {
        return $this->itemTypes ??= [
            'link' => LinkItemType::make(),
            'text' => TextItemType::make(),
        ];
    }

    public function getItemType(string $key): ?ItemType
    {
        return $this->getItemTypes()[$key] ?? null;
    }

    public function getItemTypeFor(MenuItem $item): ?ItemType
    {
        return collect($this->getItemTypes())->first(fn (ItemType $itemType): bool => $itemType->matches($item));
    }

    public function maxDepth(int | Closure | null $depth): static
    {
        $this->maxDepth = $depth;

        return $this;
    }

    public function getMaxDepth(Model $menu): ?int
    {
        $depth = $this->evaluate(
            $this->maxDepth,
            namedInjections: ['menu' => $menu],
            typedInjections: [Model::class => $menu, Menu::class => $menu, $menu::class => $menu],
        );

        if ($depth !== null && $depth < 0) {
            throw new InvalidArgumentException("The maximum menu depth must be zero or greater, [{$depth}] given.");
        }

        return $depth;
    }

    public function allowNesting(bool | Closure $condition = true): static
    {
        $this->allowNesting = $condition;

        return $this;
    }

    public function isNestingRestricted(): bool
    {
        return $this->allowNesting !== true;
    }

    public function allowsNesting(Model $item, ?Model $parent, Model $menu): bool
    {
        if (is_bool($this->allowNesting)) {
            return $this->allowNesting || $parent === null;
        }

        return (bool) $this->evaluate(
            $this->allowNesting,
            namedInjections: ['item' => $item, 'parent' => $parent, 'menu' => $menu],
            typedInjections: [Model::class => $item, MenuItem::class => $item, $item::class => $item, Menu::class => $menu, $menu::class => $menu],
        );
    }

    public function addMenuField(Component $field): static
    {
        return $this->addMenuFields([$field]);
    }

    /**
     * @param  array<Component>|Closure  $schema
     */
    public function addMenuFields(array | Closure $schema): static
    {
        $this->menuFields = is_array($schema) && is_array($this->menuFields)
            ? [...$this->menuFields, ...$schema]
            : $schema;

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getMenuFields(): array
    {
        return $this->evaluate($this->menuFields);
    }

    public function addMenuItemField(Component $field): static
    {
        return $this->addMenuItemFields([$field]);
    }

    /**
     * @param  array<Component>|Closure  $schema  A closure receives the `$type` of the item being edited.
     */
    public function addMenuItemFields(array | Closure $schema): static
    {
        $this->menuItemFields = is_array($schema) && is_array($this->menuItemFields)
            ? [...$this->menuItemFields, ...$schema]
            : $schema;

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getMenuItemFields(ItemType $type): array
    {
        return $this->evaluate(
            $this->menuItemFields,
            namedInjections: ['type' => $type],
            typedInjections: [ItemType::class => $type, $type::class => $type],
        );
    }

    public function navigationLabel(string | Closure | null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->evaluate($this->navigationLabel);
    }

    public function navigationGroup(string | Closure | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function navigationIcon(string | Closure | null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function navigationSort(int | Closure | null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }
}
