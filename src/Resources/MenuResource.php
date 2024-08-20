<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Forms\Components;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function getModel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getMenuModel();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Components\Grid::make(4)
                    ->schema([
                        Components\TextInput::make('name')
                            ->label(__('filament-menu-builder::menu-builder.resource.name.label'))
                            ->required()
                            ->columnSpan(3),

                        Components\ToggleButtons::make('is_visible')
                            ->grouped()
                            ->options([
                                true => __('filament-menu-builder::menu-builder.resource.is_visible.visible'),
                                false => __('filament-menu-builder::menu-builder.resource.is_visible.hidden'),
                            ])
                            ->colors([
                                true => 'primary',
                                false => 'danger',
                            ])
                            ->required()
                            ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                            ->default(true),
                    ]),

                Components\Group::make()
                    ->visible(fn (Component $component) => $component->evaluate(FilamentMenuBuilderPlugin::get()->getMenuFields()) !== [])
                    ->schema(FilamentMenuBuilderPlugin::get()->getMenuFields()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('filament-menu-builder::menu-builder.resource.name.label')),
                Tables\Columns\TextColumn::make('locations.location')
                    ->default($default = __('filament-menu-builder::menu-builder.resource.locations.empty'))
                    ->color(fn (string $state) => array_key_exists($state, $locations) ? 'primary' : 'gray')
                    ->formatStateUsing(fn (string $state) => $locations[$state] ?? $state)
                    ->limitList(2)
                    ->badge(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
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
