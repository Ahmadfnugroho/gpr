<?php

namespace App\Providers\Filament;

use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rmsramos\Activitylog\ActivitylogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->emailVerification()
            ->profile()
            ->passwordReset()

            // === UI Enhancement - Compact Mode ===
            ->maxContentWidth('full') // Lebih kecil dari 'full'
            ->sidebarWidth('15rem') // Sidebar lebih kecil (default 20rem)
            ->sidebarCollapsibleOnDesktop()
            ->topNavigation() // Navigasi horizontal untuk menghemat space
            ->font('Inter') // Font lebih compact daripada Poppins
            ->defaultThemeMode(ThemeMode::Light) // Light mode lebih compact
            ->colors([
                'primary' => Color::Blue, // Warna lebih subtle
                'gray' => Color::Slate,
            ])
            // === Branding ===

            ->brandName('Global Photo Rental')
            ->favicon(asset('images/LOGO GPR.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->databaseNotifications()
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()

                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 2
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),

                ActivitylogPlugin::make()
                    ->navigationGroup('Activity Log')
                    ->authorize(
                        fn(User $user) => $user->hasRole('super_admin'),
                    )
            ])
            ->globalSearch(true) // Aktifkan global search
            ->globalSearchKeyBindings(['command+k', 'ctrl+k']) // Shortcut keyboard
            ->globalSearchDebounce('500ms'); // Debounce lebih cepat untuk responsivitas

    }

    public function boot(): void
    {
        //
    }
}
