<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateFactoriesForAllModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-factories-for-all-models';

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
        // Path to your models and factories
        $modelPath = app_path('Models');
        $factoryPath = database_path('factories');

        $models = collect(scandir($modelPath))
            ->filter(fn($file) => str_ends_with($file, '.php'))
            ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME));

        foreach ($models as $model) {
            $factoryClass = "{$model}Factory";
            $factoryFile = "{$factoryClass}.php";
            $factoryFullPath = "{$factoryPath}/{$factoryFile}";
            $modelClass = "App\\Models\\{$model}";

            if (!file_exists($factoryFullPath)) {
                $this->call('make:factory', [
                    'name' => $factoryClass,
                    '--model' => $modelClass,
                ]);

                // Optional: Add basic fields if you want to auto-fill with Faker
                $stub = <<<PHP
                <?php

                namespace Database\Factories;

                use {$modelClass};
                use Illuminate\Database\Eloquent\Factories\Factory;

                class {$factoryClass} extends Factory
                {
                    protected \$model = {$modelClass}::class;

                    public function definition(): array
                    {
                        return [
                            // Add your fields here, e.g.:
                            // 'name' => \$this->faker->name,
                            // 'email' => \$this->faker->unique()->safeEmail,
                        ];
                    }
                }
                PHP;
                file_put_contents($factoryFullPath, $stub);
                $this->info("✅ Factory created for {$model}");
            } else {
                $this->line("⚠️ Factory already exists for {$model}");
            }
        }
    }
}
