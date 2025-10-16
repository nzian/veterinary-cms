<?php

namespace Database\Factories;

use App\Models\Billing;
use App\Models\Order;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingFactory extends Factory
{
    protected $model = Billing::class;

    public function definition(): array
    {
        return [
            'bill_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            // associate with existing factories for related models
            'ord_id' => Order::inRandomOrder()->first()->ord_id,
            'appoint_id' => Appointment::inRandomOrder()->first()->appoint_id,
            'bill_status' => $this->faker->randomElement(['Pending', 'Paid', 'Cancelled']),
        ];
    }
}