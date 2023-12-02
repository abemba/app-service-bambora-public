<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Enum\TransactionStatus;
use App\Models\BamboraBatch;
use App\Models\Transaction;
use Carbon\Carbon;
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
        $batch_query = BamboraBatch::whereStatus(BamboraBatchStatus::CREATED);
        $batch_items = $batch_query->get();

        try {
            $batch_items->each(function($batch){
                $batch->upload_date = Carbon::now()->format("Y-m-d");
                $batch->save();

                $process_date = Carbon::now();
                if($process_date->isWeekend()){
                    $process_date = $process_date->nextWeekday();
                }

                $response = $this->uploadBatch(
                    process_date: $process_date,
                    path: $batch->filename);

                if($response->getStatusCode() == 200){
                    $responseObject = json_decode($response->getBody()->getContents(),true);
                    $batch->batch_upload_id = $responseObject['batch_id'];
                    $batch->process_date = $responseObject['process_date'];
                    $batch->status = BamboraBatchStatus::PENDING_BAMBORA_PROCESSING;
                    $batch->save();

                    Transaction::whereBamboraBatchId($batch->id)->update(["status" => TransactionStatus::UPLOADED_TO_BAMBORA]);
                }else{
                    $this->error("Batch upload attempt failed.");
                }

                sleep(15);
            });

        }catch (\Exception $e){
            $this->error("An error occur: ".$e->getMessage());
        }
    }


    /**
     * Uploads a batch file to Bambora
     */
    private function uploadBatch(Carbon $process_date, string $path){
        $passcode = config("bambora.batch_payment_passcode");
        $this->info($path);
        $link = "https://api.na.bambora.com/v1/batchpayments";

        $client = new \GuzzleHttp\Client(
            [
                "headers"=>
					[
                        "Authorization"=>"Passcode $passcode"
					],
                    "verify" => false
			]);

        return $client->request("POST",$link,[
            "multipart"=>
					[
                        [
                            "name"=>"criteria",
							"contents"=>'{"process_date":"'.$process_date->format("Ymd").'"}',
							"headers"=>["content-type"=>"application/json"]
						],
						[
                            "name"=>"efts",
							"filename"=>"efts_".date("Y_M_d").".csv",
							"contents"=>fopen($path,"r")
						]
					]
				]);
    }
}
