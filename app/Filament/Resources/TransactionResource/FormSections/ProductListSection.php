<?php

namespace App\Filament\Resources\TransactionResource\FormSections;

use Filament\Forms\Components\Repeater;
use App\Filament\Resources\TransactionResource\FormSections\ProductList\Number;
use App\Filament\Resources\TransactionResource\FormSections\ProductList\ProductBundlingSelect;
use App\Filament\Resources\TransactionResource\FormSections\ProductList\QuantityInput;
use App\Filament\Resources\TransactionResource\FormSections\ProductList\SerialNumberSelect;
use App\Filament\Resources\TransactionResource\FormSections\ProductList\Placeholders;

class ProductListSection
{
    public static function getSchema(): array
    {
        return [
            Repeater::make('DetailTransactions')
                ->relationship()
                ->label('Tambah Produk/Bundling')
                ->schema([
                    ProductBundlingSelect::make(),
                    QuantityInput::make(),
                    SerialNumberSelect::make(),
                    Placeholders::productNameDisplay(),
                    Placeholders::availableQuantityDisplay(),
                    Placeholders::price(),
                    Placeholders::totalPricePlaceholder(),
                ])
                ->columns(2)
                ->addActionLabel('Tambah Produk')
                ->columnSpanFull(),
        ];
    }
}
