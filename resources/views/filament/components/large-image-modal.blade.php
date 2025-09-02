<div class="flex flex-col items-center justify-center space-y-4 p-6">
    <div class="flex flex-col items-center space-y-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $title }}
        </h3>
        @if($idType)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
            {{ $idType }}
        </span>
        @endif
    </div>

    <div class="relative max-w-full max-h-[80vh] overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 shadow-lg">
        <img
            src="{{ $imageUrl }}"
            alt="{{ $title }}"
            class="max-w-full max-h-full object-contain cursor-zoom-in transition-transform duration-200 hover:scale-105"
            onclick="this.style.transform = this.style.transform ? '' : 'scale(1.5)'; this.style.cursor = this.style.cursor === 'zoom-out' ? 'zoom-in' : 'zoom-out';" />
    </div>

    <div class="flex space-x-2">
        <a
            href="{{ $imageUrl }}"
            target="_blank"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Buka di Tab Baru
        </a>

        <button
            type="button"
            onclick="navigator.clipboard.writeText('{{ $imageUrl }}'); alert('URL gambar berhasil disalin!');"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            Salin URL
        </button>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
        💡 <strong>Tip:</strong> Klik gambar untuk memperbesar/memperkecil
    </p>
</div>

<style>
    /* Custom scrollbar for image container */
    .overflow-hidden:hover {
        overflow: visible;
    }

    /* Smooth zoom transition */
    img {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Loading animation */
    img[src] {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>