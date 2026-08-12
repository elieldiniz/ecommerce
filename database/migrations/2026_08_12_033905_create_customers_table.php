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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holder_type_id')->constrained('holder_types')->restrictOnDelete();
            $table->string('legal_name', 180);
            $table->string('document', 14)->unique();
            $table->string('email', 180)->unique();
            $table->string('phone', 20);
            $table->string('password_hash')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
