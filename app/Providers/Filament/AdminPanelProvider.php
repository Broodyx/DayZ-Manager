<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ConfigurationWizard;
use App\Filament\Pages\LogAnalyzer;
use App\Filament\Pages\MapEditor;
use App\Filament\Resources\ProjectResource;
use App\Filament\Widgets\DayzOverview;
use App\Filament\Widgets\ItemCategoriesChart;
use App\Filament\Widgets\RecentProjects;
use App\Filament\Widgets\ServerCommandCenter;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
        $serverItems = [
            NavigationItem::make('Servery')
                ->label('Servery')
                ->icon('heroicon-o-server-stack')
                ->url(fn (): string => ProjectResource::getUrl())
                ->sort(1)
                ->group('Správa serveru'),
        ];
        $serverItems[] = NavigationItem::make('map-editor')
            ->label('Mapový editor')
            ->icon('heroicon-o-map')
            ->url(fn (): string => MapEditor::getUrl())
            ->sort(3)
            ->group('Správa serveru');
        $serverItems[] = NavigationItem::make('configuration-wizard')
            ->label('Nastavit server')
            ->icon('heroicon-o-sparkles')
            ->url(fn (): string => ConfigurationWizard::getUrl())
            ->sort(2)
            ->group('Správa serveru');
        $serverItems[] = NavigationItem::make('log-analyzer')
            ->label('Log analyzátor')
            ->icon('heroicon-o-document-magnifying-glass')
            ->url(fn (): string => LogAnalyzer::getUrl())
            ->sort(4)
            ->group('Diagnostika');

        return $panel->default()->id('admin')->path('admin')->login()
            ->favicon(secure_asset('favicon.svg'))
            ->brandName('DayZ Manager')->colors(['primary' => Color::Lime])
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true, isForced: true)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.admin-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#84cc16"><link rel="apple-touch-icon" href="/icon-192.png">',
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.sidebar-user-menu')->render(),
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
                fn (): string => auth()->check() ? view('filament.server-workspace-nav')->render() : '',
            )
            ->renderHook(
                PanelsRenderHook::CONTENT_START,
                fn (): string => (session('status') || session('status_warning')) ? view('filament.flash-status')->render() : '',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationItems($serverItems)
            ->navigationGroups([
                NavigationGroup::make()->label('Správa serveru'),
                NavigationGroup::make()->label('Diagnostika'),
                NavigationGroup::make()->label('Historie a zálohy'),
                NavigationGroup::make()->label('Administrace'),
            ])
            ->pages([Pages\Dashboard::class, MapEditor::class, LogAnalyzer::class])
            ->widgets([
                ServerCommandCenter::class,
                DayzOverview::class,
                ItemCategoriesChart::class,
                RecentProjects::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
                AuthenticateSession::class, ShareErrorsFromSession::class, VerifyCsrfToken::class,
                SubstituteBindings::class, DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])->authMiddleware([Authenticate::class]);
    }
}
