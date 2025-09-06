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
use Illuminate\Support\Facades\Redirect;

class InventorySelectionWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.inventory-selection-widget';
    
    protected static ?int $sort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'selected_products' => request('selected_products', []),
            'selected_bundlings' => request('selected_bundlings', []),
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
                    ->headerActions([
                        Action::make('reset')
                            ->label('Reset')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function () {
                                $this->reset(['data']);
                                $this->form->fill([
                                    'selected_products' => [],
                                    'selected_bundlings' => [],
                                    'start_date' => now()->format('Y-m-d H:i:s'),
                                    'end_date' => now()->addDays(7)->endOfDay()->format('Y-m-d H:i:s'),
                                ]);
                                return redirect()->to('/admin/unified-inventory');
                            }),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('selected_products')
                                    ->label('🛍️ Pilih Produk')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return Product::select('id', 'name')
                                            ->where('status', '!=', 'deleted')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->placeholder('Ketik untuk mencari produk...')
                                    ->helperText('💡 Anda bisa memilih beberapa produk sekaligus')
                                    ->columnSpan(1),
                                    
                                Select::make('selected_bundlings')
                                    ->label('📦 Pilih Bundling')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        return Bundling::select('id', 'name')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->placeholder('Ketik untuk mencari bundling...')
                                    ->helperText('💡 Anda bisa memilih beberapa bundling sekaligus')
                                    ->columnSpan(1),
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
                                    ->required()
                                    ->columnSpan(1),
                                    
                                DateTimePicker::make('end_date')
                                    ->label('📅 Tanggal & Waktu Selesai')
                                    ->default(now()->addDays(7)->endOfDay())
                                    ->after('start_date')
                                    ->maxDate(now()->addYear())
                                    ->native(false)
                                    ->displayFormat('d M Y H:i')
                                    ->helperText('Default: 7 hari kedepan jam 24:00')
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                            
                        Actions::make([
                            Action::make('search')
                                ->label('🔍 Cari Ketersediaan')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('primary')
                                ->size('lg')
                                ->requiresConfirmation()
                                ->modalHeading('Konfirmasi Pencarian')
                                ->modalDescription('Apakah Anda yakin ingin mencari ketersediaan dengan kriteria yang dipilih?')
                                ->modalSubmitActionLabel('Ya, Cari Sekarang')
                                ->action(function (array $data) {
                                    if (empty($data['selected_products']) && empty($data['selected_bundlings'])) {
                                        Notification::make()
                                            ->title('⚠️ Pilihan Kosong')
                                            ->body('Silakan pilih minimal satu produk atau bundling.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    // Build query parameters
                                    $params = [];
                                    
                                    // Handle products array
                                    if (!empty($data['selected_products'])) {
                                        foreach ($data['selected_products'] as $productId) {
                                            $params['selected_products[]'] = $productId;
                                        }
                                    }
                                    
                                    // Handle bundlings array  
                                    if (!empty($data['selected_bundlings'])) {
                                        foreach ($data['selected_bundlings'] as $bundlingId) {
                                            $params['selected_bundlings[]'] = $bundlingId;
                                        }
                                    }
                                    
                                    // Add date parameters
                                    $params['start_date'] = $data['start_date'];
                                    $params['end_date'] = $data['end_date'];
                                    
                                    // Show success notification
                                    $productCount = count($data['selected_products'] ?? []);
                                    $bundlingCount = count($data['selected_bundlings'] ?? []);
                                    
                                    Notification::make()
                                        ->title('✅ Pencarian Berhasil')
                                        ->body("Menampilkan {$productCount} produk dan {$bundlingCount} bundling.")
                                        ->success()
                                        ->send();
                                    
                                    // Redirect with parameters
                                    return redirect()->to('/admin/unified-inventory?' . http_build_query($params));
                                })
                                ->extraAttributes(['class' => 'w-full']),
                        ])
                        ->alignment('center')
                        ->fullWidth(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),
            ])
            ->statePath('data');
    }
}
