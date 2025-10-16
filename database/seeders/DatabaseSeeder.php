<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Branch, User, Owner, Pet, Service, Product, ServiceProduct,
    Appointment, AppointServ, Order, Billing, Payment, MedicalHistory,
    Prescription, Equipment, InventoryTransaction,
    Referral
};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $count = 500; // entries per factory

        // Create branches first
        Branch::factory()->count(10)->create();

        // Users (assign some to branches)
        User::factory()->count(100)->create();

        // Products and Services
        Product::factory()->count(200)->create();
        Service::factory()->count(100)->create();

        // Service-Product pivot entries
        ServiceProduct::factory()->count(200)->create();

        // Owners and Pets
        Owner::factory()->count(100)->create();
        Pet::factory()->count(300)->create();
        Referral::factory()->count(100)->create();

        // Appointments and pivot services
        Appointment::factory()->count(500)->create();
        AppointServ::factory()->count(500)->create();

        // Orders, Billing, Payments
        Order::factory()->count(500)->create();
        Billing::factory()->count(500)->create();
        Payment::factory()->count(500)->create();

        // Medical histories, prescriptions, equipment, inventory transactions
        MedicalHistory::factory()->count(300)->create();
        Prescription::factory()->count(300)->create();
        Equipment::factory()->count(100)->create();
        InventoryTransaction::factory()->count(200)->create();

        $this->command->info('Database seeding completed.');
    }
}
