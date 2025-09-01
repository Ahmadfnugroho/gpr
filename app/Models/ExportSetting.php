<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_name',
        'column_settings',
    ];

    protected $casts = [
        'column_settings' => 'array',
    ];

    /**
     * Get export settings for a specific resource
     */
    public static function getSettings(string $resourceName): array
    {
        $setting = static::where('resource_name', $resourceName)->first();
        
        if (!$setting) {
            // Return default settings if not found
            return [
                'excluded_columns' => ['serial_numbers', 'customer_phone'],
                'included_columns' => [
                    'booking_transaction_id',
                    'customer_name',
                    'customer_email',
                    'product_info',
                    'start_date',
                    'end_date',
                    'duration',
                    'grand_total',
                    'down_payment',
                    'remaining_payment',
                    'booking_status',
                    'promo_applied',
                    'additional_services_info',
                    'cancellation_fee',
                    'note',
                    'created_at'
                ]
            ];
        }

        return $setting->column_settings;
    }

    /**
     * Update export settings for a specific resource
     */
    public static function updateSettings(string $resourceName, array $settings): void
    {
        static::updateOrCreate(
            ['resource_name' => $resourceName],
            ['column_settings' => $settings]
        );
    }

    /**
     * Get all available columns for a resource
     */
    public static function getAvailableColumns(string $resourceName): array
    {
        $columns = [
            'TransactionResource' => [
                'booking_transaction_id' => 'Transaction ID',
                'customer_name' => 'Customer Name',
                'customer_email' => 'Customer Email',
                'customer_phone' => 'Customer Phone',
                'product_info' => 'Products/Bundles',
                'detail_transactions' => 'Detail Transactions',
                'serial_numbers' => 'Serial Numbers',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'duration' => 'Duration (Days)',
                'grand_total' => 'Grand Total',
                'down_payment' => 'Down Payment',
                'remaining_payment' => 'Remaining Payment',
                'booking_status' => 'Status',
                'promo_applied' => 'Promo Applied',
                'additional_services_info' => 'Additional Services',
                'cancellation_fee' => 'Cancellation Fee',
                'note' => 'Notes',
                'created_at' => 'Created At',
            ]
        ];

        return $columns[$resourceName] ?? [];
    }
}
