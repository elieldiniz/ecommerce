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
        Schema::create('gfsis_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gfsis_order_id')->index();
            $table->foreign('gfsis_order_id')->references('gfsis_order_id')->on('order_item_gfsis')->restrictOnDelete();
            $table->char('event_hash', 64)->unique();
            $table->string('received_status', 40);
            $table->json('payload');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gfsis_events');
    }
};
