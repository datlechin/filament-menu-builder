<?php

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

trait HasLocationAction
{
    public function getLocationAction(): Action
    {
        return Action::make('locations')
            ->modalWidth(MaxWidth::Large)
            ->label(__('filament-menu-builder::menu-builder.actions.locations.label'))
            ->modalDescription(__('filament-menu-builder::menu-builder.actions.locations.description'))
            ->color('gray')
            ->fillForm(fn () => $this->getLocations()->map(fn ($location, $key) => [
                'location' => $location,
                'menu' => $this->getModel()::location($key)->id ?? null,
            ])->all())
            ->action(function (array $data) {
                $menus = collect($data)->groupBy(fn ($value, $key) => $value, preserveKeys: true)
                    ->map(fn ($items) => $items->keys()->all());

                $models = $this->getModel()::all();

                foreach ($models as $model) {
                    $model->update([
                        'locations' => $menus->get($model->id),
                    ]);
                }
            })
            ->form(function () {
                $menus = $this->getModel()::all();

                return $this->getLocations()->map(function ($location, $key) use ($menus) {
                    return Components\Grid::make(2)
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
                                ->options($menus->pluck('name', 'id')->toArray())
                                ->required(),
                        ]);
                })->all();
            });
    }

    protected function getLocations(): Collection
    {
        return collect(FilamentMenuBuilderPlugin::get()->getLocations());
    }
}
