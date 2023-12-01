<?php

namespace App\Console\Commands\Bambora;

use Illuminate\Console\Command;

class Upload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bambora:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uploads transactions that are due to bambora';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
