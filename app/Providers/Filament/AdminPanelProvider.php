<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MapEditor;
use App\Filament\Resources\ProjectResource;
use App\Filament\Widgets\DayzOverview;
use App\Filament\Widgets\ItemCategoriesChart;
use App\Filament\Widgets\RecentProjects;
use App\Models\Project;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
                ->group('DayZ konfigurace'),
            ...(auth()->check()
            ? Project::query()->where('user_id', auth()->id())->orderBy('name')->get()->map(
                fn (Project $server): NavigationItem => NavigationItem::make('server-'.$server->id)
                    ->label($server->name)
                    ->icon('heroicon-o-server')
                    ->url(fn (): string => ProjectResource::getUrl('configuration', ['record' => $server]))
                    ->group('DayZ konfigurace')
                    ->parentItem('Servery'),
            )->all()
            : []),
        ];
        $serverItems[] = NavigationItem::make('map-editor')
            ->label('Mapa Chernarus')
            ->icon('heroicon-o-map')
            ->url(fn (): string => MapEditor::getUrl())
            ->group('DayZ konfigurace');

        return $panel->default()->id('admin')->path('admin')->login()
            ->favicon(asset('favicon.svg'))
            ->brandName('DayZ Manager')->colors(['primary' => Color::Lime])
            ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(true, isForced: true)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.admin-theme')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationItems($serverItems)
            ->pages([Pages\Dashboard::class, MapEditor::class])
            ->widgets([
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
