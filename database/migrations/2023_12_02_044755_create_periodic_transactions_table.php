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
        Schema::create('periodic_transactions', function (Blueprint $table) {
            $table->string("id")->unique()->primary();
            $table->foreignIdFor(\App\Models\BankAccount::class);
            $table->date("started_on");
            $table->date("completed_on")->nullable();
            $table->unsignedBigInteger("frequency_in_days");
            $table->unsignedBigInteger("amount");
            $table->string("descriptor")->nullable();
            $table->string("status");
            $table->enum("type",["credit","debit"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodic_transactions');
    }
};
