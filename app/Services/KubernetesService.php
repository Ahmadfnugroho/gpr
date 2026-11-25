<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KubernetesService
{
    private const SA_PATH = '/var/run/secrets/kubernetes.io/serviceaccount';
    private const CACHE_TTL = 10;

    /**
     * ponytail: static map, extend when a third namespace appears.
     */
    public const ENVIRONMENTS = [
        'development' => 'gpr-dev',
        'production' => 'gpr-prod',
    ];

    public function status(string $environment): array
    {
        $namespace = self::ENVIRONMENTS[$environment] ?? null;

        if ($namespace === null) {
            return $this->unreachable($environment, '-', 'unknown environment');
        }

        return Cache::remember(
            "k8s:status:{$namespace}",
            self::CACHE_TTL,
            fn () => $this->fetch($environment, $namespace)
        );
    }

    private function fetch(string $environment, string $namespace): array
    {
        $token = $this->token();

        if ($token === null) {
            return $this->unreachable($environment, $namespace, 'service account token not found');
        }

        $client = Http::withToken($token)
            ->withOptions(['verify' => $this->caPath()])
            ->timeout(5)
            ->baseUrl('https://kubernetes.default.svc');

        try {
            $deployments = $client->get("/apis/apps/v1/namespaces/{$namespace}/deployments");
            $pods = $client->get("/api/v1/namespaces/{$namespace}/pods");
        } catch (\Throwable $e) {
            return $this->unreachable($environment, $namespace, $e->getMessage());
        }

        if ($deployments->failed() || $pods->failed()) {
            return $this->unreachable(
                $environment,
                $namespace,
                'apiserver returned '.($deployments->failed() ? $deployments->status() : $pods->status())
            );
        }

        $apps = $this->mapDeployments($deployments->json('items', []));
        $mapped = $this->mapPods($pods->json('items', []));

        return [
            'environment' => $environment,
            'namespace' => $namespace,
            'reachable' => true,
            'fetched_at' => now()->toIso8601String(),
            'sync' => $this->syncState($apps, $mapped),
            'apps' => $apps,
            'pods' => $mapped,
            'summary' => [
                'pods' => count($mapped),
                'running' => count(array_filter($mapped, fn ($p) => $p['phase'] === 'Running')),
                'pending' => count(array_filter($mapped, fn ($p) => $p['phase'] === 'Pending')),
                'failing' => count(array_filter($mapped, fn ($p) => $p['crash_loop'])),
            ],
        ];
    }

    private function mapDeployments(array $items): array
    {
        return array_map(function (array $d) {
            $spec = $d['spec'] ?? [];
            $status = $d['status'] ?? [];

            return [
                'name' => $d['metadata']['name'] ?? '?',
                'desired' => $spec['replicas'] ?? 0,
                'ready' => $status['readyReplicas'] ?? 0,
                'updated' => $status['updatedReplicas'] ?? 0,
                'images' => array_values(array_map(
                    fn ($c) => $c['image'] ?? '',
                    $spec['template']['spec']['containers'] ?? []
                )),
            ];
        }, $items);
    }

    private function mapPods(array $items): array
    {
        return array_map(function (array $p) {
            $statuses = $p['status']['containerStatuses'] ?? [];
            $containers = array_map(fn ($c) => [
                'name' => $c['name'] ?? '?',
                'image' => $c['image'] ?? '',
                'state' => $this->containerState($c),
                'restart_count' => $c['restartCount'] ?? 0,
            ], $statuses);

            return [
                'name' => $p['metadata']['name'] ?? '?',
                'phase' => $p['status']['phase'] ?? 'Unknown',
                'ready' => sprintf(
                    '%d/%d',
                    count(array_filter($statuses, fn ($c) => $c['ready'] ?? false)),
                    count($statuses)
                ),
                'restarts' => array_sum(array_column($containers, 'restart_count')),
                'crash_loop' => (bool) array_filter($containers, fn ($c) => $c['state'] === 'CrashLoopBackOff'),
                'node' => $p['spec']['nodeName'] ?? '-',
                'started_at' => $p['status']['startTime'] ?? null,
                'containers' => $containers,
            ];
        }, $items);
    }

    private function containerState(array $c): string
    {
        foreach ($c['state'] ?? [] as $state => $payload) {
            return match ($state) {
                'running' => 'running',
                'waiting' => $payload['reason'] ?? 'waiting',
                'terminated' => $payload['reason'] ?? 'terminated',
                default => $state,
            };
        }

        return 'unknown';
    }

    private function syncState(array $apps, array $pods): string
    {
        if ($apps === []) {
            return 'Unknown';
        }

        $allReady = array_reduce(
            $apps,
            fn ($carry, $a) => $carry && $a['ready'] === $a['desired'],
            true
        );

        if ($allReady && ! in_array(true, array_column($pods, 'crash_loop'), true)) {
            return 'Healthy';
        }

        return in_array(true, array_column($pods, 'crash_loop'), true) ? 'Degraded' : 'Progressing';
    }

    private function unreachable(string $environment, string $namespace, string $reason): array
    {
        return [
            'environment' => $environment,
            'namespace' => $namespace,
            'reachable' => false,
            'reason' => $reason,
            'fetched_at' => now()->toIso8601String(),
            'sync' => 'Unknown',
            'apps' => [],
            'pods' => [],
            'summary' => ['pods' => 0, 'running' => 0, 'pending' => 0, 'failing' => 0],
        ];
    }

    /**
     * Rollout restart via the restartedAt annotation, then drop the status cache
     * so the page reflects the new rollout on its next poll.
     *
     * @throws \RuntimeException
     */
    public function restart(string $environment, string $deployment): void
    {
        [$namespace, $client] = $this->writeClient($environment);

        $response = $client->withHeaders(['Content-Type' => 'application/merge-patch+json'])
            ->patch("/apis/apps/v1/namespaces/{$namespace}/deployments/{$deployment}", [
                'spec' => ['template' => ['metadata' => ['annotations' => [
                    'kubectl.kubernetes.io/restartedAt' => now()->toIso8601String(),
                ]]]],
            ]);

        $this->assertOk($response, 'restart');
        Cache::forget("k8s:status:{$namespace}");
    }

    /**
     * @throws \RuntimeException
     */
    public function scale(string $environment, string $deployment, int $replicas): void
    {
        [$namespace, $client] = $this->writeClient($environment);

        $response = $client->withHeaders(['Content-Type' => 'application/merge-patch+json'])
            ->patch("/apis/apps/v1/namespaces/{$namespace}/deployments/{$deployment}", [
                'spec' => ['replicas' => $replicas],
            ]);

        $this->assertOk($response, 'scale');
        Cache::forget("k8s:status:{$namespace}");
    }

    /**
     * @throws \RuntimeException
     */
    public function logs(string $environment, string $pod, string $container, int $tail = 100): string
    {
        [$namespace, $client] = $this->writeClient($environment);

        $response = $client->get("/api/v1/namespaces/{$namespace}/pods/{$pod}/log", [
            'container' => $container,
            'tailLines' => $tail,
        ]);

        $this->assertOk($response, 'logs');

        return $response->body();
    }

    /**
     * The dashboard only holds write verbs in the namespace it runs in, so
     * anything else must render read-only to avoid guaranteed 403s.
     */
    public function isLocal(string $environment): bool
    {
        $namespace = self::ENVIRONMENTS[$environment] ?? null;
        $path = self::SA_PATH.'/namespace';

        return $namespace !== null
            && is_readable($path)
            && trim((string) file_get_contents($path)) === $namespace;
    }

    /**
     * @param  list<string>  $command
     *
     * @throws \RuntimeException
     */
    public function exec(string $environment, string $pod, string $container, array $command): string
    {
        $namespace = self::ENVIRONMENTS[$environment] ?? null;

        if ($namespace === null) {
            throw new \RuntimeException('unknown environment');
        }

        return (new KubernetesExec())->run($namespace, $pod, $container, $command);
    }

    /**
     * @return array{0:string,1:\Illuminate\Http\Client\PendingRequest}
     */
    private function writeClient(string $environment): array
    {
        $namespace = self::ENVIRONMENTS[$environment] ?? null;

        if ($namespace === null) {
            throw new \RuntimeException('unknown environment');
        }

        $token = $this->token();

        if ($token === null) {
            throw new \RuntimeException('service account token not found');
        }

        return [$namespace, Http::withToken($token)
            ->withOptions(['verify' => $this->caPath()])
            ->timeout(10)
            ->baseUrl('https://kubernetes.default.svc')];
    }

    private function assertOk(\Illuminate\Http\Client\Response $response, string $action): void
    {
        if ($response->failed()) {
            throw new \RuntimeException("{$action} failed: HTTP {$response->status()}");
        }
    }

    private function token(): ?string
    {
        $path = self::SA_PATH.'/token';

        return is_readable($path) ? trim((string) file_get_contents($path)) : null;
    }

    private function caPath(): bool|string
    {
        $ca = self::SA_PATH.'/ca.crt';

        return is_readable($ca) ? $ca : true;
    }
}