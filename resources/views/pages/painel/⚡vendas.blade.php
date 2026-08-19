<?php

use App\Actions\Gfsis\ResendIssuanceAccessLink;
use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('components.admin-layout', ['activeItem' => 'vendas', 'title' => 'Vendas'])] #[Title('Vendas')] class extends Component
{
    use WithPagination;

    public ?string $statusPagamento = null;

    public ?string $statusEmissao = null;

    public ?string $formaPagamento = null;

    public ?int $produto = null;

    public ?string $periodo = null;

    public string $busca = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['statusPagamento', 'statusEmissao', 'formaPagamento', 'produto', 'periodo', 'busca'], true)) {
            $this->resetPage();
        }
    }

    private function filteredOrdersQuery(): Builder
    {
        return Order::query()
            ->with(['status', 'fulfillmentStatus', 'payments.status', 'items', 'customer'])
            ->when($this->statusPagamento, fn (Builder $q, string $v) => $q->whereHas('payments', fn ($q2) => $q2->where('status_id', $v)
                ->whereRaw('payments.id = (select max(id) from payments as p2 where p2.order_id = orders.id)')))
            ->when($this->statusEmissao, fn (Builder $q, string $v) => $q->where('fulfillment_status_id', $v))
            ->when($this->formaPagamento, fn (Builder $q, string $v) => $q->where('payment_method_id', $v))
            ->when($this->produto, fn (Builder $q, int $v) => $q->whereHas('items.productVariant', fn ($q2) => $q2->where('product_id', $v)))
            ->when($this->periodo, fn (Builder $q, string $v) => match ($v) {
                '7d' => $q->where('created_at', '>=', now()->subDays(7)),
                '30d' => $q->where('created_at', '>=', now()->subDays(30)),
                '90d' => $q->where('created_at', '>=', now()->subDays(90)),
                default => $q,
            })
            ->when($this->busca, fn (Builder $q, string $v) => $q->where(function ($q2) use ($v) {
                $q2->where('number', 'like', "%{$v}%")
                    ->orWhereHas('customer', fn ($q3) => $q3->where('legal_name', 'like', "%{$v}%")
                        ->orWhere('document', 'like', "%{$v}%"));
            }));
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->filteredOrdersQuery()->latest('created_at')->paginate(25);
    }

    /**
     * @return Collection<int, PaymentStatus>
     */
    #[Computed]
    public function paymentStatusOptions(): Collection
    {
        return PaymentStatus::query()->select(['id', 'name'])->get();
    }

    /**
     * @return Collection<int, OrderFulfillmentStatus>
     */
    #[Computed]
    public function fulfillmentStatusOptions(): Collection
    {
        return OrderFulfillmentStatus::query()->select(['id', 'name'])->get();
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    #[Computed]
    public function paymentMethodOptions(): Collection
    {
        return PaymentMethod::query()->select(['id', 'name'])->get();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function productOptions(): Collection
    {
        return Product::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    public function exportarCsv(): StreamedResponse
    {
        $orders = $this->filteredOrdersQuery()->latest('created_at')->get();

        Flux::toast(variant: 'success', text: __('CSV gerado com :count pedidos.', ['count' => $orders->count()]));

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Pedido', 'Cliente', 'Produto', 'Valor', 'Status pagamento', 'Status emissão', 'Data']);

            foreach ($orders as $order) {
                $latestPayment = $order->payments->sortByDesc('id')->first();

                fputcsv($handle, [
                    $order->number,
                    $order->customer->legal_name,
                    $order->items->first()?->name_snapshot ?? '—',
                    number_format((float) $order->total, 2, '.', ''),
                    $latestPayment?->status?->name ?? '—',
                    $order->fulfillmentStatus->name,
                    $order->created_at->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, 'vendas-'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function reenviarGfsis(): void
    {
        $orders = $this->filteredOrdersQuery()->get();

        foreach ($orders as $order) {
            RegisterOrderItemWithGfsisJob::dispatch($order);
        }

        Flux::toast(variant: 'success', text: __('Reenvio ao GFSIS disparado para :count pedidos.', ['count' => $orders->count()]));
    }

    public function dispararRecuperacao(): void
    {
        $fila = Order::query()
            ->with(['items', 'customer'])
            ->whereHas('status', fn (Builder $q) => $q->where('slug', 'paid'))
            ->whereHas('fulfillmentStatus', fn (Builder $q) => $q->where('slug', 'awaiting_data'))
            ->get();

        $action = app(ResendIssuanceAccessLink::class);

        $sucessos = 0;

        foreach ($fila as $order) {
            if ($action->execute($order)) {
                $sucessos++;
            }
        }

        if ($sucessos === $fila->count()) {
            Flux::toast(variant: 'success', text: __('Recuperação disparada para :count pedidos.', ['count' => $sucessos]));

            return;
        }

        Flux::toast(variant: 'danger', text: __(':sucessos de :total pedidos reenviados; :falhas falharam (verifique os e-mails cadastrados).', [
            'sucessos' => $sucessos,
            'total' => $fila->count(),
            'falhas' => $fila->count() - $sucessos,
        ]));
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
                <select wire:model.live="periodo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todo o período</option>
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d">Últimos 30 dias</option>
                    <option value="90d">Últimos 90 dias</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status do pagamento</label>
                <select wire:model.live="statusPagamento" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todos</option>
                    @foreach ($this->paymentStatusOptions as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status da emissão</label>
                <select wire:model.live="statusEmissao" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todos</option>
                    @foreach ($this->fulfillmentStatusOptions as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Forma de pagamento</label>
                <select wire:model.live="formaPagamento" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todas</option>
                    @foreach ($this->paymentMethodOptions as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Produto</label>
                <select wire:model.live="produto" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todos</option>
                    @foreach ($this->productOptions as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
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
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Bloco: Ações em lote --}}
    <section class="mt-6 flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="exportarCsv"
            wire:loading.attr="disabled"
            wire:target="exportarCsv"
            class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink disabled:cursor-not-allowed"
        >Exportar CSV</button>
        <button
            type="button"
            wire:click="reenviarGfsis"
            wire:loading.attr="disabled"
            wire:target="reenviarGfsis"
            class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink disabled:cursor-not-allowed"
        >Reenviar ao GFSIS</button>
        <button
            type="button"
            wire:click="dispararRecuperacao"
            wire:loading.attr="disabled"
            wire:target="dispararRecuperacao"
            class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink disabled:cursor-not-allowed"
        >Disparar recuperação</button>
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
