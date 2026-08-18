<?php

namespace App\Jobs;

use App\Actions\Gfsis\ApplyGfsisStatusTransition;
use App\Models\GfsisEvent;
use App\Models\OrderItemGfsis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGfsisWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public GfsisEvent $gfsisEvent)
    {
        $this->onQueue('database');
    }

    /**
     * Localiza o `order_item_gfsis` correspondente (tratando o caso órfão,
     * quando `gfsis_order_id` é nulo ou desconhecido — RF-11 v1.3), delega a
     * interpretação de status a `ApplyGfsisStatusTransition` (RF-12) e grava
     * `processed_at`/`error` ao final, sem nunca propagar exceção (RNF-04 —
     * a interpretação assíncrona nunca deve travar a fila).
     */
    public function handle(): void
    {
        $orderItemGfsis = $this->gfsisEvent->gfsis_order_id === null
            ? null
            : OrderItemGfsis::query()->where('gfsis_order_id', $this->gfsisEvent->gfsis_order_id)->first();

        if ($orderItemGfsis === null) {
            $this->gfsisEvent->update([
                'error' => 'gfsis_order_id desconhecido ou ausente: nenhum order_item_gfsis correspondente',
                'processed_at' => now(),
            ]);

            return;
        }

        try {
            (new ApplyGfsisStatusTransition)->execute($orderItemGfsis, $this->gfsisEvent->payload);
        } catch (\Throwable $exception) {
            $this->gfsisEvent->update(['error' => $exception->getMessage()]);
        }

        $this->gfsisEvent->update(['processed_at' => now()]);
    }
}
