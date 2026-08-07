<?php

namespace App\Providers\Filament;

use App\Filament\Pages\FtpExplorer;
use App\Filament\Pages\LogAnalyzer;
use App\Filament\Pages\MapEditor;
use App\Filament\Widgets\DayzOverview;
use App\Filament\Widgets\ItemCategoriesChart;
use App\Filament\Widgets\RecentProjects;
use App\Filament\Widgets\ServerCommandCenter;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->default()->id('admin')->path('admin')->login()
            ->favicon(secure_asset('favicon.svg'))
            ->brandName('DayZ Manager')->colors(['primary' => Color::Lime])
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true, isForced: true)
            // The one horizontal bar in filament.main-nav (PAGE_START below) replaces
            // Filament's own sidebar/topbar nav entirely, so there is nothing left for
            // Filament's own nav array to drive — turning it off also removes the
            // per-request DB lookups its NavigationItem url() closures used to run on
            // every single page.
            ->navigation(false)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.admin-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#84cc16"><link rel="apple-touch-icon" href="/icon-192.png">',
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => "<script>if('serviceWorker' in navigator){addEventListener('load',()=>navigator.serviceWorker.register('/sw.js'));}</script>",
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.global-confirm-dialog')->render(),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => auth()->check() ? view('filament.main-nav')->render() : '',
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn (): string => (session('status') || session('status_warning')) ? view('filament.flash-status')->render() : '',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class, MapEditor::class, LogAnalyzer::class, FtpExplorer::class])
            ->widgets([
                ServerCommandCenter::class,
                DayzOverview::class,
                ItemCategoriesChart::class,
                RecentProjects::class,
            ])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class,
                SubstituteBindings::class, DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])->authMiddleware([Authenticate::class]);
    }
}
