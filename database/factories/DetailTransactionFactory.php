<?php

namespace Database\Factories;

use App\Models\DetailTransaction;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Bundling;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailTransactionFactory extends Factory
{
    protected $model = DetailTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'bundling_id' => null,
            'product_id' => Product::factory(),
            'quantity' => 1,
            'price' => $this->faker->numberBetween(10000, 100000),
            'total_price' => $this->faker->numberBetween(10000, 100000),
            'serial_numbers' => [],
        ];
    }
}
