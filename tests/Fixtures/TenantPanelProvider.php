<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests\Fixtures;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Session\Middleware\StartSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->tenant(Team::class, slugAttribute: 'slug')
            ->plugin(FilamentMenuBuilderPlugin::make()->addLocations(['header' => 'Header', 'footer' => 'Footer']))
            ->middleware([StartSession::class])
            ->authMiddleware([Authenticate::class]);
    }
}
