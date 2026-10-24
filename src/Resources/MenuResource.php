<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Resources;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\MenuBuilder;
use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\EditMenu;
use Datlechin\FilamentMenuBuilder\Resources\MenuResource\Pages\ListMenus;
use Datlechin\FilamentMenuBuilder\Support\TranslatableField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuResource extends Resource
{
    public static function getModel(): string
    {
        return MenuBuilder::menuModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-menu-builder::menu-builder.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-menu-builder::menu-builder.resource.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationLabel() ?? static::getPluralModelLabel();
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationSort();
    }

    public static function isScopedToTenant(): bool
    {
        return MenuBuilder::isTenantAware();
    }

    public static function getTenantOwnershipRelationshipName(): string
    {
        return 'tenant';
    }

    public static function form(Schema $schema): Schema
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $schema
            ->columns(1)
            ->components([
                TranslatableField::make('menu', 'name', fn (string $statePath): TextInput => TextInput::make($statePath)
                    ->label(__('filament-menu-builder::menu-builder.resource.name'))
                    ->required()
                    ->maxLength(255)),
                CheckboxList::make('locations')
                    ->label(__('filament-menu-builder::menu-builder.resource.locations'))
                    ->options($locations)
                    ->descriptions(fn (?Menu $record): array => static::describeTakenLocations($record))
                    ->visible($locations !== [])
                    ->afterStateHydrated(fn (CheckboxList $component, ?Menu $record) => $component->state($record?->locations->pluck('location')->all() ?? []))
                    ->saveRelationshipsUsing(fn (Menu $record, array $state) => $record->syncLocations($state))
                    ->dehydrated(false),
                Toggle::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible'))
                    ->default(true),
                ...FilamentMenuBuilderPlugin::get()->getMenuFields(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('locations')->withCount('items'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-menu-builder::menu-builder.resource.name'))
                    ->formatStateUsing(fn (Menu $record): string => $record->translated('name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locations.location')
                    ->label(__('filament-menu-builder::menu-builder.resource.locations'))
                    ->formatStateUsing(fn (string $state): string => $locations[$state] ?? $state)
                    ->badge()
                    ->placeholder(__('filament-menu-builder::menu-builder.resource.no_locations')),
                TextColumn::make('items_count')
                    ->label(__('filament-menu-builder::menu-builder.resource.items'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible'))
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->excludeAttributes(['items_count'])
                    ->after(fn (Menu $record, Menu $replica) => $record->copyItemsTo($replica))
                    ->successRedirectUrl(fn (Menu $replica): string => static::getUrl('edit', ['record' => $replica])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function describeTakenLocations(?Menu $record): array
    {
        $tenantKey = MenuBuilder::tenantForeignKey();

        return MenuBuilder::menuLocationModel()::query()
            ->with('menu')
            ->when($record, fn (Builder $query) => $query->where('menu_id', '!=', $record->getKey()))
            ->when($tenantKey, fn (Builder $query) => $query->where($tenantKey, $record?->getAttribute($tenantKey) ?? Filament::getTenant()?->getKey()))
            ->get()
            ->filter(fn ($location): bool => $location->menu !== null)
            ->mapWithKeys(fn ($location): array => [
                $location->location => __('filament-menu-builder::menu-builder.resource.location_taken', ['menu' => $location->menu->translated('name')]),
            ])
            ->all();
    }
}
