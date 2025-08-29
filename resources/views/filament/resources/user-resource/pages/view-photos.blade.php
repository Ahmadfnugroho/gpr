<div class="space-y-4">
    @if($user->userPhotos->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($user->userPhotos as $photo)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 bg-gray-50">
                        <h3 class="font-semibold text-gray-900 text-sm uppercase">
                            {{ $photo->photo_type ?: 'Dokumen ID' }}
                        </h3>
                        <p class="text-gray-600 text-xs">
                            Diupload: {{ $photo->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    
                    <div class="relative">
                        @if(Storage::disk('public')->exists($photo->photo))
                            <img 
                                src="{{ Storage::url($photo->photo) }}" 
                                alt="{{ $photo->photo_type }}"
                                class="w-full h-auto object-contain max-h-96 cursor-pointer"
                                onclick="openImageModal('{{ Storage::url($photo->photo) }}', '{{ $photo->photo_type ?: 'Dokumen ID' }}')"
                            >
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <div class="text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-2 text-sm">Foto tidak ditemukan</p>
                                    <p class="text-xs text-gray-400">{{ $photo->photo }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Safe view overlay - tidak ada opsi delete -->
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                View Only
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Informasi User -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">Informasi User</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><strong>Nama:</strong> {{ $user->name }}</div>
                <div><strong>Email:</strong> {{ $user->email }}</div>
                <div><strong>Status:</strong> 
                    <span class="px-2 py-1 text-xs rounded {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </div>
                <div><strong>Total Foto:</strong> {{ $user->userPhotos->count() }}</div>
            </div>
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium">Tidak ada foto</h3>
            <p class="mt-1">User ini belum mengupload foto dokumen.</p>
        </div>
    @endif
</div>

<!-- Modal untuk melihat gambar dalam ukuran penuh -->
<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeImageModal()"></div>
        
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                    Foto Dokumen
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeImageModal()">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="text-center">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto object-contain">
            </div>
            
            <div class="mt-4 flex justify-end">
                <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded" onclick="closeImageModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openImageModal(imageSrc, imageTitle) {
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('modal-title').textContent = imageTitle;
        document.getElementById('imageModal').classList.remove('hidden');
    }
    
    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
        }
    });
</script>
