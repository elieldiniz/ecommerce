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
        Schema::create('integration_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained('queue_job_statuses')->restrictOnDelete();
            $table->string('job', 60)->index();
            $table->string('reference_type', 60);
            $table->unsignedBigInteger('reference_id')->index();
            $table->json('payload')->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('run_at')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_queue');
    }
};
