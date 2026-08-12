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
        Schema::create('issuance_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->restrictOnDelete();
            $table->foreignId('holder_type_id')->constrained('holder_types')->restrictOnDelete();
            $table->string('holder_name', 180);
            $table->string('document', 14)->index();
            $table->date('birth_date')->nullable();
            $table->string('email', 180);
            $table->string('phone', 20);
            $table->string('postal_code', 8);
            $table->string('street', 180);
            $table->string('number', 20);
            $table->string('complement', 120)->nullable();
            $table->string('neighborhood', 120);
            $table->string('city', 120);
            $table->char('state', 2);
            $table->string('ibge_code', 7)->nullable();
            $table->string('responsible_name', 180)->nullable();
            $table->string('responsible_document', 11)->nullable();
            $table->date('responsible_birth_date')->nullable();
            $table->string('responsible_email', 180)->nullable();
            $table->string('responsible_phone', 20)->nullable();
            $table->char('access_token', 40)->unique();
            $table->timestamp('access_token_expires_at');
            $table->timestamp('filled_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issuance_data');
    }
};
