<?php

namespace App\Console\Commands\Bambora;

use Illuminate\Console\Command;

class Check extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bambora:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks and updates the status of transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
    }
}
