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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('payment_statuses')->restrictOnDelete();
            $table->string('gateway_transaction_id', 80)->index();
            $table->string('gateway_status_code', 20)->nullable();
            $table->decimal('gross_amount', 10, 2)->unsigned();
            $table->decimal('gateway_fee', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            $table->string('pix_id', 64)->nullable();
            $table->string('end_to_end_id', 64)->nullable()->index();
            $table->text('qr_code_payload')->nullable();
            $table->string('boleto_digitable_line', 80)->nullable();
            $table->string('receipt_url', 255)->nullable();
            $table->smallInteger('installments')->nullable();
            $table->string('card_brand', 20)->nullable();
            $table->char('card_last_digits', 4)->nullable();
            $table->string('authorization_nsu', 40)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->date('expected_settlement_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
