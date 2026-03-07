<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

trait HasLocationAction
{
    protected ?Collection $menus = null;

    protected ?Collection $menuLocations = null;

    public function getLocationAction(): Action
    {
        return Action::make('locations')
            ->label(__('filament-menu-builder::menu-builder.actions.locations.label'))
            ->modalHeading(__('filament-menu-builder::menu-builder.actions.locations.heading'))
            ->modalDescription(__('filament-menu-builder::menu-builder.actions.locations.description'))
            ->modalSubmitActionLabel(__('filament-menu-builder::menu-builder.actions.locations.submit'))
            ->modalWidth(Width::Large)
            ->modalSubmitAction($this->getRegisteredLocations()->isEmpty() ? false : null)
            ->color('gray')
            ->fillForm(fn () => $this->getRegisteredLocations()->map(fn ($location, $key) => [
                'location' => $location,
                'menu' => $this->getMenuLocations()->where('location', $key)->first()?->menu_id,
            ])->all())
            ->action(function (array $data) {
                $locations = collect($data)
                    ->map(fn ($item) => $item['menu'] ?? null)
                    ->all();

                $this->getMenuLocations()
                    ->pluck('location')
                    ->diff($this->getRegisteredLocations()->keys())
                    ->each(fn ($location) => $this->getMenuLocations()->where('location', $location)->each->delete());

                foreach ($locations as $location => $menu) {
                    if (! $menu) {
                        $this->getMenuLocations()->where('location', $location)->each->delete();

                        continue;
                    }

                    FilamentMenuBuilderPlugin::get()->getMenuLocationModel()::updateOrCreate(
                        ['location' => $location],
                        ['menu_id' => $menu],
                    );
                }

                Notification::make()
                    ->title(__('filament-menu-builder::menu-builder.notifications.locations.title'))
                    ->success()
                    ->send();
            })
            ->form($this->getRegisteredLocations()->map(
                fn ($location, $key) => Grid::make(2)
                    ->statePath($key)
                    ->schema([
                        TextInput::make('location')
                            ->label(__('filament-menu-builder::menu-builder.actions.locations.form.location.label'))
                            ->hiddenLabel($key !== $this->getRegisteredLocations()->keys()->first())
                            ->disabled(),

                        Select::make('menu')
                            ->label(__('filament-menu-builder::menu-builder.actions.locations.form.menu.label'))
                            ->searchable()
                            ->hiddenLabel($key !== $this->getRegisteredLocations()->keys()->first())
                            ->options($this->getMenus()->pluck('name', 'id')->all()),
                    ]),
            )->all() ?: [
                EmptyState::make(__('filament-menu-builder::menu-builder.actions.locations.empty.heading'))
                    ->icon('heroicon-o-x-mark'),
            ]);
    }

    protected function getMenus(): Collection
    {
        return $this->menus ??= FilamentMenuBuilderPlugin::get()->getMenuModel()::all();
    }

    protected function getMenuLocations(): Collection
    {
        return $this->menuLocations ??= FilamentMenuBuilderPlugin::get()->getMenuLocationModel()::all();
    }

    protected function getRegisteredLocations(): Collection
    {
        return collect(FilamentMenuBuilderPlugin::get()->getLocations());
    }
}
