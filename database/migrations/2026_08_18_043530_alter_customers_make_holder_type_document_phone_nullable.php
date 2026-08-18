<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('holder_type_id')->nullable()->change();
            $table->string('document', 14)->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('holder_type_id')->nullable(false)->change();
            $table->string('document', 14)->nullable(false)->change();
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
