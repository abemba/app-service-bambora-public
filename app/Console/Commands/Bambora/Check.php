<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Enum\TransactionStatus;
use App\Models\BamboraBatch;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Spatie\ArrayToXml\ArrayToXml;

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
        $batches = BamboraBatch::whereStatus(BamboraBatchStatus::PENDING_BAMBORA_PROCESSING)->get();

        $this->newLine();
        $this->alert("Checking status of ".$batches->count()." batches on ".date("Y-m-d"));

        $batches->each(function($batch){
            $this->info("Batch: ".$batch->id);
            $this->info("Count: ".$batch->count);
            $xml=$this->build_xml($batch->batch_upload_id);
            /**
		      * Create http request
		     */
        	$response = Http::withoutVerifying()->withHeaders(["content-type"=>"application/xml"])
    			->withBody($xml,"application/xml")
    			->post("https://api.na.bambora.com/scripts/reporting/report.aspx");

            $data = json_decode($response->body(), true);
            $response_data = $data['response'];
            $incomplete_count = 0;
            if(array_key_exists("record",$response_data)){
                $records = collect($response_data['record']);
                $records->each(function($item) use (&$incomplete_count){
                    $transaction = Transaction::whereId($item['reference'])->first();

                    if(!$transaction)
                        return;

                    $status = TransactionStatus::from($transaction->status);
                    if($status == TransactionStatus::UPLOADED_TO_BAMBORA){
                        if($item['statusName'] == "Rejected/Declined"){
                            $transaction->status = TransactionStatus::ERROR_REJECTED;
                        }elseif($item['stateName'] == "Completed"){
                            $transaction->status = TransactionStatus::COMPLETED;
                        }else{
                            $incomplete_count++;
                        }
                        $transaction->save();
                    }
                });
            }

            if($incomplete_count == 0){
                $batch->status = BamboraBatchStatus::COMPLETED;
                $batch->save();
            }

            $this->info("Pending: $incomplete_count");
            $this->info("Completed: ".($incomplete_count ? 'no':'yes'));
            $this->newLine();
        });
    }

    private  function build_xml($batch_id,$starting_row =1){
        $array =
        [
            "rptVersion" => "2.0",
            "serviceName" => "BatchPaymentsEFT",
            "merchantId" => config("bambora.merchant_id"),
            "passCode" => config("bambora.batch_report_passcode"),
            "sessionSource" => "external",
            "rptFormat" => "JSON",
            "rptFilterBy1" => "batch_id",
            "rptOperationType1" => "EQ",
            "rptFilterValue1" => $batch_id,
            "rptStartRow" => $starting_row
        ];
        return ArrayToXml::convert(array: $array, rootElement: "request", xmlEncoding: "UTF-8");
    }
}
