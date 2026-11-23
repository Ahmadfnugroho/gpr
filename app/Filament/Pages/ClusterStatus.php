<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\KubernetesService;
use Filament\Pages\Page;

class ClusterStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Cluster Status';
    protected static ?string $title = 'Cluster & Container Status';
    protected static ?int $navigationSort = 100;
    protected static ?string $pollingInterval = '15s';
    protected static string $view = 'filament.pages.cluster-status';

    public string $environment = 'development';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->hasRole('super_admin');
    }

    public function selectEnvironment(string $environment): void
    {
        if (array_key_exists($environment, KubernetesService::ENVIRONMENTS)) {
            $this->environment = $environment;
        }
    }

    protected function getViewData(): array
    {
        return [
            'status' => app(KubernetesService::class)->status($this->environment),
        ];
    }
}