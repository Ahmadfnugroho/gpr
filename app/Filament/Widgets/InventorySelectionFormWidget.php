<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Bundling;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;

class InventorySelectionFormWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.inventory-selection-form';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public ?array $data = [];

    public function mount(): void
    {
        // Pre-populate from URL parameters
        $selectedItems = [];
        
        // Handle products from URL
        $selectedProducts = request('selected_products', []);
        if (!empty($selectedProducts)) {
            foreach ($selectedProducts as $productId) {
                $selectedItems[] = "produk-{$productId}";
            }
        }
        
        // Handle bundlings from URL
        $selectedBundlings = request('selected_bundlings', []);
        if (!empty($selectedBundlings)) {
            foreach ($selectedBundlings as $bundlingId) {
                $selectedItems[] = "bundling-{$bundlingId}";
            }
        }
        
        $this->form->fill([
            'selected_items' => $selectedItems,
            'start_date' => request('start_date', now()->format('Y-m-d H:i:s')),
            'end_date' => request('end_date', now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('🔍 Pencarian Ketersediaan Produk & Bundling')
                    ->description('Pilih produk atau bundling yang ingin Anda periksa ketersediaannya, lalu tentukan periode tanggal.')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('selected_items')
                                    ->label('🛍️📦 Pilih Produk/Bundling')
                                    ->multiple()
                                    ->searchable()
                                    ->options(function () {
                                        $products = Product::where('status', '!=', 'deleted')
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(function($name, $id) {
                                                return ["produk-{$id}" => "🛍️ {$name}"];
                                            });
                                            
                                        $bundlings = Bundling::orderBy('name')
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(function($name, $id) {
                                                return ["bundling-{$id}" => "📦 {$name}"];
                                            });
                                            
                                        return $products->merge($bundlings)->toArray();
                                    })
                                    ->placeholder('Ketik untuk mencari produk atau bundling...')
                                    ->helperText('💡 Pilih kombinasi produk dan bundling sesuai kebutuhan')
                                    ->live()
                                    ->columnSpanFull(),
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->label('📅 Tanggal & Waktu Mulai')
                                    ->default(now())
                                    ->maxDate(now()->addYear())
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->helperText('Tanggal mulai periode pengecekan')
                                    ->required(),
                                    
                                DateTimePicker::make('end_date')
                                    ->label('📅 Tanggal & Waktu Selesai')
                                    ->default(now()->addDays(7)->endOfDay())
                                    ->after('start_date')
                                    ->maxDate(now()->addYear())
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->helperText('Default: 7 hari kedepan jam 24:00')
                                    ->required(),
                            ]),
                            
                        Actions::make([
                            Action::make('search')
                                ->label('🔍 Cari Ketersediaan')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('primary')
                                ->size('lg')
                                ->action(function (array $data) {
                                    $this->searchInventory($data);
                                }),
                                
                            Action::make('reset')
                                ->label('🔄 Reset')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->action(function () {
                                    $this->form->fill([
                                        'selected_items' => [],
                                        'start_date' => now()->format('Y-m-d H:i:s'),
                                        'end_date' => now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s'),
                                    ]);
                                    return redirect()->to('/admin/unified-inventory');
                                }),
                        ])
                        ->alignment('center')
                        ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),
            ])
            ->statePath('data');
    }

    public function searchInventory(array $data): void
    {
        if (empty($data['selected_items'])) {
            Notification::make()
                ->title('⚠️ Pilihan Kosong')
                ->body('Silakan pilih minimal satu produk atau bundling.')
                ->warning()
                ->send();
            return;
        }

        // Separate products and bundlings
        $selectedProducts = [];
        $selectedBundlings = [];

        foreach ($data['selected_items'] as $item) {
            if (str_starts_with($item, 'produk-')) {
                $selectedProducts[] = str_replace('produk-', '', $item);
            } elseif (str_starts_with($item, 'bundling-')) {
                $selectedBundlings[] = str_replace('bundling-', '', $item);
            }
        }

        // Build query parameters
        $params = [];

        // Handle products array
        if (!empty($selectedProducts)) {
            foreach ($selectedProducts as $productId) {
                $params['selected_products[]'] = $productId;
            }
        }

        // Handle bundlings array  
        if (!empty($selectedBundlings)) {
            foreach ($selectedBundlings as $bundlingId) {
                $params['selected_bundlings[]'] = $bundlingId;
            }
        }

        // Add date parameters
        $params['start_date'] = $data['start_date'];
        $params['end_date'] = $data['end_date'];

        // Show success notification
        $productCount = count($selectedProducts);
        $bundlingCount = count($selectedBundlings);

        Notification::make()
            ->title('✅ Pencarian Berhasil')
            ->body("Menampilkan {$productCount} produk dan {$bundlingCount} bundling.")
            ->success()
            ->send();

        // Redirect with parameters
        redirect('/admin/unified-inventory?' . http_build_query($params));
    }
}
