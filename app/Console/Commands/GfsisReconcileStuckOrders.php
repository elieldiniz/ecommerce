<?php

namespace App\Console\Commands;

use App\Models\OrderItemGfsis;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('gfsis:reconcile-stuck')]
#[Description('Sinaliza order_item_gfsis presos em enviado_gfsis além do limiar configurável, sem chamar o GFSIS (RF-13).')]
class GfsisReconcileStuckOrders extends Command
{
    /**
     * RF-13: identifica `order_item_gfsis` com `status = enviado_gfsis` cuja
     * `sent_at` ultrapassa `gfsis_stuck_threshold_hours` (`settings`,
     * RNF-03), registra um log estruturado por linha e exibe uma tabela no
     * comando. Nenhuma chamada HTTP ao GFSIS é realizada — não há endpoint de
     * consulta de pedido documentado.
     */
    public function handle(): int
    {
        $thresholdHours = (int) Setting::query()
            ->where('key', 'gfsis_stuck_threshold_hours')
            ->value('value');

        $stuckItems = OrderItemGfsis::query()
            ->whereHas('status', fn ($query) => $query->where('slug', 'enviado_gfsis'))
            ->where('sent_at', '<', now()->subHours($thresholdHours))
            ->get();

        $stuckItems->each(function (OrderItemGfsis $orderItemGfsis): void {
            Log::warning('gfsis.stuck_order_item', [
                'order_item_gfsis_id' => $orderItemGfsis->id,
                'gfsis_order_id' => $orderItemGfsis->gfsis_order_id,
                'sent_at' => $orderItemGfsis->sent_at?->toDateTimeString(),
            ]);
        });

        $this->table(
            ['ID', 'gfsis_order_id', 'sent_at'],
            $stuckItems->map(fn (OrderItemGfsis $orderItemGfsis) => [
                $orderItemGfsis->id,
                $orderItemGfsis->gfsis_order_id,
                $orderItemGfsis->sent_at?->toDateTimeString(),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
