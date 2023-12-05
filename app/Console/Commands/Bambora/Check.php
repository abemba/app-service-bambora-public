<?php

namespace App\Console\Commands\Bambora;

use App\Enum\BamboraBatchStatus;
use App\Enum\TransactionStatus;
use App\Models\BamboraBatch;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Webhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Spatie\ArrayToXml\ArrayToXml;
use Spatie\WebhookServer\WebhookCall;

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
            $completed_transactions_ids = [];
            $rejected_transactions_ids = [];
            if(array_key_exists("record",$response_data)){
                $records = collect($response_data['record']);
                $records->each(function($item) use (&$incomplete_count, &$completed_transactions_ids, &$rejected_transactions_ids){
                    $transaction = Transaction::whereId($item['reference'])->first();

                    if(!$transaction)
                        return;

                    $status = TransactionStatus::from($transaction->status);
                    if($status == TransactionStatus::UPLOADED_TO_BAMBORA){
                        if($item['statusName'] == "Rejected/Declined"){
                            $transaction->status = TransactionStatus::ERROR_REJECTED;
                            $rejected_transactions_ids[$transaction->bank_account_id][] = $transaction->id;
                        }elseif($item['stateName'] == "Completed"){
                            $transaction->status = TransactionStatus::COMPLETED;
                            $completed_transactions_ids[$transaction->bank_account_id][] = $transaction->id;
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

            /** Completed webhook **/
            $this->sendHook($completed_transactions_ids);

            /** Rejected webhook **/
            $this->sendHook($rejected_transactions_ids);
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

    private function sendHook(array $account_transactions): void{
        $webhook_config_cache = [];
        foreach ($account_transactions as $account_id => $transactions){
            $account = BankAccount::whereId($account_id)->first();
            if(array_key_exists($account->app_name,$webhook_config_cache)){
                $webhook_config = $webhook_config_cache[$account_id];
            }else{
                $webhook_config = Webhook::whereAppName($account->app_name)->first();
                $webhook_config_cache[$account_id] = $webhook_config;
            }

            if($webhook_config){
                $call = WebhookCall::create()->url($webhook_config->endpoint);
                if($webhook_config->secret){
                    $call = $call->useSecret($webhook_config->secret);
                }else{
                    $call = $call->doNotSign();
                }
                    $call->payload([
                        "bank_account_id" => $account_id,
                        "transactions" => $transactions,
                        "status" => TransactionStatus::COMPLETED->value
                    ])->dispatchSync();
            }
        }
    }
}
