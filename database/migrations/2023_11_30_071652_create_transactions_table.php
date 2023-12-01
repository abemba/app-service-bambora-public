<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->string("id")->unique();
            $table->string("bank_account_id");
            $table->bigInteger("amount");
            $table->string("status");
            $table->enum("type",["credit","debit"]);
            $table->date("scheduled_for");
            $table->string("bambora_batch_id")->nullable();
            $table->string("descriptor",20)->nullable();
            $table->timestamps();
            
            $table->foreign("bank_account_id")->references("id")->on("bank_accounts");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
