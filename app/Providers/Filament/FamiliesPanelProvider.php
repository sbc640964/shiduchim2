<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Settings\Resources\Banners\Utilities\BannerMiddleware;
use App\Filament\Widgets\NewCalendarWidget;
use App\Filament\Widgets\OpenProposalsOverview;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Clusters\Reports\Pages\ReportsPage\Widgets\ReportsProposalsTableWidget;
use App\Filament\Clusters\Settings\Resources\CallsDiaries\CallsDiariesResource;
use App\Filament\Clusters\Settings\Resources\TimeSheets\TimeSheetsResource;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\GoldListWidget;
use Awcodes\Gravatar\GravatarPlugin;
use Awcodes\Gravatar\GravatarProvider;
use Blade;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Livewire\Notifications;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
//use Kenepa\Banner\BannerPlugin;
//use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

//use App\Http\Middleware\UpgradeToHttpsUnderNgrokMiddleware;

class FamiliesPanelProvider extends PanelProvider
{
    public function boot()
    {
        Table::configureUsing(modifyUsing: function (Table $table) {
            $table->paginationPageOptions([10, 25, 50, 100]);
        });
    }


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
            ->favicon(asset('storage/favicon.png'))
            ->colors([
                'primary' => [
                    50 => 'oklch(81.355% 0.05267 344.73)',
                    100 => 'oklch(77.768% 0.06309 344.84)',
                    200 => 'oklch(70.916% 0.08453 345.35)',
                    300 => 'oklch(63.842% 0.1074 345.49)',
                    400 => 'oklch(57.115% 0.13162 345.98)',
                    500 => 'oklch(50.608% 0.11732 346)',
                    600 => 'oklch(43.749% 0.09879 345.18)',
                    700 => 'oklch(35.696% 0.07779 344.36)',
                    800 => 'oklch(27.167% 0.05526 342.76)',
                    900 => 'oklch(17.88% 0.02905 341.47)',
                    950 => 'oklch(12.573% 0.01376 340.32)',
                ],
            ])
            ->brandName('שידוכון')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight(40)
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class
            ])
            ->topNavigation()
//            ->navigationGroups([])
            ->widgets([
//                CalendarWidget::class,
//                Widgets\AccountWidget::class,
                OpenProposalsOverview::class,
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
                BannerMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                GravatarPlugin::make()
                    ->default('robohash')
                    ->size(200),
//                BannerPlugin::make()
//                    ->disableBannerManager()
//                    ->persistsBannersInDatabase()
//                    ->bannerManagerAccessPermission('banner-manager')
//                    ->navigationLabel('באנרים'),
                FilamentShieldPlugin::make()
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

//                FilamentFullCalendarPlugin::make()
////                    ->schedulerLicenseKey()
//                    ->selectable()
//                    ->editable()
////                    ->timezone()
////                    ->locale()
////                    ->plugins()
////                    ->config()
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
            ->renderHook(TablesRenderHook::TOOLBAR_START, function () {
                return ReportsProposalsTableWidget::getTabsElement();
            }, ReportsProposalsTableWidget::class )
//            ->renderHook(PanelsRenderHook::PAGE_END, function () {
//                return Blade::render('@livewire(\'active-call-drawer\')');
//            })
            ->userMenuItems([
                Action::make('work_diary')
                    ->label('יומן שעות')
                    ->icon('iconsax-bul-clock-1')
                    ->url(fn (): string => TimeSheetsResource::getUrl()),

                Action::make('call_diary')
                    ->label('יומן שיחות')
                    ->icon('iconsax-bul-call')
                    ->url(fn (): string => CallsDiariesResource::getUrl()),
            ])
            ->assets(app()->environment('local') && ! app()->runningInConsole()  ? [
                Js::make('livewire-hot-reload', Vite::asset('resources/js/livewire-hot-reload.js'))->module()
            ]: [])
            ->viteTheme('resources/css/filament/families/theme.css');
    }
}
