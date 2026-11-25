<x-filament-panels::page>
    {{-- The panel ships Filament's precompiled CSS only; utilities outside that
         set (divide-*, whitespace-pre-wrap, break-all, bg-gray-950) are absent,
         so the handful this page needs are declared here. --}}
    <style>
        .cs-rows > tr + tr { border-top: 1px solid rgb(var(--gray-100)); }
        .cs-pre { white-space: pre-wrap; word-break: break-all; }
    </style>

    <div class="flex flex-wrap items-center gap-3">
        @foreach (['development' => 'Development', 'production' => 'Production'] as $key => $label)
            <x-filament::button
                size="sm"
                :color="$environment === $key ? 'primary' : 'gray'"
                :outlined="$environment !== $key"
                wire:click="selectEnvironment('{{ $key }}')"
                aria-pressed="{{ $environment === $key ? 'true' : 'false' }}"
            >
                {{ $label }}
            </x-filament::button>
        @endforeach

        <div class="ml-auto">
            <x-filament::badge
                :color="match ($status['sync']) {
                    'Healthy' => 'success',
                    'Progressing' => 'warning',
                    'Degraded' => 'danger',
                    default => 'gray',
                }"
            >
                {{ $status['sync'] }}
            </x-filament::badge>
        </div>
    </div>

    <p class="text-xs text-gray-500">
        namespace <span class="font-mono">{{ $status['namespace'] }}</span>
        &middot; diperbarui {{ $status['fetched_at'] }}
    </p>

    @if (! $status['reachable'])
        <x-filament::section>
            <p class="text-sm">
                Cluster tidak terjangkau: {{ $status['reason'] ?? 'tidak diketahui' }}.
                Halaman ini hanya menampilkan data nyata saat berjalan di dalam cluster.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">Deployments</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-600">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Ready</th>
                            <th class="px-3 py-2">Updated</th>
                            <th class="px-3 py-2">Image</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="cs-rows">
                        @forelse ($status['apps'] as $app)
                            <tr wire:key="deploy-{{ $app['name'] }}">
                                <td class="px-3 py-2 font-medium">{{ $app['name'] }}</td>
                                <td class="px-3 py-2">{{ $app['ready'] }}/{{ $app['desired'] }}</td>
                                <td class="px-3 py-2">{{ $app['updated'] }}</td>
                                <td class="px-3 py-2 font-mono text-xs break-all">{{ implode(', ', $app['images']) }}</td>
                                <td class="px-3 py-2">
                                    @if ($writable)
                                        <div class="flex flex-wrap gap-1">
                                            <x-filament::button
                                                size="xs"
                                                color="primary"
                                                outlined
                                                wire:click="restart('{{ $app['name'] }}')"
                                                wire:confirm="Restart deployment {{ $app['name'] }}? Pod akan dibuat ulang bergiliran."
                                            >Restart</x-filament::button>

                                            @if ($app['desired'] > 0)
                                                <x-filament::button
                                                    size="xs"
                                                    color="danger"
                                                    outlined
                                                    wire:click="stop('{{ $app['name'] }}')"
                                                    wire:confirm="Scale {{ $app['name'] }} ke 0? Layanan ini akan berhenti melayani."
                                                >Stop</x-filament::button>
                                            @else
                                                <x-filament::button
                                                    size="xs"
                                                    color="success"
                                                    outlined
                                                    wire:click="start('{{ $app['name'] }}')"
                                                    wire:confirm="Scale up {{ $app['name'] }}?"
                                                >Start</x-filament::button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">read-only</span>
                                    @endif
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
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Pods</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-600">
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
                    <tbody class="cs-rows">
                        @forelse ($status['pods'] as $pod)
                            <tr @if ($pod['crash_loop']) style="background:rgb(var(--danger-50))" @endif wire:key="pod-{{ $pod['name'] }}">
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
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                outlined
                                                wire:click="showLogs('{{ $pod['name'] }}', '{{ $container['name'] }}')"
                                            >Logs</x-filament::button>

                                            @if ($writable)
                                                <x-filament::button
                                                    size="xs"
                                                    color="gray"
                                                    outlined
                                                    wire:click="showTerminal('{{ $pod['name'] }}', '{{ $container['name'] }}')"
                                                >Terminal</x-filament::button>
                                            @endif
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
        </x-filament::section>

        <p class="text-xs text-gray-500">
            Pods {{ $status['summary']['pods'] }}
            &middot; running {{ $status['summary']['running'] }}
            &middot; pending {{ $status['summary']['pending'] }}
            &middot; gagal {{ $status['summary']['failing'] }}
        </p>
    @endif

    @if ($panelMode)
        <div
            class="fixed inset-0 z-40 flex items-center justify-center p-4"
            style="background:rgba(2,6,23,.5)"
            wire:key="panel-{{ $panelPod }}-{{ $panelContainer }}"
        >
            <div
                class="flex w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl"
                style="max-height:85vh"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h3 class="text-sm font-semibold">{{ $panelTitle }}</h3>
                    <x-filament::button size="xs" color="gray" outlined wire:click="closePanel">Tutup</x-filament::button>
                </div>

                <div class="flex-1 overflow-auto p-4" style="background:#030712">
                    <pre class="cs-pre font-mono text-xs" style="color:#f1f5f9">{{ $panelBody !== '' ? $panelBody : 'Belum ada output.' }}</pre>
                </div>

                @if ($panelMode === 'terminal')
                    <form wire:submit="runCommand" class="flex gap-2 border-t border-gray-200 p-3">
                        <input
                            type="text"
                            wire:model="command"
                            placeholder="ls -la /data"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"
                        >
                        <x-filament::button type="submit">Jalankan</x-filament::button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>