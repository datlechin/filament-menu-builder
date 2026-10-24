<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder;

use Datlechin\FilamentMenuBuilder\Livewire\MenuItems;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMenuBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-menu-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasMigrations(['create_menus_table'])
            ->hasTranslations()
            ->hasViews(static::$name)
            ->hasInstallCommand(fn (InstallCommand $command) => $command
                ->publishConfigFile()
                ->publishMigrations()
                ->askToRunMigrations()
                ->askToStarRepoOnGitHub('datlechin/filament-menu-builder'));
    }

    public function packageBooted(): void
    {
        $this->publishMigrationStub('upgrade_menus_table_to_v2', 'filament-menu-builder-upgrade-migrations');
        $this->publishMigrationStub('make_translatable_columns_json', 'filament-menu-builder-translatable-migrations');

        FilamentAsset::register([
            AlpineComponent::make('menu-builder', __DIR__ . '/../resources/dist/filament-menu-builder.js'),
            Css::make('menu-builder', __DIR__ . '/../resources/css/filament-menu-builder.css')->loadedOnRequest(),
        ], 'datlechin/filament-menu-builder');

        Livewire::component('menu-builder-items', MenuItems::class);

        Blade::componentNamespace('Datlechin\\FilamentMenuBuilder\\View\\Components', static::$name);
    }

    protected function publishMigrationStub(string $name, string $tag): void
    {
        $this->publishes([
            __DIR__ . "/../database/migrations/{$name}.php.stub" => $this->app->databasePath('migrations/' . date('Y_m_d_His') . "_{$name}.php"),
        ], $tag);
    }
}
