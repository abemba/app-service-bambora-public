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
        // (0)Check (1) File (2) Upload
    }
}
