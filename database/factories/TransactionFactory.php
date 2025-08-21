<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => 1, // Pastikan ada user dengan id 1 atau sesuaikan
            'booking_transaction_id' => 'TRX-' . strtoupper(Str::random(8)),
            'grand_total' => $this->faker->numberBetween(10000, 100000),
            'booking_status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'duration' => 1,
            'promo_id' => null,
            'note' => null,
            'down_payment' => 0,
            'remaining_payment' => 0,
        ];
    }
}
