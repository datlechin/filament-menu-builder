<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderServiceProvider;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\AdminPanelProvider;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\User;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Livewire's DataStore is a true singleton.
        // The Mechanism::register() uses app()->instance() which may not persist.
        // Re-bind the existing instance as a scoped singleton to prevent new instances.
        $dataStore = app(DataStore::class);
        $this->app->instance(DataStore::class, $dataStore);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentMenuBuilderServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/blog', fn () => 'blog')->name('blog.index');
        $router->get('/pages/{page}', fn () => 'page')->name('pages.show');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures');
        $this->loadMigrationsFrom($this->publishPackageMigrations());
    }

    protected function publishPackageMigrations(): string
    {
        $directory = sys_get_temp_dir() . '/filament-menu-builder-migrations';

        if (! is_dir($directory)) {
            mkdir($directory);
        }

        copy(__DIR__ . '/../database/migrations/create_menus_table.php.stub', "{$directory}/2026_01_01_000000_create_menus_table.php");

        return $directory;
    }
}
