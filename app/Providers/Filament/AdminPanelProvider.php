<?php
namespace App\Providers\Filament;
use App\Http\Middleware\LogUserAccess;
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
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfile;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class)
            // ==== BRANDING ====
            ->brandName('SIMARSIP Keuangan')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo-rajawali.png'))
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // ==== WARNA TEMA (identitas korporat PT PG Rajawali I) ====
            ->colors([
                'primary' => Color::hex('#1B5FA8'),
                'gray' => Color::Slate,
                'success' => Color::hex('#2E8B57'),
                'warning' => Color::hex('#F5A623'),
                'danger' => Color::hex('#D32F2F'),
                'info' => Color::hex('#0F3D6E'),
            ])
            ->navigationGroups([
                'Master Data',
                'Administrasi',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LogUserAccess::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // ==== CSS CUSTOM (font, sudut membulat, bayangan) ====
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.custom-styles')
            )
            // ==== FOOTER ====
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.footer')
            );
    }
}