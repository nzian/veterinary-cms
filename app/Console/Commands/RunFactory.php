<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunFactory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-factory';

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
        $model = $this->argument('model'); // e.g., User
        $count = $this->option('count') ?? 1;

        $modelClass = "App\\Models\\{$model}";
        $modelClass::factory()->count($count)->create();
    }
}
