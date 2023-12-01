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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->string("id")->unique();
            $table->string("first_name",100);
            $table->string("last_name",100);
            $table->string("middle_name",100)->nullable();
            $table->string("app_name");
            $table->string("branch_number",5);
            $table->string("account_number",12);
            $table->string("bank_number",3);
            $table->timestamps();
            
            $table->unique(["branch_number","account_number","bank_number","app_name","last_name","first_name"],"app_name_bank_account_unique");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
