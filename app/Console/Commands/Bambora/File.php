<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Enum\TransactionStatus;
use App\Enum\TransactionType;
use App\Models\BamboraBatch;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class File extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bambora:file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a csv file of transactions that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = date("Y-m-d",strtotime("today"));
        $transactions_query = Transaction::whereStatus(TransactionStatus::SCHEDULED);
        $transactions = $transactions_query->whereScheduledFor($date)->get();
        
        if($transactions->count()){
            $filename = $this->createCsvFile($transactions);

            $batch = new BamboraBatch(["filename"=>$filename,"count"=>$transactions->count(), "status"=> BamboraBatchStatus::CREATED]);
            $batch->id = $batch->generateUniqueId();
            $batch->scheduled_date = $date;
            $batch->save();

            $transactions_query->update(["status" => TransactionStatus::FILE_CREATED, "bambora_batch_id" => $batch->id]);

            $this->alert("Transactions saved in: $filename");
        }else{
            $this->alert("No scheduled transactions.");
        }
    }
    
    private function createCsvFile(Collection $transactions): string{
        $content = "";
        
        $transactions->each(function($transaction) use(&$content){

            $account = BankAccount::whereId($transaction->bank_account_id)->first();

            // Transaction Class
    		$row = "E";
            
            // Transaction Type
            $type = TransactionType::from($transaction->type);
			$row = $row .",".$type->getBamboraType();
            
            // Bank Number
			$row = $row .",".$account->bank_number;
            
            // Transit Number
			$row = $row .",".$account->branch_number;
            
            // Account Number
			$row = $row .",".$account->account_number;
            
            // Amount
			$row = $row .",".$transaction->amount;
            
            // Reference id
			$row = $row .",".$transaction->id;
            
            // Full name
			$row = $row .",".$account->first_name." ".$account->middle_name." ".$account->last_name;
            
            // Descriptor
			$row = $row.",,".$transaction->descriptor ?? "AlgofameCA";
            
            if(!empty($content)){
                $content = $content."\n\r";
            }

			$content = $content.$row;
        });
        
        $filename = "bambora_batch_".date("Y_m_d_",strtotime("yesterday")).time();
        $path = storage_path("bambora/$filename.csv");
        $stream = fopen($path,"w+");
        
        fwrite($stream,$content);
        fclose($stream);
        
        return $path;
    }
}
