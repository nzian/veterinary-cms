<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateSeeders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-seeders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $models = [
            'Appointment',
            'AppointServ', 
            'Biling',
            'Branch',
            'Equipment',
            'InventoryTransaction',
            'MedicalHistory',
            'Order',
            'Owner',
            'Payment',
            'Pet',
            'Prescription',
            'Product',
            'Service',
            'ServiceProduct',
            'User',
        ]; // Add all your model names here

    foreach ($models as $model) {
        $seederClass = "{$model}Seeder";
        $modelClass = "App\\Models\\{$model}";
        $seederPath = database_path("seeders/{$seederClass}.php");

        if (!file_exists($seederPath)) {
            Artisan::call("make:seeder", ['name' => $seederClass]);
        }

        file_put_contents($seederPath, "<?php

        namespace Database\Seeders;

        use Illuminate\Database\Seeder;
        use {$modelClass};

        class {$seederClass} extends Seeder
        {
            public function run(): void
            {
                {$model}::factory()->count(500)->create();
            }
        }
        ");
        }
    }
}
