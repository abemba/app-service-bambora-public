<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(\App\Http\Controllers\AppController::class)
->middleware(\Algofame\Internal\App\Service\Auth\AppAuthMiddleware::class)
->group(function(){
    Route::post("webhook","updateWebhookConfig");
    Route::post("accounts","createBankAccount");
    Route::post("accounts/{account}","getAccount");
    
    Route::post("accounts/{account}/transactions","createTransaction");
    Route::get("accounts/{account}/transactions/{transaction}","getTransaction");

    Route::post("accounts/{account}/periodic","createPeriodicTransaction");
    Route::get("accounts/{account}/periodic/{periodic}","getPeriodicTransaction");
});