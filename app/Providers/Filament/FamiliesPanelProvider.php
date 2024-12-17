<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Settings\Resources\CallsDiariesResource;
use App\Filament\Clusters\Settings\Resources\TimeSheetsResource;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CalendarWidget;
//use App\Http\Middleware\UpgradeToHttpsUnderNgrokMiddleware;
use App\Filament\Widgets\GoldListWidget;
use Awcodes\FilamentGravatar\GravatarPlugin;
use Awcodes\FilamentGravatar\GravatarProvider;
use Blade;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Notifications\Livewire\Notifications;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\Banner\BannerPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class FamiliesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('families')
            ->path('admin')
            ->login()
            ->profile()
            ->maxContentWidth('full')
            ->defaultAvatarProvider(GravatarProvider::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('שידוכון')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->topNavigation()
//            ->navigationGroups([])
            ->widgets([
                CalendarWidget::class,
                Widgets\AccountWidget::class,
                GoldListWidget::class,
            ])
            ->spa()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
//                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
//                UpgradeToHttpsUnderNgrokMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                GravatarPlugin::make()
                    ->default('robohash')
                    ->size(200),
                BannerPlugin::make()
                    ->disableBannerManager()
                    ->persistsBannersInDatabase()
                    ->bannerManagerAccessPermission('banner-manager')
                    ->navigationLabel('באנרים'),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                //                    ->myProfile(),

                FilamentFullCalendarPlugin::make()
//                    ->schedulerLicenseKey()
                    ->selectable()
                    ->editable()
//                    ->timezone()
//                    ->locale()
//                    ->plugins()
//                    ->config()
            ])
            ->bootUsing(function () {
                Notifications::alignment(Alignment::Center);
                Notifications::verticalAlignment(VerticalAlignment::Start);
            })
            ->databaseNotifications()
            ->renderHook('panels:body.end', fn (): string => Blade::render('@livewire(x-impersonate::banner)'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@livewire(\'calls-box\')'),
            )
            ->renderHook(PanelsRenderHook::USER_MENU_AFTER,
                fn (): string => Blade::render('@livewire(\'time-box\')'),
            )
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@livewire(\'discussion-top-bar\')'),
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('יומן שעות')
                    ->icon('iconsax-bul-clock-1')
                    ->url(fn (): string => TimeSheetsResource::getUrl()),

                MenuItem::make()
                    ->label('יומן שיחות')
                    ->icon('iconsax-bul-call')
                    ->url(fn (): string => CallsDiariesResource::getUrl()),
            ])
            ->viteTheme('resources/css/filament/families/theme.css');
    }
}
