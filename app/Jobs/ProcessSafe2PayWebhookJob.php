<?php

namespace App\Jobs;

use App\Actions\Refunds\CreateRefund;
use App\Models\IntegrationQueueJob;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentStatus;
use App\Models\QueueJobStatus;
use App\Models\RefundReason;
use App\Models\Role;
use App\Models\User;
use App\Support\Safe2Pay\TransactionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProcessSafe2PayWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * E-mail fixo da conta de sistema usada para atribuir `refunds.user_id`/
     * `audit_logs.user_id` de reembolsos automáticos (RF-33), já que essas
     * colunas são `NOT NULL` e uma tentativa duplicada nunca tem um operador
     * de painel por trás.
     */
    private const SYSTEM_USER_EMAIL = 'sistema@digitallock.com.br';

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
     * Os efeitos colaterais de negócio de RF-10, RF-15 e RF-17 são
     * implementados em fase futura desta feature; os de RF-09/RF-24/RF-33
     * (autorização, múltiplos pagamentos e duplicata) já estão cobertos.
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

            if ($targetSlug === 'authorized') {
                $this->applyAuthorizedSideEffects($payment);
            }
        }

        $this->paymentEvent->update(['processed_at' => now()]);
    }

    /**
     * RF-09/RF-24: grava `payments.paid_at` e, apenas na primeira autorização
     * do pedido, move `orders.status_id`/`orders.paid_at` e sinaliza a
     * elegibilidade para o ciclo de emissão futuro via `integration_queue`.
     * RF-33: uma autorização subsequente de um pedido já `paid` nunca move
     * `orders.status_id` novamente, abrindo um reembolso automático de
     * duplicata em vez disso.
     */
    private function applyAuthorizedSideEffects(Payment $payment): void
    {
        $payment->update(['paid_at' => now()]);

        $order = $payment->order;

        if ($order->status->slug !== 'paid') {
            DB::transaction(function () use ($order): void {
                $order->update([
                    'status_id' => OrderStatus::query()->where('slug', 'paid')->value('id'),
                    'paid_at' => now(),
                ]);

                IntegrationQueueJob::query()->create([
                    'status_id' => QueueJobStatus::query()->where('slug', 'pending')->value('id'),
                    'job' => 'send_to_gfsis',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'run_at' => now(),
                ]);
            });

            return;
        }

        (new CreateRefund)->execute(
            $payment,
            RefundReason::query()->where('slug', 'duplicate')->firstOrFail(),
            $this->systemUser(),
            'duplicate_payment_auto_refund',
            '127.0.0.1',
        );
    }

    /**
     * Conta de sistema usada para atribuir a autoria de efeitos colaterais
     * automáticos (RF-33) que não têm um operador de painel por trás;
     * criada sob demanda, nunca usada para login (`is_active = false`).
     */
    private function systemUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => self::SYSTEM_USER_EMAIL],
            [
                'name' => 'Sistema',
                'password' => Hash::make(Str::random(40)),
                'role_id' => Role::query()->where('slug', 'admin')->value('id'),
                'is_active' => false,
            ]
        );
    }
}
