<?php

namespace Datlechin\FilamentMenuBuilder\Resources\Menus;

use Datlechin\FilamentMenuBuilder\Resources\Menus\Pages\CreateMenu;
use Datlechin\FilamentMenuBuilder\Resources\Menus\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Resources\Menus\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Resources\Menus\Schemas\MenuForm;
use Datlechin\FilamentMenuBuilder\Resources\Menus\Tables\MenusTable;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
