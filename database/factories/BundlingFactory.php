<?php

namespace Database\Factories;

use App\Models\Bundling;
use Illuminate\Database\Eloquent\Factories\Factory;

class BundlingFactory extends Factory
{
    protected $model = Bundling::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'price' => $this->faker->numberBetween(10000, 100000),
            'premiere' => false,
        ];
    }
}
