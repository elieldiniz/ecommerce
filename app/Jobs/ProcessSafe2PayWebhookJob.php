<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentStatus;
use App\Support\Safe2Pay\TransactionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSafe2PayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public PaymentEvent $paymentEvent)
    {
        $this->onQueue('database');
    }

    /**
     * Interpreta o status recebido (RF-18) e aplica a transição de
     * `payments.status_id` apenas quando o peso do status alvo é estritamente
     * maior que o peso atual (RF-21). `payment_events.processed_at` é gravado
     * ao final independentemente do resultado, e um evento órfão (sem
     * `Payment` correspondente) registra `payment_events.error` sem lançar
     * exceção não tratada.
     *
     * Os efeitos colaterais de negócio (RF-09, RF-10, RF-15, RF-17) são
     * implementados em fase futura desta feature.
     */
    public function handle(): void
    {
        $code = (int) ($this->paymentEvent->payload['TransactionStatus']['Id'] ?? 0);

        try {
            $targetSlug = TransactionStatus::fromCode($code)->toInternalStatusSlug();
        } catch (\ValueError $exception) {
            $this->paymentEvent->update([
                'error' => "Código de status Safe2Pay desconhecido: {$code} ({$exception->getMessage()})",
                'processed_at' => now(),
            ]);

            return;
        }

        $payment = Payment::query()
            ->where('gateway_transaction_id', $this->paymentEvent->gateway_transaction_id)
            ->first();

        if (! $payment) {
            $this->paymentEvent->update([
                'error' => "Nenhum Payment encontrado para gateway_transaction_id={$this->paymentEvent->gateway_transaction_id}",
                'processed_at' => now(),
            ]);

            return;
        }

        $this->paymentEvent->update(['payment_id' => $payment->id]);

        $targetStatus = PaymentStatus::query()->where('slug', $targetSlug)->first();

        if ($targetStatus && $targetStatus->weight > $payment->status->weight) {
            $payment->update(['status_id' => $targetStatus->id]);
        }

        $this->paymentEvent->update(['processed_at' => now()]);
    }
}
