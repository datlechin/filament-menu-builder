<?php

namespace Datlechin\FilamentMenuBuilder\Concerns;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

trait HasLocationAction
{
    protected ?Collection $menuLocations = null;

    public function getLocationAction(): Action
    {
        return Action::make('locations')
            ->label(__('filament-menu-builder::menu-builder.actions.locations.label'))
            ->modalHeading(__('filament-menu-builder::menu-builder.actions.locations.heading'))
            ->modalDescription(__('filament-menu-builder::menu-builder.actions.locations.description'))
            ->modalSubmitActionLabel(__('filament-menu-builder::menu-builder.actions.locations.submit'))
            ->modalWidth(MaxWidth::Large)
            ->color('gray')
            ->fillForm(fn () => $this->getLocations()->map(fn ($location, $key) => [
                'location' => $location,
                'menu' => $this->getMenuLocations()->where('location', $key)->first()?->menu_id,
            ])->all())
            ->action(function (array $data) {
                $locations = collect($data)
                    ->map(fn ($item) => $item['menu'] ?? null)
                    ->all();

                $this->getMenuLocations()
                    ->pluck('location')
                    ->diff($this->getLocations()->keys())
                    ->each(fn ($location) => $this->getMenuLocations()->where('location', $location)->each->delete());

                foreach ($locations as $location => $menu) {
                    if (! $menu) {
                        $this->getMenuLocations()->where('location', $location)->each->delete();

                        continue;
                    }

                    MenuLocation::updateOrCreate(
                        ['location' => $location],
                        ['menu_id' => $menu]
                    );
                }

                Notification::make()
                    ->title(__('filament-menu-builder::menu-builder.notifications.locations.title'))
                    ->success()
                    ->send();
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
                            ->options($this->getModel()::all()->pluck('name', 'id')->all()),
                    ])
            )->all());
    }

    protected function getMenuLocations(): Collection
    {
        return $this->menuLocations ??= MenuLocation::all();
    }

    protected function getLocations(): Collection
    {
        return collect(FilamentMenuBuilderPlugin::get()->getLocations());
    }
}
