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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 60);
            $table->string('entity', 60);
            $table->unsignedBigInteger('entity_id');
            $table->json('data_before')->nullable();
            $table->json('data_after')->nullable();
            $table->string('ip_address', 45);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
