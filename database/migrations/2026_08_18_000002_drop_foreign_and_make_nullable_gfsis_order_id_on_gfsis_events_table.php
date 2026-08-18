<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove a FK entre `gfsis_events.gfsis_order_id` e
     * `order_item_gfsis.gfsis_order_id` e torna a coluna nullable — o webhook
     * pode referenciar um `gfsis_order_id` desconhecido localmente (ainda sem
     * `order_item_gfsis` correspondente) e esse valor real do payload precisa
     * ser gravado como recebido, não como `NULL` (RF-11). A coluna também
     * passa a aceitar `NULL` para o caso raro de payload sem identificador.
     */
    public function up(): void
    {
        Schema::table('gfsis_events', function (Blueprint $table) {
            $table->dropForeign(['gfsis_order_id']);
            $table->unsignedBigInteger('gfsis_order_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Recria a FK e reverte a nulabilidade. Em produção, isso exige limpar ou
     * realocar previamente quaisquer linhas com `gfsis_order_id` nulo ou
     * órfão gravadas após o deploy desta migration, sob pena de falhar.
     */
    public function down(): void
    {
        Schema::table('gfsis_events', function (Blueprint $table) {
            $table->unsignedBigInteger('gfsis_order_id')->nullable(false)->change();
            $table->foreign('gfsis_order_id')->references('gfsis_order_id')->on('order_item_gfsis')->restrictOnDelete();
        });
    }
};
