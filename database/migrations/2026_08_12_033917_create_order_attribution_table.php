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
        Schema::create('order_attribution', function (Blueprint $table) {
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->primary('order_id');
            $table->foreignId('device_type_id')->constrained('device_types')->restrictOnDelete();
            $table->string('gclid', 255)->nullable()->index();
            $table->string('utm_source', 180)->nullable();
            $table->string('utm_medium', 180)->nullable();
            $table->string('utm_campaign', 180)->nullable();
            $table->string('utm_term', 180)->nullable();
            $table->string('utm_content', 180)->nullable();
            $table->string('landing_page', 255);
            $table->string('referrer', 255)->nullable();
            $table->timestamp('first_touch_at');
            $table->smallInteger('sessions_before_purchase')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_attribution');
    }
};
