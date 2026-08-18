<?php

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'vendas', 'title' => 'Vendas'])] #[Title('Vendas')] class extends Component
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()
            ->with(['status', 'fulfillmentStatus', 'payments.status', 'items', 'customer'])
            ->latest('created_at')
            ->paginate(25);
    }
}
?>

<div>
    {{-- Bloco: Filtros --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Últimos 30 dias</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status do pagamento</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status da emissão</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Forma de pagamento</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Produto</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Origem</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar por nome, documento ou número do pedido</label>
                <input type="text" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Bloco: Ações em lote --}}
    <section class="mt-6 flex flex-wrap gap-2">
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Exportar CSV</button>
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Reenviar ao GFSIS</button>
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Disparar recuperação</button>
    </section>

    {{-- Tabela de pedidos --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pagamento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Emissão</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->orders as $order)
                        @php
                            $latestPayment = $order->payments->sortByDesc('id')->first();
                            $paymentVariant = match ($latestPayment?->status?->slug) {
                                'authorized' => 'emitido',
                                'denied', 'expired', 'reversed' => 'erro',
                                default => 'aguardando',
                            };
                            $fulfillmentVariant = match ($order->fulfillmentStatus->slug) {
                                'sent_to_gfsis' => 'emitido',
                                'send_failed' => 'erro',
                                'data_complete' => 'agendado',
                                default => 'aguardando',
                            };
                        @endphp
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                <a href="{{ route('painel.vendas.show', $order->id) }}" class="font-semibold text-brand">{{ $order->number }}</a>
                            </td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->items->first()?->name_snapshot ?? '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$paymentVariant">{{ $latestPayment?->status?->name ?? '—' }}</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$fulfillmentVariant">{{ $order->fulfillmentStatus->name }}</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum pedido encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 font-sans text-xs text-muted-light">
            Mostrando {{ $this->orders->firstItem() ?? 0 }} a {{ $this->orders->lastItem() ?? 0 }} de {{ $this->orders->total() }}
        </div>

        <div class="mt-4">
            {{ $this->orders->links() }}
        </div>
    </section>
</div>
