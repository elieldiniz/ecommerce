<?php

namespace App\Console\Commands;

use App\Actions\Payments\ApplyPaymentStatusTransition;
use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\Setting;
use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('payments:reconcile')]
#[Description('Consulta a Safe2Pay (CT-04) para pagamentos pendentes além do prazo, fechando a lacuna de webhooks perdidos (RF-11, RF-23).')]
class ReconcilePendingPayments extends Command
{
    /**
     * RF-11/RF-23: consulta `GET /v2/payment/{id}` (CT-04) para Pix
     * `pending`/`under_review` cujo `expires_at` já passou e para Cartão/
     * Boleto `pending`/`under_review` sem `expires_at`, criados há mais tempo
     * que o limiar configurável em `settings`, reaproveitando a mesma regra
     * de peso do webhook (`ApplyPaymentStatusTransition`, T18) para nunca
     * duplicá-la.
     */
    public function handle(): int
    {
        $pendingStatusIds = PaymentStatus::query()->whereIn('slug', ['pending', 'under_review'])->pluck('id');

        $this->reconcilePix($pendingStatusIds);
        $this->reconcileCardAndBoleto($pendingStatusIds);

        return self::SUCCESS;
    }

    /**
     * RF-11: Pix `pending`/`under_review` cujo `expires_at` já passou sem que
     * nenhum webhook de `Autorizado` tenha sido recebido. Se a consulta a
     * CT-04 não resultar em `authorized` (nem em algo de peso igual/maior,
     * ex. `reversed`), o pagamento é marcado `expired` diretamente — a
     * expiração de Pix é uma regra de tempo da aplicação, não depende do que
     * a Safe2Pay reporta.
     *
     * @param  Collection<int, int>  $pendingStatusIds
     */
    private function reconcilePix(Collection $pendingStatusIds): void
    {
        Payment::query()
            ->whereIn('status_id', $pendingStatusIds)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(fn (Payment $payment) => $this->reconcile($payment, forcePixExpiration: true));
    }

    /**
     * RF-23/Q-01: Cartão/Boleto `pending`/`under_review` sem `expires_at`,
     * criados há mais tempo que `reconciliation_pending_threshold_minutes`
     * (`settings`, `group = 'pagamento'`, independente de
     * `pix_expiration_seconds`/RF-07) — rede de segurança contra webhooks
     * perdidos, sem regra de expiração automática associada.
     *
     * @param  Collection<int, int>  $pendingStatusIds
     */
    private function reconcileCardAndBoleto(Collection $pendingStatusIds): void
    {
        $thresholdMinutes = (int) Setting::query()
            ->where('key', 'reconciliation_pending_threshold_minutes')
            ->value('value');

        Payment::query()
            ->whereIn('status_id', $pendingStatusIds)
            ->whereNull('expires_at')
            ->where('created_at', '<', now()->subMinutes($thresholdMinutes))
            ->each(fn (Payment $payment) => $this->reconcile($payment, forcePixExpiration: false));
    }

    private function reconcile(Payment $payment, bool $forcePixExpiration): void
    {
        $response = (new Safe2PayClient)->query($payment->gateway_transaction_id);

        $code = (int) ($response->json('TransactionStatus.Id') ?? 0);

        try {
            (new ApplyPaymentStatusTransition)->execute($payment, $code);
        } catch (\ValueError) {
            // Código desconhecido: ignora a interpretação; a decisão de
            // expirar (quando aplicável ao ramo Pix) segue abaixo mesmo assim.
        }

        if (! $forcePixExpiration) {
            return;
        }

        $expired = PaymentStatus::query()->where('slug', 'expired')->firstOrFail();
        $currentStatus = $payment->fresh()->status;

        if ($currentStatus->weight < $expired->weight) {
            $payment->update(['status_id' => $expired->id]);
        }
    }
}
