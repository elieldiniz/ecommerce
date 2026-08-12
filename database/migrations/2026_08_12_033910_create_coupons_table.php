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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('type_id')->constrained('coupon_types')->restrictOnDelete();
            $table->foreignId('restricted_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->decimal('value', 10, 2)->unsigned();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
