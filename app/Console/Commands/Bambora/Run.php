<?php

namespace App\Console\Commands\Bambora;

use Illuminate\Console\Command;

class Run extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bambora:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs bambora commands in appropriate order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking periodic transactions.");
        $this->call("bambora:periodic");

        $this->info("Creating CSV file");
        $this->call("bambora:file");

        $this->info("Uploading file");
        $this->call("bambora:upload");

        $this->info("Checking transaction status");
        $this->call("bambora:check");
    }
}
