<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'bill_id' => null,
            'pay_total' => $this->faker->randomFloat(2, 0, 10000),
            'pay_cashAmount' => $this->faker->randomFloat(2, 0, 10000),
            'pay_change' => 0,
        ];
    }
}