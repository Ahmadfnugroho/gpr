<?php

namespace Database\Factories;

use App\Models\ProductItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductItemFactory extends Factory
{
    protected $model = ProductItem::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'serial_number' => 'SN-' . $this->faker->unique()->numerify('###-###'),
            'is_available' => true,
            'detail_transaction_id' => null,
        ];
    }
}
