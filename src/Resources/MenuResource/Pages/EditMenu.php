<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Datlechin\FilamentMenuBuilder\Resources\MenuResource;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected static string $view = 'filament-menu-builder::edit-record';

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
}
