<?php

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

trait HasLocationAction
{
    protected ?Collection $locationMenus = null;

    public function getLocationAction(): Action
    {
        return Action::make('locations')
            ->modalWidth(MaxWidth::Large)
            ->label(__('filament-menu-builder::menu-builder.actions.locations.label'))
            ->modalDescription(__('filament-menu-builder::menu-builder.actions.locations.description'))
            ->color('gray')
            ->fillForm(fn () => $this->getLocations()->map(fn ($location, $key) => [
                'location' => $location,
                'menu' => $this->getLocationMenus()->first(fn ($menu) => in_array($key, $menu->locations))->id ?? null,
            ])->all())
            ->action(function (array $data) {
                $menus = collect($data)->groupBy(fn ($value, $key) => $value, preserveKeys: true)
                    ->map(fn ($items) => $items->keys()->all());

                foreach ($this->getLocationMenus() as $model) {
                    $model->update([
                        'locations' => $menus->get($model->id),
                    ]);
                }
            })
            ->form(fn () => $this->getLocations()->map(
                fn ($location, $key) => Components\Grid::make(2)
                    ->statePath($key)
                    ->schema([
                        Components\TextInput::make('location')
                            ->label(__('filament-menu-builder::menu-builder.actions.locations.form.location.label'))
                            ->hiddenLabel($key !== $this->getLocations()->keys()->first())
                            ->disabled(),

                        Components\Select::make('menu')
                            ->label(__('filament-menu-builder::menu-builder.actions.locations.form.menu.label'))
                            ->searchable()
                            ->hiddenLabel($key !== $this->getLocations()->keys()->first())
                            ->options($this->getLocationMenus()->pluck('name', 'id')->all()),
                    ])
            )->all());
    }

    protected function getLocationMenus(): Collection
    {
        return $this->locationMenus ??= $this->getModel()::all();
    }

    protected function getLocations(): Collection
    {
        return collect(FilamentMenuBuilderPlugin::get()->getLocations());
    }
}
