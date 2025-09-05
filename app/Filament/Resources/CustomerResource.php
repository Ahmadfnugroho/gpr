<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Filament\Resources\CustomerResource\RelationManagers\CustomerPhotosRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\CustomerPhoneNumbersRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActions;
use Illuminate\Support\HtmlString;
use Illuminate\Http\Response;
use App\Services\CustomerImportExportService;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Checkbox;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Customer Management';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->native(false),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(Customer::STATUS_LABELS)
                                    ->default(Customer::STATUS_BLACKLIST)
                                    ->required()
                                    ->native(false),
                                TextInput::make('source_info')
                                    ->label('Source Info')
                                    ->placeholder('How did they find us?'),
                            ]),
                    ]),

                Section::make('Contact Information')
                    ->schema([
                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('job')
                                    ->label('Job/Profession')
                                    ->maxLength(255),
                                Textarea::make('office_address')
                                    ->label('Office Address')
                                    ->rows(2),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('emergency_contact_name')
                                    ->label('Emergency Contact Name')
                                    ->maxLength(255),
                                TextInput::make('emergency_contact_number')
                                    ->label('Emergency Contact Phone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                    ]),

                Section::make('Social Media')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('instagram_username')
                                    ->label('Instagram Username')
                                    ->prefix('@')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->formatStateUsing(fn($record) => $record->phone_number ?? '-')
                    ->url(fn($record) => $record->phone_number ? 'https://wa.me/' . preg_replace('/\D/', '', $record->phone_number) : null)
                    ->openUrlInNewTab()
                    ->color('success')
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Customer::STATUS_ACTIVE => 'success',
                        Customer::STATUS_INACTIVE => 'warning',
                        Customer::STATUS_BLACKLIST => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => Customer::STATUS_LABELS[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable(),
                TextColumn::make('source_info')
                    ->label('Source')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Customer::STATUS_LABELS)
                    ->multiple(),
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),
                SelectFilter::make('source_info')
                    ->options([
                        'Instagram' => 'Instagram',
                        'Google' => 'Google',
                        'Teman' => 'Teman',
                        'TikTok' => 'TikTok',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Action::make('whatsapp')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->url(fn($record) => $record->phone_number ? 'https://wa.me/' . preg_replace('/\D/', '', $record->phone_number) : null)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => (bool) $record->phone_number),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = new CustomerImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'customer_import_template.xlsx')->deleteFileAfterSend();
                    }),

                Action::make('import')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('excel_file')
                            ->label('Excel File')
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                            ->required()
                            ->maxSize(2048)
                            ->helperText('Upload Excel file (.xls, .xlsx, .csv). Maximum 2MB'),
                        Checkbox::make('update_existing')
                            ->label('Update existing customers (based on email)')
                            ->default(false)
                            ->helperText('If unchecked, customers with existing emails will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new CustomerImportExportService();
                            $file = $data['excel_file'];
                            $updateExisting = $data['update_existing'] ?? false;

                            // Convert to UploadedFile if needed
                            if (is_string($file)) {
                                $filePath = storage_path('app/public/' . $file);
                                $file = new \Illuminate\Http\UploadedFile(
                                    $filePath,
                                    basename($filePath),
                                    mime_content_type($filePath),
                                    null,
                                    true
                                );
                            }

                            // Check file size for import strategy
                            if ($file instanceof \Illuminate\Http\UploadedFile && $file->getSize() > 1024 * 1024) {
                                // Use async import for large files (> 1MB)
                                $result = $service->importCustomersAsync(
                                    $file, 
                                    $updateExisting, 
                                    auth()->id()
                                );
                                
                                if ($result['queued']) {
                                    Notification::make()
                                        ->title('Import Started')
                                        ->body('Large file import is being processed in background. Import ID: ' . $result['import_id'])
                                        ->info()
                                        ->duration(8000)
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Import Failed to Start')
                                        ->body($result['message'] ?? 'Failed to queue import job')
                                        ->danger()
                                        ->send();
                                }
                            } else {
                                // Use sync import for small files
                                $results = $service->importCustomers($file, $updateExisting);
                                
                                $message = "Import completed! Total: {$results['total']}, Success: {$results['success']}, Updated: {$results['updated']}, Failed: {$results['failed']}";
                                
                                if (!empty($results['errors'])) {
                                    Notification::make()
                                        ->title('Import Completed with Errors')
                                        ->body($message . "\n\nErrors: " . implode(', ', array_slice($results['errors'], 0, 3)))
                                        ->warning()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Import Successful')
                                        ->body($message)
                                        ->success()
                                        ->send();
                                }
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        $service = new CustomerImportExportService();
                        $filePath = $service->exportCustomers();
                        return response()->download($filePath, 'customers_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Action::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $service = new CustomerImportExportService();
                            $customerIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportCustomers($customerIds);
                            return response()->download($filePath, 'customers_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                        }),
                    Action::make('bulkActivate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['status' => Customer::STATUS_ACTIVE]));
                            Notification::make()
                                ->title('Customers Activated')
                                ->body(count($records) . ' customers have been activated')
                                ->success()
                                ->send();
                        }),
                    Action::make('bulkDeactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['status' => Customer::STATUS_INACTIVE]));
                            Notification::make()
                                ->title('Customers Deactivated')
                                ->body(count($records) . ' customers have been deactivated')
                                ->warning()
                                ->send();
                        }),
                    Action::make('bulkBlacklist')
                        ->label('Blacklist')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['status' => Customer::STATUS_BLACKLIST]));
                            Notification::make()
                                ->title('Customers Blacklisted')
                                ->body(count($records) . ' customers have been blacklisted')
                                ->danger()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CustomerPhotosRelationManager::class,
            CustomerPhoneNumbersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
