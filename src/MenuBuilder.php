<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Datlechin\FilamentMenuBuilder\Models\Menu;
use Datlechin\FilamentMenuBuilder\Models\MenuItem;
use Datlechin\FilamentMenuBuilder\Models\MenuLocation;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class MenuBuilder
{
    /**
     * @return class-string<Menu>
     */
    public static function menuModel(): string
    {
        return config('filament-menu-builder.models.menu', Menu::class);
    }

    /**
     * @return class-string<MenuItem>
     */
    public static function menuItemModel(): string
    {
        return config('filament-menu-builder.models.menu_item', MenuItem::class);
    }

    /**
     * @return class-string<MenuLocation>
     */
    public static function menuLocationModel(): string
    {
        return config('filament-menu-builder.models.menu_location', MenuLocation::class);
    }

    public static function table(string $name): string
    {
        return config("filament-menu-builder.tables.{$name}", $name);
    }

    /**
     * @return list<string>
     */
    public static function locales(): array
    {
        return array_values(config('filament-menu-builder.translations.locales', []));
    }

    public static function isTranslatable(): bool
    {
        return self::locales() !== [];
    }

    /**
     * @return list<string>
     */
    public static function translatableAttributes(string $model): array
    {
        if (! self::isTranslatable()) {
            return [];
        }

        return array_values(config("filament-menu-builder.translations.attributes.{$model}", []));
    }

    /**
     * @return class-string<Model>|null
     */
    public static function tenantModel(): ?string
    {
        return config('filament-menu-builder.tenant_model');
    }

    public static function isTenantAware(): bool
    {
        return self::tenantModel() !== null;
    }

    public static function tenantForeignKey(): ?string
    {
        $tenantModel = self::tenantModel();

        return $tenantModel === null ? null : (new $tenantModel)->getForeignKey();
    }

    public static function cache(): ?Repository
    {
        if (! config('filament-menu-builder.cache.enabled', true)) {
            return null;
        }

        return Cache::store(config('filament-menu-builder.cache.store'));
    }
}
