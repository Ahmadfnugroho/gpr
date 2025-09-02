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
use Illuminate\Support\HtmlString;

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
                                TextInput::make('facebook_username')
                                    ->label('Facebook Username')
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
                    ->color(fn (string $state): string => match ($state) {
                        Customer::STATUS_ACTIVE => 'success',
                        Customer::STATUS_INACTIVE => 'warning', 
                        Customer::STATUS_BLACKLIST => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Customer::STATUS_LABELS[$state] ?? $state)
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
