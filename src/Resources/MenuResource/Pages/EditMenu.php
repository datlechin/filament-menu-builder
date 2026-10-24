<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditMenu extends EditRecord
{
    public static function getResource(): string
    {
        return FilamentMenuBuilderPlugin::get()->getResource();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->schema([
                        Livewire::make('menu-builder-items', ['menu' => $this->getRecord()])
                            ->key('menu-builder-items')
                            ->columnSpan(['lg' => 2]),
                        Section::make(__('filament-menu-builder::menu-builder.resource.settings'))
                            ->schema([$this->getFormContentComponent()]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
