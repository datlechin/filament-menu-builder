<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                TextInput::make('name')
                    ->label('Tên')
                    ->required(),
                Radio::make('location')
                    ->visible(fn() => FilamentMenuBuilderPlugin::get()->getLocations())
                    ->label('Vị trí')
                    ->helperText('Chọn vị trí hiển thị menu.')
                    ->options(FilamentMenuBuilderPlugin::get()->getLocations()),
                Checkbox::make('is_visible')
                    ->label('Hiển thị')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Tên'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Hiển thị')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus::route('/'),
            'edit' => \Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
