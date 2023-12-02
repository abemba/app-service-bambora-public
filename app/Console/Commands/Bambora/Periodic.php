<?php

namespace App\Console\Commands\Bambora;

use App\Enum\PeriodicTransactionStatus;
use App\Enum\TransactionStatus;
use App\Models\PeriodicTransaction;
use App\Models\Transaction;
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
        $query = PeriodicTransaction::whereStatus(PeriodicTransactionStatus::ACTIVE);

        $query->where("completed_on","<",date("Y-m-d"))->update(["status" => PeriodicTransactionStatus::COMPLETED]);

        $periods = $query->where("completed_on",">=",date("Y-m-d"))->orWhereNull("completed_on")->get();

        $periods->each(function(PeriodicTransaction $item){
            $next_date = $item->getNextDate();
            if(!Transaction::wherePeriodicTransactionId($item->id)->whereScheduledFor($next_date->format("Y-m-d"))->exists()){
                $transaction = new Transaction(
                    [
                        "type" => $item->type,
                        "amount" => $item->amount,
                        "descriptor" => $item->descriptor,
                    ]);
                $transaction->scheduled_for = $next_date->format("Y-m-d");
                $transaction->status = TransactionStatus::SCHEDULED;
                $transaction->id = $transaction->generateUniqueId();
                $transaction->bank_account_id = $item->bank_account_id;
                $transaction->periodic_transaction_id = $item->id;
                $transaction->save();
                
                $this->info("Created: ".$transaction->id);
            }
        });
    }
}
