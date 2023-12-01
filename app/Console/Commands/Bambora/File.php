<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Enum\TransactionStatus;
use App\Enum\TransactionType;
use App\Models\BamboraBatch;
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
            $transactions_query->update(["status" => TransactionStatus::PROCESSING]);
            $filename = $this->createCsvFile($transactions);
            $batch = new BamboraBatch(["filename"=>$filename, "status"=> BamboraBatchStatus::CREATED]);
            $batch->id = $batch->generateUniqueId();
            $batch->save();
            $this->info("Transactions saved in: $filename");
        }else{
            $this->alert("No scheduled transactions.");
        }
    }
    
    private function createCsvFile(Collection $transactions): string{
        $content = "";
        
        $transactions->each(function($item) use(&$content){
            // Transaction Class
    		$row = "E";
            
            // Transaction Type
            $type = TransactionType::from($item->type);
			$row = $row .",".$type->getBamboraType();
            
            // Bank Number
			$row = $row .",".$item->bank_number;
            
            // Transit Number
			$row = $row .",".$item->branch_number;
            
            // Account Number
			$row = $row .",".$item->account_number;
            
            // Amount
			$row = $row .",".$item->amount;
            
            // Reference id
			$row = $row .",".$item->id;
            
            // Full name
			$row = $row .",".$item->first_name." ".$item->middle_name." ".$item->last_name;
            
            // Descriptor
			$row = $row.",,".$item->descriptor ?? "AlgofameCA";
            
            if(!empty($content)){
                $content = $content."\n\r";
            }

			$content = $content.$row;
        });
        
        $filename = "bambora_batch_".date("Y_m_d_",strtotime("yesterday")).time();
        $path = storage_path("bambora/$filename");
        $stream = fopen($path,"w+");
        
        fwrite($stream,$content);
        fclose($stream);
        
        return $path;
    }
}
