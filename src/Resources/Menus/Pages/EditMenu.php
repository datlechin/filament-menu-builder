<?php

namespace Datlechin\FilamentMenuBuilder\Resources\Menus\Pages;

use Datlechin\FilamentMenuBuilder\Resources\Menus\MenuResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
