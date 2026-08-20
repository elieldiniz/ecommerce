<?php

use App\Models\Order;
use App\Models\OrderItemGfsis;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('components.admin-layout', ['activeItem' => 'relatorios', 'title' => 'Relatórios'])] #[Title('Relatórios')] class extends Component
{
    public ?string $periodo = '30d';

    public ?int $produto = null;

    public ?int $formaPagamento = null;

    public string $busca = '';

    /**
     * @return array<int, array{key: string, name: string, description: string}>
     */
    #[Computed]
    public function reports(): array
    {
        return [
            ['key' => 'vendas-por-periodo', 'name' => 'Vendas por período', 'description' => 'Faturamento e pedidos por dia, semana ou mês'],
            ['key' => 'vendas-por-produto', 'name' => 'Vendas por produto', 'description' => 'Comparativo entre e-CPF, e-CNPJ e MEI'],
            ['key' => 'funil-operacional', 'name' => 'Funil operacional', 'description' => 'Conversão em cada etapa do pedido'],
            ['key' => 'pagos-sem-dados', 'name' => 'Pagos sem dados', 'description' => 'Clientes que pagaram e não preencheram dados'],
            ['key' => 'base-de-renovacao', 'name' => 'Base de renovação', 'description' => 'Certificados próximos do vencimento'],
            ['key' => 'atribuicao', 'name' => 'Atribuição', 'description' => 'Origem e campanha de cada venda'],
            ['key' => 'conciliacao-do-gateway', 'name' => 'Conciliação do gateway', 'description' => 'Comparativo entre pedidos e repasses'],
            ['key' => 'estornos', 'name' => 'Estornos', 'description' => 'Pedidos estornados no período'],
            ['key' => 'cupons', 'name' => 'Cupons', 'description' => 'Uso e desempenho de cada cupom'],
        ];
    }

    private function filteredPaidOrdersQuery(): Builder
    {
        return Order::query()
            ->whereNotNull('paid_at')
            ->when($this->periodo, fn (Builder $q, string $v) => match ($v) {
                '7d' => $q->where('paid_at', '>=', now()->subDays(7)),
                '30d' => $q->where('paid_at', '>=', now()->subDays(30)),
                '90d' => $q->where('paid_at', '>=', now()->subDays(90)),
                default => $q,
            })
            ->when($this->produto, fn (Builder $q, int $v) => $q->whereHas('items.productVariant', fn ($q2) => $q2->where('product_id', $v)))
            ->when($this->formaPagamento, fn (Builder $q, int $v) => $q->where('payment_method_id', $v))
            ->when($this->busca, fn (Builder $q, string $v) => $q->where(function ($q2) use ($v) {
                $q2->where('number', 'like', "%{$v}%")
                    ->orWhereHas('customer', fn ($q3) => $q3->where('legal_name', 'like', "%{$v}%")
                        ->orWhere('document', 'like', "%{$v}%"));
            }));
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function filteredPaidOrders(): Collection
    {
        return $this->filteredPaidOrdersQuery()->get();
    }

    #[Computed]
    public function faturamento(): float
    {
        return (float) $this->filteredPaidOrders->sum('total');
    }

    #[Computed]
    public function pedidosCount(): int
    {
        return $this->filteredPaidOrders->count();
    }

    #[Computed]
    public function ticketMedio(): float
    {
        return $this->pedidosCount > 0 ? $this->faturamento / $this->pedidosCount : 0.0;
    }

    #[Computed]
    public function descontos(): float
    {
        return (float) $this->filteredPaidOrders->sum(fn (Order $o) => (float) $o->coupon_discount + (float) $o->payment_method_discount);
    }

    /**
     * @return BaseCollection<int, array{dia: string, pedidos: int, faturamento: float, ticket_medio: float, desconto: float}>
     */
    #[Computed]
    public function vendasPorDia(): BaseCollection
    {
        return $this->filteredPaidOrders
            ->groupBy(fn (Order $order) => $order->paid_at->format('Y-m-d'))
            ->map(fn (Collection $orders, string $dia) => [
                'dia' => Carbon::parse($dia)->format('d/m/Y'),
                'pedidos' => $orders->count(),
                'faturamento' => (float) $orders->sum('total'),
                'ticket_medio' => (float) $orders->avg('total'),
                'desconto' => (float) $orders->sum(fn (Order $o) => (float) $o->coupon_discount + (float) $o->payment_method_discount),
            ])
            ->sortKeysDesc()
            ->values();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function productOptions(): Collection
    {
        return Product::query()->select(['id', 'name'])->orderBy('name')->get();
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
     * @return Collection<int, OrderItemGfsis>
     */
    #[Computed]
    public function baseDeRenovacao(): Collection
    {
        return OrderItemGfsis::query()
            ->whereBetween('certificate_expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with(['orderItem.order.customer', 'orderItem.productVariant.product', 'orderItem.productVariant.certificateFormat'])
            ->orderBy('certificate_expires_at')
            ->get();
    }

    public function exportarCsv(): StreamedResponse
    {
        $rows = $this->vendasPorDia;

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Dia', 'Pedidos', 'Faturamento', 'Ticket médio', 'Desconto']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['dia'],
                    $row['pedidos'],
                    number_format($row['faturamento'], 2, '.', ''),
                    number_format($row['ticket_medio'], 2, '.', ''),
                    number_format($row['desconto'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'vendas-por-periodo-'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
?>

<div>
    {{-- Bloco: Seleção --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seleção</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            @foreach ($this->reports as $report)
                <button
                    type="button"
                    data-report="{{ $report['key'] }}"
                    @class([
                        'rounded-xl p-4 text-left',
                        'border-2 border-brand bg-highlight' => $report['key'] === 'vendas-por-periodo',
                        'border border-border-light bg-white' => $report['key'] !== 'vendas-por-periodo',
                    ])
                >
                    <div class="font-heading text-sm font-bold text-ink">{{ $report['name'] }}</div>
                    <div class="mt-1 font-sans text-xs text-muted">{{ $report['description'] }}</div>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Vendas por período --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por período</h2>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período</label>
                <select wire:model.live="periodo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d">Últimos 30 dias</option>
                    <option value="90d">Últimos 90 dias</option>
                    <option value="">Todo o período</option>
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
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Forma de pagamento</label>
                <select wire:model.live="formaPagamento" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todas</option>
                    @foreach ($this->paymentMethodOptions as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Origem</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-kpi-card label="Faturamento" value="{{ Number::currency($this->faturamento, in: 'BRL', locale: 'pt_BR') }}" />
            <x-kpi-card label="Pedidos" value="{{ $this->pedidosCount }}" />
            <x-kpi-card label="Ticket médio" value="{{ Number::currency($this->ticketMedio, in: 'BRL', locale: 'pt_BR') }}" />
            <x-kpi-card label="Descontos" value="{{ Number::currency($this->descontos, in: 'BRL', locale: 'pt_BR') }}" />
        </div>

        <div class="mb-4 flex h-48 items-center justify-center rounded-lg border border-border-light bg-surface-alt font-sans text-xs text-muted-light">
            Gráfico de linha · faturamento diário
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dia</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedidos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Faturamento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ticket médio</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Desconto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vendasPorDia as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['dia'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['pedidos'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($row['faturamento'], in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($row['ticket_medio'], in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($row['desconto'], in: 'BRL', locale: 'pt_BR') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhuma venda no período selecionado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>

    {{-- Base de renovação --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Base de renovação</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Vence em</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dias</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Contato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->baseDeRenovacao as $item)
                        @php
                            $customer = $item->orderItem->order->customer;
                            $variant = $item->orderItem->productVariant;
                            $whatsapp = 'https://wa.me/55'.preg_replace('/\D/', '', $customer->phone ?? '');
                        @endphp
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->document }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->product->name }} {{ $variant->certificateFormat->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $item->certificate_expires_at->format('d/m/Y') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $item->certificate_expires_at->diffInDays(now()) }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="inline-block rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt">WhatsApp</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum certificado vencendo nos próximos 30 dias</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
