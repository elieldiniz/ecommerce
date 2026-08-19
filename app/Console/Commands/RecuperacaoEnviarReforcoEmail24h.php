<?php

namespace App\Console\Commands;

use App\Actions\Gfsis\ResendIssuanceAccessLink;
use App\Models\IntegrationQueueJob;
use App\Models\Order;
use App\Models\QueueJobStatus;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('recuperacao:reforco-24h')]
#[Description('Reenvia o e-mail de reforço para pedidos pagos aguardando dados há mais do que o limiar configurável, com idempotência por pedido (RF-02, RF-03, RF-04).')]
class RecuperacaoEnviarReforcoEmail24h extends Command
{
    private const IDEMPOTENCY_JOB = 'recovery_email_24h';

    public function __construct(private ResendIssuanceAccessLink $resendIssuanceAccessLink)
    {
        parent::__construct();
    }

    /**
     * RF-02: identifica pedidos `paid`+`awaiting_data` com `paid_at` anterior
     * ao limiar configurável (RF-02/CT-04) e ainda sem a marca de
     * idempotência do RF-04, reenviando o link via `ResendIssuanceAccessLink`
     * (mesmo mecanismo/TTL do reenvio manual). RNF-02: uma falha em um
     * pedido não interrompe o processamento dos demais na mesma execução.
     */
    public function handle(): int
    {
        $thresholdHours = (int) Setting::query()
            ->where('key', 'recovery_reinforcement_email_threshold_hours')
            ->value('value');

        $fila = Order::query()
            ->with(['items', 'customer'])
            ->whereHas('status', fn ($query) => $query->where('slug', 'paid'))
            ->whereHas('fulfillmentStatus', fn ($query) => $query->where('slug', 'awaiting_data'))
            ->where('paid_at', '<', now()->subHours($thresholdHours))
            ->get();

        foreach ($fila as $order) {
            if ($this->jaEnviado($order)) {
                continue;
            }

            try {
                if ($this->resendIssuanceAccessLink->execute($order)) {
                    $this->marcarComoEnviado($order);
                }
            } catch (Throwable $e) {
                Log::error('recuperacao.reforco_24h_falhou', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function jaEnviado(Order $order): bool
    {
        return IntegrationQueueJob::query()
            ->where('job', self::IDEMPOTENCY_JOB)
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();
    }

    private function marcarComoEnviado(Order $order): void
    {
        IntegrationQueueJob::query()->create([
            'status_id' => QueueJobStatus::query()->where('slug', 'completed')->value('id'),
            'job' => self::IDEMPOTENCY_JOB,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'run_at' => now(),
        ]);
    }
}
