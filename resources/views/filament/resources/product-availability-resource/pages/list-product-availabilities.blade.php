<x-filament-panels::page>
    {{-- Custom form section above the table --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                🔍 Product & Bundling Availability Search
            </x-slot>
            
            <x-slot name="description">
                Select products/bundlings and set date range to check their availability
            </x-slot>
            
            <form wire:submit="searchAction">
                {{ $this->form }}
                
                <div class="mt-4 flex gap-4 justify-center">
                    <x-filament::button type="submit" color="primary" size="lg" icon="heroicon-o-magnifying-glass">
                        🔍 Search Availability
                    </x-filament::button>
                    
                    <x-filament::button 
                        tag="a" 
                        :href="route('filament.admin.resources.product-availability.index')"
                        color="gray" 
                        icon="heroicon-o-arrow-path"
                    >
                        🔄 Clear Filters
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>

    {{-- Default table section --}}
    {{ $this->table }}
</x-filament-panels::page>
