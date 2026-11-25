<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\KubernetesService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class ClusterStatus extends Page
{
    protected static ?string $pollingInterval = '15s';

    protected static string $view = 'filament.pages.cluster-status';

    /**
     * Read-only commands the terminal will accept. Anything else is refused
     * before a request ever reaches the apiserver.
     */
    private const ALLOWED_COMMANDS = [
        'ls', 'cat', 'head', 'tail', 'grep', 'find', 'wc', 'stat', 'du', 'df',
        'free', 'ps', 'id', 'whoami', 'date', 'uname', 'uptime', 'echo',
        'php', 'mc', 'nslookup',
    ];

    private const BLOCKED_PATTERNS = [
        'rm ', 'mv ', 'dd ', 'mkfs', 'shutdown', 'reboot', 'kill', 'pkill',
        'killall', 'chmod', 'chown', 'truncate', '>', '<', '|', ';', '&&',
        '||', '$(', '`', '..', '/var/run', '/proc', '.env', 'password',
        'secret', 'token', 'aws_', 'minio_root',
    ];

    /** Deployments we refuse to scale to zero from the panel. */
    private const PROTECTED_DEPLOYMENTS = ['gpr-backend'];

    /** Replica count restored by "Start" for workloads that normally run >1. */
    private const DEFAULT_REPLICAS = ['gpr-backend' => 2, 'gpr-frontend' => 2];

    public string $environment = 'development';

    public ?string $panelMode = null;

    public string $panelTitle = '';

    public string $panelBody = '';

    public string $panelPod = '';

    public string $panelContainer = '';

    public string $command = '';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->hasRole('super_admin');
    }

    public function selectEnvironment(string $environment): void
    {
        if (array_key_exists($environment, KubernetesService::ENVIRONMENTS)) {
            $this->environment = $environment;
            $this->closePanel();
        }
    }

    public function restart(string $deployment): void
    {
        $this->guard();

        try {
            app(KubernetesService::class)->restart($this->environment, $deployment);
            $this->audit('restart', $deployment);
            Notification::make()->title("Restart {$deployment} dikirim")->success()->send();
        } catch (\Throwable $e) {
            $this->fail('restart', $deployment, $e);
        }
    }

    public function stop(string $deployment): void
    {
        $this->guard();

        if (in_array($deployment, self::PROTECTED_DEPLOYMENTS, true)) {
            Notification::make()
                ->title("{$deployment} tidak boleh dihentikan dari panel")
                ->danger()
                ->send();

            return;
        }

        try {
            app(KubernetesService::class)->scale($this->environment, $deployment, 0);
            $this->audit('stop', $deployment);
            Notification::make()->title("{$deployment} di-scale ke 0")->warning()->send();
        } catch (\Throwable $e) {
            $this->fail('stop', $deployment, $e);
        }
    }

    public function start(string $deployment): void
    {
        $this->guard();

        try {
            app(KubernetesService::class)->scale(
                $this->environment,
                $deployment,
                self::DEFAULT_REPLICAS[$deployment] ?? 1
            );
            $this->audit('start', $deployment);
            Notification::make()->title("{$deployment} di-scale up")->success()->send();
        } catch (\Throwable $e) {
            $this->fail('start', $deployment, $e);
        }
    }

    public function showLogs(string $pod, string $container): void
    {
        $this->guard();

        try {
            $this->panelBody = app(KubernetesService::class)->logs($this->environment, $pod, $container);
            $this->openPanel('Logs', "{$pod} · {$container}", $pod, $container);
            $this->audit('logs', $pod, $container);
        } catch (\Throwable $e) {
            $this->fail('logs', $pod, $e);
        }
    }

    public function showTerminal(string $pod, string $container): void
    {
        $this->guard();

        $this->command = '';
        $this->panelBody = '';
        $this->openPanel('Terminal', "{$pod} · {$container}", $pod, $container);
    }

    public function runCommand(): void
    {
        $this->guard();

        $argv = $this->parseCommand($this->command);

        if ($argv === null) {
            Notification::make()
                ->title('Perintah ditolak')
                ->body('Hanya perintah baca dari allowlist yang diizinkan.')
                ->danger()
                ->send();

            return;
        }

        try {
            $output = app(KubernetesService::class)->exec(
                $this->environment,
                $this->panelPod,
                $this->panelContainer,
                $argv
            );
            $this->panelBody = trim($this->command)."\n\n".$output;
            $this->audit('exec', $this->panelPod, $this->panelContainer, $argv[0]);
        } catch (\Throwable $e) {
            $this->fail('exec', $this->panelPod, $e);
        }
    }

    public function closePanel(): void
    {
        $this->panelMode = null;
        $this->panelTitle = '';
        $this->panelBody = '';
        $this->panelPod = '';
        $this->panelContainer = '';
        $this->command = '';
    }

    protected function getViewData(): array
    {
        return ['status' => app(KubernetesService::class)->status($this->environment)];
    }

    /**
     * @return list<string>|null
     */
    private function parseCommand(string $command): ?array
    {
        $command = trim($command);

        if ($command === '' || strlen($command) > 200) {
            return null;
        }

        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (stripos($command, $pattern) !== false) {
                return null;
            }
        }

        $argv = preg_split('/\s+/', $command) ?: [];

        if ($argv === [] || ! in_array($argv[0], self::ALLOWED_COMMANDS, true)) {
            return null;
        }

        return $argv;
    }

    private function openPanel(string $title, string $subtitle, string $pod, string $container): void
    {
        $this->panelMode = $title === 'Terminal' ? 'terminal' : 'logs';
        $this->panelTitle = "{$title} — {$subtitle}";
        $this->panelPod = $pod;
        $this->panelContainer = $container;
    }

    private function guard(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    private function audit(string $action, string $target, string $container = '', string $detail = ''): void
    {
        Log::info('[cluster-status] action', [
            'user_id' => auth()->id(),
            'environment' => $this->environment,
            'action' => $action,
            'target' => $target,
            'container' => $container,
            'detail' => $detail,
        ]);
    }

    private function fail(string $action, string $target, \Throwable $e): void
    {
        Log::warning('[cluster-status] failed', [
            'user_id' => auth()->id(),
            'environment' => $this->environment,
            'action' => $action,
            'target' => $target,
            'error' => $e->getMessage(),
        ]);

        Notification::make()
            ->title("{$action} gagal")
            ->body($e->getMessage())
            ->danger()
            ->send();
    }
}