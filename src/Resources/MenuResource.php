<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    public static function form(Form $form): Form
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $form
            ->columns(1)
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-menu-builder::menu-builder.resource.name.label'))
                    ->required(),
                ToggleButtons::make('locations')
                    ->multiple()
                    ->inline()
                    ->reactive()
                    ->visible(fn (string $context) => $context === 'edit' && $locations)
                    ->label(__('filament-menu-builder::menu-builder.resource.locations.label'))
                    ->afterStateHydrated(fn (Menu $menu, Set $set) => $set('locations', $menu->locations->pluck('location')))
                    ->helperText(__('filament-menu-builder::menu-builder.resource.locations.description'))
                    ->hintActions([
                        Action::make(__('filament-menu-builder::menu-builder.resource.locations.actions.select_all'))
                            ->action(fn (Set $set) => $set('locations', array_keys($locations)))
                            ->visible(fn (Get $get) => count($get('locations')) !== count($locations)),

                        Action::make(__('filament-menu-builder::menu-builder.resource.locations.actions.deselect_all'))
                            ->action(fn (Set $set) => $set('locations', []))
                            ->visible(fn (Get $get) => count($get('locations')) === count($locations)),
                    ])
                    ->options($locations),
                Toggle::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                    ->default(true),
                Group::make()
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
                    ->color(fn (string $state) => $state !== $default ? 'primary' : 'gray')
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
