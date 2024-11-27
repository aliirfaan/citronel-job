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
        Schema::create('job_policies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->tinyInteger('active')->default(0);
            $table->integer('max_retry_count')->default(2);
            $table->integer('max_exceptions_count')->nullable(true);
            $table->integer('time_window_period')->nullable(true);
            $table->string('backoff_period')->nullable(true); // this can take integers and also string for exponential backoff
            $table->integer('delay')->nullable(true);
            $table->string('connection')->nullable(true);
            $table->string('queue')->nullable(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_policies');
    }
};
