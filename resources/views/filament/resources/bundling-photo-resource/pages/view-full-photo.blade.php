<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    Foto Bundling - {{ $bundlingPhoto->bundling->name }}
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Diupload: {{ $bundlingPhoto->created_at?->format('d M Y H:i') }}
                </p>
            </div>

            <div class="text-center">
                @if($bundlingPhoto->photo)
                <img
                    src="{{ Storage::disk('public')->url($bundlingPhoto->photo) }}"
                    alt="Foto Bundling {{ $bundlingPhoto->bundling->name }}"
                    class="max-w-full max-h-96 object-contain mx-auto rounded-lg shadow-md"
                    style="max-height: 600px;" />
                @else
                <div class="bg-gray-100 rounded-lg p-8">
                    <svg class="w-16 h-16 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 mt-2">Foto tidak tersedia</p>
                </div>
                @endif
            </div>

            <div class="mt-4 text-center">
                @if($bundlingPhoto->photo)
                <a
                    href="{{ Storage::disk('public')->url($bundlingPhoto->photo) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Buka di Tab Baru
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
