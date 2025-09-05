<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class CustomNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add custom notification component to Filament admin panel
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => view('components.custom-notification')->render()
        );
    }

    public function register(): void
    {
        //
    }
}
