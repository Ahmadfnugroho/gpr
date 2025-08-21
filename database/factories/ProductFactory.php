<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'custom_id' => 'PRD-' . strtoupper(Str::random(5)),
            'price' => $this->faker->numberBetween(10000, 100000),
            'thumbnail' => null,
            'status' => 'available',
            'slug' => $this->faker->unique()->slug(),
            'category_id' => null,
            'brand_id' => null,
            'sub_category_id' => null,
            'premiere' => false,
        ];
    }
}
