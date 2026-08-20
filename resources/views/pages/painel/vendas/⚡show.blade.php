<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'vendas', 'title' => 'Vendas'])] #[Title('Detalhe do pedido')] class extends Component
{
    public int $id;

    public function mount(int $id): void
    {
        if (Order::query()->find($id) === null) {
            abort(404);
        }

        $this->id = $id;
    }

    #[Computed]
    public function order(): ?Order
    {
        return Order::query()
            ->with(['customer', 'items', 'items.productVariant', 'items.issuanceData', 'items.gfsis', 'items.gfsis.status', 'items.gfsis.events', 'status', 'fulfillmentStatus'])
            ->find($this->id);
    }

    #[Computed]
    public function payment(): ?Payment
    {
        return Payment::query()
            ->where('order_id', $this->id)
            ->with(['method', 'status'])
            ->latest('id')
            ->first();
    }

    /**
     * Monta a linha do tempo real do pedido a partir das datas já gravadas em
     * orders/issuance_data/order_item_gfsis e do log de webhooks do GFSIS
     * (gfsis_events). Eventos CRIADO/ENVIADO do GFSIS são ignorados aqui
     * porque já são representados por "Enviado ao GFSIS" (order_item_gfsis.sent_at).
     *
     * @return array<int, array{date: string, description: string, origin: string}>
     */
    #[Computed]
    public function timeline(): array
    {
        $order = $this->order;
        $events = [];

        $events[] = ['at' => $order->created_at, 'description' => 'Pedido criado', 'origin' => 'sistema'];

        if ($order->paid_at !== null) {
            $events[] = ['at' => $order->paid_at, 'description' => 'Pagamento autorizado', 'origin' => 'webhook'];
        }

        foreach ($order->items as $item) {
            if ($item->issuanceData?->filled_at !== null) {
                $events[] = ['at' => $item->issuanceData->filled_at, 'description' => 'Dados de emissão preenchidos', 'origin' => 'cliente'];
            }

            if ($item->gfsis?->sent_at !== null) {
                $events[] = ['at' => $item->gfsis->sent_at, 'description' => 'Enviado ao GFSIS', 'origin' => 'fila'];
            }

            foreach ($item->gfsis?->events ?? [] as $gfsisEvent) {
                $description = match (strtoupper((string) ($gfsisEvent->payload['status'] ?? ''))) {
                    'APROVADO' => 'Aprovado pelo GFSIS (videoconferência validada)',
                    'EMITIDO' => 'Certificado emitido',
                    'RECUSADO' => 'Recusado pelo GFSIS',
                    'CANCELADO' => 'Cancelado no GFSIS',
                    default => null,
                };

                if ($description === null) {
                    continue;
                }

                $events[] = ['at' => $gfsisEvent->received_at, 'description' => $description, 'origin' => 'webhook'];
            }
        }

        usort($events, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        return array_map(fn (array $event) => [
            'date' => $event['at']->format('d/m/Y H:i'),
            'description' => $event['description'],
            'origin' => $event['origin'],
        ], $events);
    }
}
?>

<div>
    @php
        $order = $this->order;
        $payment = $this->payment;

        $financialVariant = match ($order->status->slug) {
            'paid' => 'emitido',
            'cancelled' => 'erro',
            default => 'aguardando',
        };
        $fulfillmentVariant = match ($order->fulfillmentStatus->slug) {
            'sent_to_gfsis' => 'emitido',
            'send_failed' => 'erro',
            'data_complete' => 'agendado',
            default => 'aguardando',
        };

        $firstItem = $order->items->first();
        $issuanceData = $firstItem?->issuanceData;
        $issuanceFilled = $issuanceData !== null && $issuanceData->filled_at !== null;

        $orderItemGfsis = $firstItem?->gfsis;
    @endphp

    {{-- Bloco: Cabeçalho --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-heading text-lg font-bold text-ink">Pedido {{ $order->number }}</h2>
                <div class="font-sans text-xs text-muted">Criado em {{ $order->created_at->format('d/m/Y') }} · {{ $order->created_at->format('H\hi') }}</div>
                <div class="mt-1 font-sans text-sm text-ink">{{ $order->customer->legal_name }}</div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge-status :variant="$financialVariant">{{ $order->status->name }}</x-badge-status>
                <x-badge-status :variant="$fulfillmentVariant">{{ $order->fulfillmentStatus->name }}</x-badge-status>
            </div>
        </div>
    </section>

    {{-- Bloco: Itens --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Itens</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">SKU</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço tabela</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço praticado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $item->productVariant?->sku ?? '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $item->name_snapshot }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $item->issuanceData?->holder_name ?? '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($item->list_price_snapshot, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($item->unit_price, in: 'BRL', locale: 'pt_BR') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Financeiro --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Valores</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Subtotal</dt><dd class="text-ink">{{ Number::currency($order->subtotal, in: 'BRL', locale: 'pt_BR') }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Desconto cupom</dt><dd class="text-ink">- {{ Number::currency($order->coupon_discount, in: 'BRL', locale: 'pt_BR') }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Desconto Pix</dt><dd class="text-ink">- {{ Number::currency($order->payment_method_discount, in: 'BRL', locale: 'pt_BR') }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Total</dt><dd class="text-ink">{{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Taxa do gateway</dt><dd class="text-ink">{{ $payment?->gateway_fee !== null ? Number::currency($payment->gateway_fee, in: 'BRL', locale: 'pt_BR') : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Líquido previsto</dt><dd class="text-ink">{{ $payment?->net_amount !== null ? Number::currency($payment->net_amount, in: 'BRL', locale: 'pt_BR') : '—' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Pagamento</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Método</dt><dd class="text-ink">{{ $payment?->method?->name ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Status</dt><dd class="text-ink">{{ $payment?->status?->name ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>ID no gateway</dt><dd class="text-ink">{{ $payment?->gateway_transaction_id ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>TXID</dt><dd class="text-ink">{{ $payment?->pix_id ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>End-to-end</dt><dd class="text-ink">{{ $payment?->end_to_end_id ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Pago em</dt><dd class="text-ink">{{ $payment?->paid_at !== null ? $payment->paid_at->format('d/m/Y').' · '.$payment->paid_at->format('H\hi') : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Previsão de repasse</dt><dd class="text-ink">{{ $payment?->expected_settlement_date?->format('d/m/Y') ?? '—' }}</dd></div>
            </dl>
        </div>
    </section>

    {{-- Bloco: Emissão e GFSIS --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Dados do titular</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Nome</dt><dd class="text-ink">{{ $issuanceFilled ? $issuanceData->holder_name : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>CPF</dt><dd class="text-ink">{{ $issuanceFilled ? $issuanceData->document : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Responsável (PJ)</dt><dd class="text-ink">{{ $issuanceFilled ? ($issuanceData->responsible_name ?? '—') : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>E-mail</dt><dd class="text-ink">{{ $issuanceFilled ? $issuanceData->email : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Endereço</dt><dd class="text-ink">{{ $issuanceFilled ? $issuanceData->street.', '.$issuanceData->number : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Município/UF</dt><dd class="text-ink">{{ $issuanceFilled ? $issuanceData->city.'/'.$issuanceData->state : '—' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Integração</h3>
            <dl class="mb-4 flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>gfsis_order_id</dt><dd class="text-ink">{{ $orderItemGfsis?->gfsis_order_id ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Código GFSIS</dt><dd class="text-ink">{{ $orderItemGfsis?->gfsis_code ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Status GFSIS</dt><dd class="text-ink">{{ $orderItemGfsis?->status?->name ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Agendamento</dt><dd class="text-ink">{{ $orderItemGfsis?->appointment_date !== null ? $orderItemGfsis->appointment_date->format('d/m/Y').' · '.substr((string) $orderItemGfsis->appointment_time, 0, 5).'h' : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Validade até</dt><dd class="text-ink">{{ $orderItemGfsis?->certificate_expires_at?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Sincronizado em</dt><dd class="text-ink">{{ $orderItemGfsis?->status_synced_at !== null ? $orderItemGfsis->status_synced_at->format('d/m/Y').' · '.$orderItemGfsis->status_synced_at->format('H\hi') : '—' }}</dd></div>
                <div class="flex justify-between text-muted"><dt>Tentativas</dt><dd class="text-ink">{{ $orderItemGfsis?->attempts ?? '—' }}</dd></div>
            </dl>
            <button type="button" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-xs font-semibold text-ink">Reenviar ao GFSIS</button>
        </div>
    </section>

    {{-- Bloco: Origem da venda --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Origem da venda</h2>
        <x-comparison-table
            :columns="['Campo', 'Valor']"
            :rows="[
                ['Campanha', 'Google Ads · Certificado Digital e-CPF'],
                ['Origem e meio', 'google / cpc'],
                ['gclid', 'Cj0KCQjw_gclid_exemplo'],
                ['Página de entrada', '/certificado-digital/e-cpf/'],
                ['Dispositivo', 'Mobile'],
                ['Sessões até a compra', '3'],
                ['Status de conversão enviada', 'Enviada'],
            ]"
        />
    </section>

    {{-- Bloco: Linha do tempo --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Linha do tempo</h2>
        <x-timeline :events="$this->timeline" />
    </section>
</div>
