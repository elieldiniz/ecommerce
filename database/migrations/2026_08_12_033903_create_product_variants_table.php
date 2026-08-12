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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('certificate_format_id')->constrained('certificate_formats')->restrictOnDelete();
            $table->string('sku', 40)->unique();
            $table->smallInteger('validity_months');
            $table->decimal('price', 10, 2)->unsigned();
            $table->decimal('promotional_price', 10, 2)->unsigned()->nullable();
            $table->dateTime('promotion_starts_at')->nullable();
            $table->dateTime('promotion_ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'certificate_format_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
