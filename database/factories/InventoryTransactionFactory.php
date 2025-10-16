<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        return [
            'prod_id' => null,
            'appoint_id' => null,
            'serv_id' => null,
            'quantity_change' => $this->faker->randomFloat(2, -10, 10),
            'transaction_type' => $this->faker->randomElement(['in','out','adjustment']),
            'reference' => $this->faker->optional()->uuid(),
            'notes' => $this->faker->optional()->sentence(),
            'performed_by' => null,
        ];
    }
}