<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Models\BamboraBatch;
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
                $batch->upload_date = date("Y-m-d");
                $batch->save();

                $response = $this->uploadBatch(
                    date_param: Carbon::createFromFormat("Y-m-d", $batch->upload_date),
                    path: $batch->filename);

                if($response->getStatusCode() == 200){
                    $responseObject = json_decode($response->getBody()->getContents(),true);
                    $batch->batch_upload_id = $responseObject['batch_id'];
                    $batch->process_date = $responseObject['process_date'];
                    $batch->status = BamboraBatchStatus::PENDING_BAMBORA_PROCESSING;
                    $batch->save();
                }else{
                    $this->error("Batch upload failed.");
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
    private function uploadBatch(Carbon $date_param, string $path){
        $passcode = config("bambora.batch_payment_passcode");
        $this->info($path);
        $link = "https://api.na.bambora.com/v1/batchpayments";

        $client = new \GuzzleHttp\Client(
            [
                "headers"=>
					[
                        "Authorization"=>"Passcode $passcode"
					],
                    "defaults" => [ "verify" => false],
                    "verify" => false
			]);

        return $client->request("POST",$link,[
            "multipart"=>
					[
                        [
                            "name"=>"criteria",
							"contents"=>'{"process_date":"'.$date_param->format("Ymd").'"}',
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
