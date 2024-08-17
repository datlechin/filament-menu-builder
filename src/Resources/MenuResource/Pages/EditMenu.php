<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditMenu extends EditRecord
{
    protected static string $view = 'filament-menu-builder::edit-record';

    public static function getResource(): string
    {
        return FilamentMenuBuilderPlugin::get()->getResource();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema($form->getComponents()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $registeredLocations = FilamentMenuBuilderPlugin::get()->getLocations();

        $locations = collect(Arr::pull($data, 'locations') ?: [])
            ->filter(fn ($location) => array_key_exists($location, $registeredLocations))
            ->values();

        /** @var Menu */
        $record = parent::handleRecordUpdate($record, $data);

        $record->locations()
            ->whereNotIn('location', $locations)
            ->delete();

        foreach ($locations as $location) {
            $record->locations()->firstOrCreate([
                'location' => $location,
            ]);
        }

        return $record;
    }
}
