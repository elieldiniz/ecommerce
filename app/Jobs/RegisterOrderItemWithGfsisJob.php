<?php

namespace App\Jobs;

use App\Actions\Gfsis\RegisterOrderItemWithGfsis;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Consumidor assíncrono do sinal `send_to_gfsis` (`integration_queue`) e do
 * gatilho de dados completos (RF-03). Delega o registro em si a
 * `RegisterOrderItemWithGfsis`, reaproveitada também pelo reenvio manual
 * síncrono do painel ("Corrigir e reenviar").
 */
class RegisterOrderItemWithGfsisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->onQueue('database');
    }

    public function handle(): void
    {
        app(RegisterOrderItemWithGfsis::class)->execute($this->order);
    }
}
