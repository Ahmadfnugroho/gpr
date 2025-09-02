{{-- ⚠️ ARCHITECTURE CHANGE NOTICE --}}
<div class="max-w-2xl mx-auto">
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-blue-900">Architecture Change Notice</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p class="mb-3">User model no longer handles photos and phone numbers. These have been moved to the Customer model for better separation of concerns.</p>
                    
                    <div class="bg-white p-4 rounded-md border border-blue-200 mb-4">
                        <h4 class="font-semibold text-blue-900 mb-2">New Model Structure:</h4>
                        <ul class="space-y-1 text-sm">
                            <li><strong>👤 User Model:</strong> Admin/Staff authentication only (name, email, roles)</li>
                            <li><strong>👥 Customer Model:</strong> Rental customers with photos, phone numbers, addresses</li>
                        </ul>
                    </div>
                    
                    <div class="bg-white p-4 rounded-md border border-blue-200">
                        <h4 class="font-semibold text-blue-900 mb-2">Current User Information:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                            <div><strong>Name:</strong> {{ $user->name }}</div>
                            <div><strong>Email:</strong> {{ $user->email }}</div>
                            <div><strong>Email Verified:</strong> {{ $user->email_verified_at ? '✅ Yes' : '❌ No' }}</div>
                            <div><strong>Roles:</strong> {{ $user->roles->pluck('name')->join(', ') ?: 'No roles assigned' }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="{{ route('filament.admin.resources.customers.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition">
                        📷 View Customer Photos & Documents
                    </a>
                </div>
            </div>
        </div>
    </div>
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
                <img id="modalImage" src="" alt="" class="w-full h-auto object-contain max-h-[80vh] mx-auto border border-gray-200 rounded-lg shadow-sm">
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
