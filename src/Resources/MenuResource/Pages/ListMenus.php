<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
