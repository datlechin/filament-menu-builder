<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests;

use Datlechin\FilamentMenuBuilder\Tests\Fixtures\AdminPanelProvider;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\Team;
use Datlechin\FilamentMenuBuilder\Tests\Fixtures\TenantPanelProvider;
use Illuminate\Database\Eloquent\Model;

class TenancyTestCase extends TestCase
{
    protected function setUp(): void
    {
        // Filament registers the tenancy scope once per model class, bound to the panel of the test that booted first.
        Model::clearBootedModels();

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...array_diff(parent::getPackageProviders($app), [AdminPanelProvider::class]),
            TenantPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('filament-menu-builder.tenant_model', Team::class);
    }
}
