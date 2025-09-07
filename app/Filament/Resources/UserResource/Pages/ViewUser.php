<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\RelationManagers;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getInfolistContents(): array
    {
        return [
            TextColumn::make('name')->label('Name'),
            TextColumn::make('email')->label('Email'),
            TextColumn::make('user_phone_numbers.0.phone_number')->label('Phone Number'),
            TextColumn::make('status')->label('Status'),
        ];
    }

    protected function getRelations(): array
    {
        return [
            RelationManagers\UserPhoneNumberRelationManager::class,
        ];
    }

   }
   
