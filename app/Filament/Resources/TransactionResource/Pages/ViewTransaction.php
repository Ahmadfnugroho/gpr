<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    /**
     * Configure the query to eager load necessary relationships
     */
    protected function configureTableQuery(Builder $query): Builder
    {
        return $query->with([
            'customer:id,name,email',
            'customer.customerPhoneNumbers:id,customer_id,phone_number',
            'detailTransactions:id,transaction_id,product_id,bundling_id,quantity',
            'detailTransactions.product:id,name,price',
            'detailTransactions.bundling:id,name,price',
            'detailTransactions.bundling.bundlingProducts:id,bundling_id,product_id,quantity',
            'detailTransactions.bundling.bundlingProducts.product:id,name,price',
            'detailTransactions.productItems:id,serial_number,product_id',
            'promo:id,name,type,rules'
        ]);
    }

    /**
     * Override the resolveRecord method to ensure proper eager loading
     */
    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getModel()::query()
            ->with([
                'customer:id,name,email',
                'customer.customerPhoneNumbers:id,customer_id,phone_number',
                'detailTransactions:id,transaction_id,product_id,bundling_id,quantity',
                'detailTransactions.product:id,name,price',
                'detailTransactions.bundling:id,name,price',
                'detailTransactions.bundling.bundlingProducts:id,bundling_id,product_id,quantity',
                'detailTransactions.bundling.bundlingProducts.product:id,name,price',
                'detailTransactions.productItems:id,serial_number,product_id',
                'promo:id,name,type,rules'
            ])
            ->findOrFail($key);
    }
}
