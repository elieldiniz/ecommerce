<?php

namespace App\Jobs;

use App\Models\PaymentEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSafe2PayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PaymentEvent $paymentEvent) {}

    /**
     * Interpretação do status e aplicação da regra de peso (RF-18, RF-21) e
     * dos efeitos colaterais de negócio (RF-09, RF-10, RF-15, RF-17) — a
     * implementar em fase futura desta feature.
     */
    public function handle(): void {}
}
