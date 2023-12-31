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
        Schema::create('bambora_batches', function (Blueprint $table) {
            $table->string("id")->unique()->primary();
            $table->string("filename");
            $table->string("batch_upload_id")->nullable();
            $table->string("process_date")->nullable();
            $table->string("status");
            $table->string("upload_date")->nullable();
            $table->string("scheduled_date");
            $table->unsignedBigInteger("count")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bambora_batches');
    }
};
