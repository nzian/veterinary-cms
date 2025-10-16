<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        return [
            'prod_id' => Product::inRandomOrder()->first()->prod_id,
            'appoint_id' => Appointment::inRandomOrder()->first()->appoint_id,
            'serv_id' => Service::inRandomOrder()->first()->serv_id,
            'quantity_change' => $this->faker->randomFloat(2, -10, 10),
            'transaction_type' => $this->faker->randomElement(['purchase','service_usage','adjustment','return','waste','damage','pullout']),
            'reference' => $this->faker->optional()->uuid(),
            'notes' => $this->faker->optional()->sentence(),
            'performed_by' => User::whereIn('user_role', ['receptionist', 'veterinarian'])->inRandomOrder()->first()->user_id,
        ];
    }
}