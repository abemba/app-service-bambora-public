<?php

namespace App\Http\Controllers;

use Algofame\Internal\App\Service\Auth\AppAuth;
use App\Enum\TransactionStatus;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;

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
    ]);
        $account = BankAccount::whereAppName($authToken->getAppName())
        ->whereAccountNumber($request->input("account_number"))
        ->whereBankNumber($request->input("bank_number"))
        ->whereBranchNumber($request->input("branch_number"))
        ->first();
        
        if($account){
            return $account;
        }
            
        $account = new BankAccount($request->only(["account_number","bank_number","branch_number","app_name"]));
        $account->id = $account->generateUniqueId();
        $account->save();
        
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
     * Gets a transaction
     */
    public function getTransaction(BankAccount $account,Transaction $transaction){
        return $transaction;
    }
}
