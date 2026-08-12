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
        Schema::create('ads_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('ads_conversion_statuses')->restrictOnDelete();
            $table->string('transaction_id', 60)->unique();
            $table->string('gclid', 255)->nullable();
            $table->decimal('amount', 10, 2)->unsigned();
            $table->char('currency', 3)->default('BRL');
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads_conversions');
    }
};
