<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Tests\Fixtures;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Datlechin\FilamentMenuBuilder\ItemTypes\LinkItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\ModelItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\RouteItemType;
use Datlechin\FilamentMenuBuilder\ItemTypes\TextItemType;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(
                FilamentMenuBuilderPlugin::make()
                    ->addLocations([
                        'header' => 'Header Navigation',
                        'footer' => 'Footer Navigation',
                    ])
                    ->itemTypes([
                        LinkItemType::make(),
                        TextItemType::make(),
                        RouteItemType::make()->routes(['home' => 'Home', 'pages.show' => 'Page']),
                        ModelItemType::make(Page::class)->titleAttribute('title'),
                    ]),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
