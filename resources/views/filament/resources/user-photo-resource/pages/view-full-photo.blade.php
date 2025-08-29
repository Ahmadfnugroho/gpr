<div class="space-y-6">
    <!-- Photo Display -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        @if(Storage::disk('public')->exists($userPhoto->photo))
            <div class="text-center bg-gray-50 p-4">
                <img 
                    src="{{ Storage::url($userPhoto->photo) }}" 
                    alt="{{ $userPhoto->photo_type ?: 'Dokumen ID' }}"
                    class="max-w-full h-auto mx-auto object-contain"
                    style="max-height: 70vh;"
                >
            </div>
        @else
            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium">Foto tidak ditemukan</h3>
                    <p class="text-sm text-gray-400">{{ $userPhoto->photo }}</p>
                </div>
            </div>
        @endif
        
        <!-- Safe view indicator -->
        <div class="bg-green-50 border-t border-green-200 p-3">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">
                        <strong>Mode View Only:</strong> Foto hanya dapat dilihat, tidak dapat dihapus atau dimodifikasi untuk keamanan data.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Information -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Foto</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-500">Jenis Dokumen</label>
                    <p class="text-base text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $userPhoto->photo_type ?: 'Dokumen ID' }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Tanggal Upload</label>
                    <p class="text-base text-gray-900">{{ $userPhoto->created_at->format('d F Y, H:i') }} WIB</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Terakhir Update</label>
                    <p class="text-base text-gray-900">{{ $userPhoto->updated_at->format('d F Y, H:i') }} WIB</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-500">Pemilik</label>
                    <p class="text-base text-gray-900">{{ $userPhoto->user->name }}</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Email</label>
                    <p class="text-base text-gray-900">{{ $userPhoto->user->email }}</p>
                </div>
                
                <div>
                    <label class="text-sm font-medium text-gray-500">Status User</label>
                    <p class="text-base">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $userPhoto->user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($userPhoto->user->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- File Information -->
    @if(Storage::disk('public')->exists($userPhoto->photo))
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Informasi File</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Nama File:</span>
                    <br>
                    <span class="text-gray-900 font-mono">{{ basename($userPhoto->photo) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Ukuran:</span>
                    <br>
                    <span class="text-gray-900">{{ number_format(Storage::disk('public')->size($userPhoto->photo) / 1024, 2) }} KB</span>
                </div>
                <div>
                    <span class="text-gray-500">Path:</span>
                    <br>
                    <span class="text-gray-900 font-mono text-xs">{{ $userPhoto->photo }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
        <div class="text-sm text-gray-500">
            ID: {{ $userPhoto->id }}
        </div>
        
        @if(Storage::disk('public')->exists($userPhoto->photo))
            <a 
                href="{{ Storage::url($userPhoto->photo) }}" 
                target="_blank" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Buka di Tab Baru
            </a>
        @endif
    </div>
</div>
