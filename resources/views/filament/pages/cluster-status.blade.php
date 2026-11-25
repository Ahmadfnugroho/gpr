<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-3">
        @foreach (['development' => 'Development', 'production' => 'Production'] as $key => $label)
            <button
                type="button"
                wire:click="selectEnvironment('{{ $key }}')"
                @class([
                    'rounded-lg border px-4 py-2 text-sm font-semibold transition',
                    'border-primary-600 bg-primary-600 text-white' => $environment === $key,
                    'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700' => $environment !== $key,
                ])
                aria-pressed="{{ $environment === $key ? 'true' : 'false' }}"
            >
                {{ $label }}
            </button>
        @endforeach

        <span
            @class([
                'ml-auto rounded-md px-3 py-1 text-sm font-semibold',
                'bg-success-100 text-success-800' => $status['sync'] === 'Healthy',
                'bg-warning-100 text-warning-800' => $status['sync'] === 'Progressing',
                'bg-danger-100 text-danger-800' => $status['sync'] === 'Degraded',
                'bg-gray-100 text-gray-700' => ! in_array($status['sync'], ['Healthy', 'Progressing', 'Degraded'], true),
            ])
        >
            {{ $status['sync'] }}
        </span>
    </div>

    <p class="text-xs text-gray-500">
        namespace <span class="font-mono">{{ $status['namespace'] }}</span>
        &middot; diperbarui {{ $status['fetched_at'] }}
    </p>

    @if (! $status['reachable'])
        <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-900">
            Cluster tidak terjangkau: {{ $status['reason'] ?? 'tidak diketahui' }}.
            Halaman ini hanya menampilkan data nyata saat berjalan di dalam cluster.
        </div>
    @else
        <section class="space-y-2">
            <h2 class="text-base font-semibold text-gray-900">Deployments</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Ready</th>
                            <th class="px-3 py-2">Updated</th>
                            <th class="px-3 py-2">Image</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($status['apps'] as $app)
                            <tr wire:key="deploy-{{ $app['name'] }}">
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $app['name'] }}</td>
                                <td class="px-3 py-2">{{ $app['ready'] }}/{{ $app['desired'] }}</td>
                                <td class="px-3 py-2">{{ $app['updated'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs break-all">{{ implode(', ', $app['images']) }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            type="button"
                                            class="rounded border border-primary-300 px-2 py-1 text-xs font-semibold text-primary-700 hover:bg-primary-50"
                                            wire:click="restart('{{ $app['name'] }}')"
                                            wire:confirm="Restart deployment {{ $app['name'] }}? Pod akan dibuat ulang bergiliran."
                                        >Restart</button>
                                        @if ($app['desired'] > 0)
                                            <button
                                                type="button"
                                                class="rounded border border-danger-300 px-2 py-1 text-xs font-semibold text-danger-700 hover:bg-danger-50"
                                                wire:click="stop('{{ $app['name'] }}')"
                                                wire:confirm="Scale {{ $app['name'] }} ke 0? Layanan ini akan berhenti melayani."
                                            >Stop</button>
                                        @else
                                            <button
                                                type="button"
                                                class="rounded border border-success-300 px-2 py-1 text-xs font-semibold text-success-700 hover:bg-success-50"
                                                wire:click="start('{{ $app['name'] }}')"
                                                wire:confirm="Scale up {{ $app['name'] }}?"
                                            >Start</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-500">Belum ada Deployment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-gray-900">Pods</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Phase</th>
                            <th class="px-3 py-2">Ready</th>
                            <th class="px-3 py-2">Restart</th>
                            <th class="px-3 py-2">Node</th>
                            <th class="px-3 py-2">Container</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($status['pods'] as $pod)
                            <tr @class(['bg-danger-50' => $pod['crash_loop']]) wire:key="pod-{{ $pod['name'] }}">
                                <td class="px-3 py-2 font-mono text-xs">{{ $pod['name'] }}</td>
                                <td class="px-3 py-2">{{ $pod['phase'] }}</td>
                                <td class="px-3 py-2">{{ $pod['ready'] }}</td>
                                <td class="px-3 py-2">{{ $pod['restarts'] }}</td>
                                <td class="px-3 py-2">{{ $pod['node'] }}</td>
                                <td class="px-3 py-2">
                                    @foreach ($pod['containers'] as $container)
                                        <div class="font-mono text-xs">
                                            {{ $container['name'] }}: {{ $container['state'] }}
                                        </div>
                                    @endforeach
                                </td>
                                <td class="px-3 py-2">
                                    @foreach ($pod['containers'] as $container)
                                        <div class="flex flex-wrap gap-1 pb-1">
                                            <button
                                                type="button"
                                                class="rounded border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                wire:click="showLogs('{{ $pod['name'] }}', '{{ $container['name'] }}')"
                                            >Logs</button>
                                            <button
                                                type="button"
                                                class="rounded border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                wire:click="showTerminal('{{ $pod['name'] }}', '{{ $container['name'] }}')"
                                            >Terminal</button>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-4 text-center text-gray-500">Belum ada Pod.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-xs text-gray-500">
            Pods {{ $status['summary']['pods'] }}
            &middot; running {{ $status['summary']['running'] }}
            &middot; pending {{ $status['summary']['pending'] }}
            &middot; gagal {{ $status['summary']['failing'] }}
        </p>
    @endif

    @if ($panelMode)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
            wire:key="panel-{{ $panelPod }}-{{ $panelContainer }}"
        >
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $panelTitle }}</h3>
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-sm text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                        wire:click="closePanel"
                    >Tutup</button>
                </div>

                <div class="flex-1 overflow-auto bg-gray-950 p-4">
                    <pre class="whitespace-pre-wrap break-all font-mono text-xs text-gray-100">{{ $panelBody !== '' ? $panelBody : 'Belum ada output.' }}</pre>
                </div>

                @if ($panelMode === 'terminal')
                    <form wire:submit="runCommand" class="flex gap-2 border-t border-gray-200 p-3 dark:border-gray-700">
                        <input
                            type="text"
                            wire:model="command"
                            placeholder="ls -la /data"
                            class="flex-1 rounded border border-gray-300 px-3 py-2 font-mono text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        >
                        <button
                            type="submit"
                            class="rounded bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500"
                        >Jalankan</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>