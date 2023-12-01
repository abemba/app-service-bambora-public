<?php

namespace App\Console\Commands\Bambora;

use Illuminate\Console\Command;

class Periodic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bambora:periodic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates transactions from periodic transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Strategy: schedule the next transaction before it is due, compute next future transaction, check if it exists, if not create it.
    }
}
