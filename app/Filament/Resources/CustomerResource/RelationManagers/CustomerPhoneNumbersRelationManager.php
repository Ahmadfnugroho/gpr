<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class CustomerPhoneNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'customerPhoneNumbers';

    protected static ?string $title = 'Nomor Telepon';

    protected static ?string $modelLabel = 'Nomor Telepon';

    protected static ?string $pluralModelLabel = 'Nomor Telepon';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('phone_number')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->required()
                    ->placeholder('+62812xxxxxxxx')
                    ->helperText('Format: +62812xxxxxxxx atau 0812xxxxxxxx')
                    ->rules(['regex:/^(\+62|62|0)[0-9]{8,13}$/'])
                    ->validationMessages([
                        'regex' => 'Format nomor telepon tidak valid. Gunakan format +62812xxxxxxxx atau 0812xxxxxxxx'
                    ])
                    ->formatStateUsing(function ($state) {
                        // Format untuk display
                        if (empty($state)) return $state;
                        
                        $phone = preg_replace('/\D/', '', $state);
                        if (str_starts_with($phone, '62')) {
                            return '+' . $phone;
                        } elseif (str_starts_with($phone, '0')) {
                            return '+62' . substr($phone, 1);
                        }
                        return $state;
                    })
                    ->dehydrateStateUsing(function ($state) {
                        // Format untuk database
                        if (empty($state)) return $state;
                        
                        $phone = preg_replace('/\D/', '', $state);
                        if (str_starts_with($phone, '62')) {
                            return '+' . $phone;
                        } elseif (str_starts_with($phone, '0')) {
                            return '+62' . substr($phone, 1);
                        }
                        return $state;
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->columns([
                TextColumn::make('phone_number')
                    ->label('Nomor Telepon')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor telepon berhasil disalin!')
                    ->icon('heroicon-m-phone')
                    ->formatStateUsing(function ($state) {
                        // Format display yang bagus
                        if (empty($state)) return $state;
                        
                        $phone = preg_replace('/\D/', '', $state);
                        if (str_starts_with($phone, '62')) {
                            $formatted = '+62 ' . substr($phone, 2);
                            // Format: +62 812 1234 5678
                            return preg_replace('/(\+62\s)(\d{3})(\d{4})(\d+)/', '$1$2 $3 $4', $formatted);
                        }
                        return $state;
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since(),
                    
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Nomor')
                    ->icon('heroicon-o-phone-plus')
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
            ])
            ->actions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function ($record) {
                        $phone = preg_replace('/\D/', '', $record->phone_number);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        } elseif (!str_starts_with($phone, '62')) {
                            $phone = '62' . $phone;
                        }
                        return "https://wa.me/{$phone}";
                    })
                    ->openUrlInNewTab(),
                    
                Action::make('call')
                    ->label('Telepon')
                    ->icon('heroicon-o-phone')
                    ->color('primary')
                    ->url(fn ($record) => "tel:{$record->phone_number}"),
                    
                Tables\Actions\EditAction::make()
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
                    
                Tables\Actions\DeleteAction::make()
                    ->successRedirectUrl(fn () => $this->getOwnerRecord()->resource::getUrl('view', ['record' => $this->getOwnerRecord()])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada nomor telepon')
            ->emptyStateDescription('Tambahkan nomor telepon customer di sini')
            ->emptyStateIcon('heroicon-o-phone');
    }
}
