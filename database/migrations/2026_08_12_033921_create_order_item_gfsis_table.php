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
        Schema::create('order_item_gfsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('gfsis_statuses')->restrictOnDelete();
            $table->unsignedBigInteger('gfsis_order_id')->unique();
            $table->string('gfsis_code', 30)->nullable();
            $table->timestamp('status_synced_at')->nullable();
            $table->unsignedInteger('appointment_id')->nullable();
            $table->date('appointment_date')->nullable()->index();
            $table->time('appointment_time')->nullable();
            $table->date('certificate_expires_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->smallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_gfsis');
    }
};
