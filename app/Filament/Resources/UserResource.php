<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Services\UserImportExportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http as FacadesHttp;
use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $recordTitleAttribute = 'name';

    // ✅ Global Search Configuration
    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'email',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        // Email verification status
        $emailVerified = $record->email_verified_at ? '✅ Verified' : '❌ Not Verified';
        
        return [
            'Email' => $record->email,
            'Email Status' => $emailVerified,
            'Roles' => $record->roles->pluck('name')->join(', ') ?: 'No roles assigned',
            'Created' => $record->created_at?->format('d M Y'),
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    // protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('Leave blank if email is not verified. Set date/time when email was verified.')
                            ->nullable(),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText('Leave blank to keep current password when editing'),
                        Forms\Components\CheckboxList::make('roles')
                            ->label('User Roles')
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->helperText('Select roles for this admin/staff user'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->headerActions([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = new UserImportExportService();
                        $filePath = $service->generateTemplate();
                        return response()->download($filePath, 'user_import_template.xlsx')->deleteFileAfterSend();
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
                            ->label('Update existing users (based on email)')
                            ->default(false)
                            ->helperText('If unchecked, users with existing emails will be skipped')
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = new UserImportExportService();
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
                            
                            $results = $service->importUsers($file, $updateExisting);
                            
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
                        $service = new UserImportExportService();
                        $filePath = $service->exportUsers();
                        return response()->download($filePath, 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                    }),
            ])

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified')
                    ->label('Email Verified')
                    ->getStateUsing(fn($record) => $record->email_verified_at !== null)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn($record) => $record->email_verified_at
                        ? 'Verified on: ' . $record->email_verified_at->format('d M Y H:i')
                        : 'Email not verified'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                // Note: Phone numbers and photos now handled by Customer model


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('email_verified')
                    ->label('Email Verification Status')
                    ->options([
                        'verified' => 'Verified',
                        'not_verified' => 'Not Verified',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'verified') {
                            return $query->whereNotNull('email_verified_at');
                        } elseif ($data['value'] === 'not_verified') {
                            return $query->whereNull('email_verified_at');
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('User Status')
                    ->options([
                        'active' => 'Active',
                        'blacklist' => 'Blacklist',
                    ]),
            ])
            ->actions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\Action::make('verify_email')
                            ->icon('heroicon-o-check-badge')
                            ->color('success')
                            ->label('Verify Email')
                            ->requiresConfirmation()
                            ->visible(fn(User $record) => $record->email_verified_at === null)
                            ->action(function (User $record) {
                                $record->update(['email_verified_at' => now()]);
                                Notification::make()
                                    ->success()
                                    ->title('Email Verified Successfully')
                                    ->body('User email has been marked as verified.')
                                    ->send();
                            }),
                        Tables\Actions\Action::make('unverify_email')
                            ->icon('heroicon-o-x-mark')
                            ->color('warning')
                            ->label('Unverify Email')
                            ->requiresConfirmation()
                            ->visible(fn(User $record) => $record->email_verified_at !== null)
                            ->action(function (User $record) {
                                $record->update(['email_verified_at' => null]);
                                Notification::make()
                                    ->warning()
                                    ->title('Email Unverified')
                                    ->body('User email has been marked as unverified.')
                                    ->send();
                            })
                    ])
                        ->label('Email Actions'),
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\Action::make('active')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->label('active')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'active']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            }),
                        Tables\Actions\Action::make('blacklist')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->label('blacklist')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'blacklist']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            })
                    ])
                        ->label('Ubah Status User'),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Photo viewing removed - now handled by Customer model
                    Tables\Actions\DeleteAction::make(),


                ])
                    ->label('Lihat/Ubah User')
                    ->icon('heroicon-o-eye'),
                ActivityLogTimelineTableAction::make('Activities')
                    ->timelineIcons([
                        'created' => 'heroicon-m-check-badge',
                        'updated' => 'heroicon-m-pencil-square',
                    ])
                    ->timelineIconColors([
                        'created' => 'info',
                        'updated' => 'warning',
                    ]),



            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\Action::make('active')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->label('active')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'active']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            }),
                        Tables\Actions\Action::make('blacklist')
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->label('blacklist')
                            ->requiresConfirmation()
                            ->action(function (User $record) {
                                $record->update(['status' => 'blacklist']);
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengubah Status User')
                                    ->send();
                            })
                    ])
                        ->label('Ubah Status User'),

                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Action::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $service = new UserImportExportService();
                            $userIds = $records->pluck('id')->toArray();
                            $filePath = $service->exportUsers($userIds);
                            return response()->download($filePath, 'users_selected_export_' . date('Y-m-d_H-i-s') . '.xlsx')->deleteFileAfterSend();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Note: Phone numbers and photos are now handled by Customer model
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),

        ];
    }
}
