<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\Support\TranslatableFieldWrapper;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MenuResource extends Resource
{
    public static function getModel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getMenuModel();
    }

    public static function getNavigationLabel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationLabel() ?? Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationIcon(): string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationSort();
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationBadge(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function form(Schema $schema): Schema
    {
        $plugin = FilamentMenuBuilderPlugin::get();

        $nameField = TextInput::make('name')
            ->label(__('filament-menu-builder::menu-builder.resource.name.label'))
            ->required()
            ->columnSpan(3);

        if ($plugin->isTranslatable() && in_array('name', $plugin->getTranslatableMenuFields())) {
            $nameField = TranslatableFieldWrapper::wrap(
                TextInput::make('name')
                    ->label(__('filament-menu-builder::menu-builder.resource.name.label'))
                    ->required(),
                $plugin->getTranslatableLocales(),
            );
        }

        return $schema
            ->columns(1)
            ->components([
                Grid::make(4)
                    ->schema([
                        $nameField,

                        ToggleButtons::make('is_visible')
                            ->grouped()
                            ->boolean(
                                trueLabel: __('filament-menu-builder::menu-builder.resource.is_visible.visible'),
                                falseLabel: __('filament-menu-builder::menu-builder.resource.is_visible.hidden'),
                            )
                            ->colors([
                                1 => 'primary',
                                0 => 'danger',
                            ])
                            ->required()
                            ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                            ->default(true),
                    ]),

                Group::make()
                    ->visible(fn (Component $component) => $component->evaluate(FilamentMenuBuilderPlugin::get()->getMenuFields()) !== [])
                    ->schema(FilamentMenuBuilderPlugin::get()->getMenuFields()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('menuItems'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => is_array($state) ? ($state[app()->getLocale()] ?? collect($state)->first() ?? '') : (string) $state)
                    ->label(__('filament-menu-builder::menu-builder.resource.name.label')),
                TextColumn::make('locations.location')
                    ->label(__('filament-menu-builder::menu-builder.resource.locations.label'))
                    ->default(__('filament-menu-builder::menu-builder.resource.locations.empty'))
                    ->color(fn (string $state) => array_key_exists($state, $locations) ? 'primary' : 'gray')
                    ->formatStateUsing(fn (string $state) => $locations[$state] ?? $state)
                    ->limitList(2)
                    ->sortable()
                    ->badge(),
                TextColumn::make('menu_items_count')
                    ->label(__('filament-menu-builder::menu-builder.resource.items.label'))
                    ->icon('heroicon-o-link')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                    ->sortable()
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MenuResource\Pages\ListMenus::route('/'),
            'edit' => MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
