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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('order_statuses')->restrictOnDelete();
            $table->foreignId('fulfillment_status_id')->constrained('order_fulfillment_statuses')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->restrictOnDelete();
            $table->decimal('subtotal', 10, 2)->unsigned();
            $table->decimal('coupon_discount', 10, 2)->unsigned()->default(0);
            $table->decimal('payment_method_discount', 10, 2)->unsigned()->default(0);
            $table->decimal('total', 10, 2)->unsigned();
            $table->string('ip_address', 45);
            $table->string('user_agent', 255);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index(['customer_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
