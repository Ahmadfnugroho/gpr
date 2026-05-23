<div class="p-6 bg-white rounded-lg shadow-md">
    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Pilih File (Maks 10MB)</label>
            <div class="mt-1 flex items-center space-x-4">
                <input type="file" wire:model="photo" 
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
            </div>
            
            @error('photo') 
                <span class="text-red-500 text-xs mt-1">{{ $message }}</span> 
            @enderror
        </div>

        {{-- Progress Bar --}}
        <div x-data="{ uploading: false, progress: 0 }" 
             x-on:livewire-upload-start="uploading = true" 
             x-on:livewire-upload-finish="uploading = false" 
             x-on:livewire-upload-error="uploading = false" 
             x-on:livewire-upload-progress="progress = $event.detail.progress">
            
            <div x-show="uploading" class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                <div class="bg-blue-600 h-2.5 rounded-full" :style="'width: ' + progress + '%'"></div>
            </div>
        </div>

        {{-- Preview --}}
        @if ($photo)
            <div class="mt-4">
                <p class="text-sm text-gray-500 mb-2">Pratinjau:</p>
                @if (in_array($photo->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                    <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border">
                @else
                    <div class="p-4 bg-gray-100 rounded-lg text-sm text-gray-600 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        {{ $photo->getClientOriginalName() }}
                    </div>
                @endif
            </div>
        @endif

        <button type="submit" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
            wire:loading.attr="disabled">
            <span wire:loading.remove>Unggah File</span>
            <span wire:loading>Memproses...</span>
        </button>

        @if (session()->has('message'))
            <div class="mt-4 p-2 bg-green-100 text-green-700 text-sm rounded">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
