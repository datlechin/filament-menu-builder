<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\ItemTypes;

use Closure;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteItemType extends ItemType
{
    /**
     * @var array<int|string, string>|Closure
     */
    protected array | Closure $routes = [];

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  array<int|string, string>|Closure  $routes  Route names, or route names mapped to labels.
     */
    public function routes(array | Closure $routes): static
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getRoutes(): array
    {
        $routes = $this->routes instanceof Closure ? ($this->routes)() : $this->routes;

        return collect($routes)
            ->mapWithKeys(fn (string $label, int | string $name): array => is_int($name) ? [$label => Str::headline($label)] : [$name => $label])
            ->all();
    }

    public function getName(): string
    {
        return 'route';
    }

    protected function getDefaultLabel(): string
    {
        return __('filament-menu-builder::menu-builder.item_types.route');
    }

    protected function getDefaultIcon(): string
    {
        return 'heroicon-o-document';
    }

    public function getDestinationSchema(): array
    {
        return [
            Select::make('route')
                ->label(__('filament-menu-builder::menu-builder.form.route'))
                ->options(fn (): array => $this->getRoutes())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                    $set('route_parameters', array_fill_keys(self::getParameterNames($state), null));

                    $titlePath = in_array('title', MenuBuilder::translatableAttributes('menu_item'), true)
                        ? 'title.' . MenuBuilder::locales()[0]
                        : 'title';

                    if ($state && blank($get($titlePath))) {
                        $set($titlePath, $this->getRoutes()[$state] ?? null);
                    }
                }),
            KeyValue::make('route_parameters')
                ->label(__('filament-menu-builder::menu-builder.form.route_parameters'))
                ->visible(fn (Get $get): bool => self::getParameterNames($get('route')) !== [])
                ->addable(false)
                ->deletable(false)
                ->editableKeys(false)
                ->dehydrateStateUsing(fn (?array $state): ?array => array_filter($state ?? [], filled(...)) ?: null),
        ];
    }

    /**
     * @return list<string>
     */
    public static function getParameterNames(?string $route): array
    {
        if (blank($route)) {
            return [];
        }

        return Route::getRoutes()->getByName($route)?->parameterNames() ?? [];
    }
}
