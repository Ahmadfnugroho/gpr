<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;



class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'detailTransactions';

    public function form(Form $form): Form
    {
        return $form;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name') // Menentukan atribut yang digunakan untuk judul record
            ->columns([
                // Menampilkan nama produk dari relasi 'product'
                Tables\Columns\TextColumn::make('product.name') // 'product.name' mengacu pada relasi 'product' dan field 'name' di model Product
                    ->label('Product Name') // Memberi label untuk kolom tersebut
                    ->searchable() // Menambahkan kemampuan pencarian pada kolom ini
                    ->sortable(), // Menambahkan kemampuan pengurutan pada kolom ini
                Tables\Columns\TextColumn::make('product.rentalIncludes.includedProduct.name')
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
