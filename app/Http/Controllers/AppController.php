<?php

namespace App\Http\Controllers;

use Algofame\Internal\App\Service\Auth\AppAuth;
use App\Enum\PeriodicTransactionStatus;
use App\Enum\TransactionStatus;
use App\Models\BankAccount;
use App\Models\PeriodicTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppController extends Controller
{
    /**
     * Creates a bank account
     */
    public function createBankAccount(Request $request, AppAuth $authToken){
        $request->validate([
        "account_number" => "required|string|min:7|max:12",
        "branch_number" => "required|string|size:5",
        "bank_number" => "required|string|size:3",
        "app_name" => "required|string",
        "first_name" => "required|string",
        "last_name" => "required|string",
        "middle_name" => "string",
    ]);
        $account = BankAccount::whereAppName($authToken->getAppName())
        ->whereAccountNumber($request->input("account_number"))
        ->whereBankNumber($request->input("bank_number"))
        ->whereBranchNumber($request->input("branch_number"))
        ->whereFirstName($request->input("first_name"))
        ->whereLastName($request->input("last_name"))
        ->first();
        
        if($account){
            return $account;
        }
            
        $account = new BankAccount($request->only(["account_number","bank_number","branch_number","app_name","first_name","last_name","middle_name"]));
        $account->first_name = Str::upper($account->first_name);
        $account->last_name = Str::upper($account->last_name);
        $account->middle_name = Str::upper($account->middle_name);
        $account->id = $account->generateUniqueId();
        $account->save();
        
        return $account;
    }

    public function getPeriodicTransaction(BankAccount $account, PeriodicTransaction $periodic){
        return $periodic;
    }

    public function getAccount(BankAccount $account){
        return $account;
    }
    
    /**
     * Create transaction
     */
    public function createTransaction(Request $request, AppAuth $appAuth, BankAccount $account){
        $request->validate(
            [
        "type" => "required|in:credit,debit",
        "amount"=> "required|integer|min:1",
        "descriptor"=> "string|min:1|max:20",
        "schedule_for"=> "date|date_format:Y-m-d|after_or_equel:today",
    ]);
        
        /**
         * Only allow access if the application is the owner of the object
         */
        if($account->app_name != $appAuth->getAppName()){
            return response()->json(["message" => "Bank account id is invalid."],400);
        }
        
        $transaction = new Transaction($request->only(["type","amount","descriptor"]));
        $transaction->scheduled_for = $request->has("schedule_for") ? $request->input("schedule_for") : date("Y-m-d");
        $transaction->status = TransactionStatus::SCHEDULED;
        $transaction->id = $transaction->generateUniqueId();
        $transaction->bank_account_id = $account->id;
        $transaction->save();
        
        return $transaction;
    }
    
    /**
     * Creates a periodic transaction
     */
    public function createPeriodicTransaction(Request $request, BankAccount $account){
        $request->validate([
        "start_on" => "date|equel_or_after:today",
        "completes_on" => "date|after:start_on",
        "amount" => "required|integer|min:1",
        "frequency_in_days" => "required|integer|min:1",
        "descriptor" => "string|max:15",
        "type" => "required|string|in:credit,debit",
    ]);
        $periodic = new PeriodicTransaction(
            [
                "amount" => $request->input("amount"),
                "type" => $request->input("type"),
                "status" => PeriodicTransactionStatus::ACTIVE,
                "frequency_in_days" => $request->input("frequency_in_days"),
                "descriptor" => $request->input("descriptor"),
                "started_on" => $request->input("start_on") ?? date("Y-m-d"),
                "completed_on" => $request->input("completes_on"),
                "bank_account_id" => $account->id
            ]);
        $periodic->id = $periodic->generateUniqueId();
        $periodic->save();

        return $periodic;
    }

    /**
     * Gets a transaction
     */
    public function getTransaction(BankAccount $account,Transaction $transaction){
        return $transaction;
    }
}
