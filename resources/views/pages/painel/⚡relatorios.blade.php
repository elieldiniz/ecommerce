<?php

use App\Models\CouponUse;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
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
    public string $activeReport = 'vendas-por-periodo';

    public ?string $periodo = '30d';

    public ?int $produto = null;

    public ?int $formaPagamento = null;

    public ?string $origem = null;

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

    private function filteredPaidOrdersQuery(bool $applyBusca = true): Builder
    {
        $query = Order::query()
            ->whereNotNull('paid_at')
            ->when($this->periodo, fn (Builder $q, string $v) => match ($v) {
                '7d' => $q->where('paid_at', '>=', now()->subDays(7)),
                '30d' => $q->where('paid_at', '>=', now()->subDays(30)),
                '90d' => $q->where('paid_at', '>=', now()->subDays(90)),
                default => $q,
            })
            ->when($this->produto, fn (Builder $q, int $v) => $q->whereHas('items.productVariant', fn ($q2) => $q2->where('product_id', $v)))
            ->when($this->formaPagamento, fn (Builder $q, int $v) => $q->where('payment_method_id', $v))
            ->when($this->origem, fn (Builder $q, string $v) => $q->whereHas('attribution', fn ($q2) => $q2->where('utm_source', $v)));

        if ($applyBusca) {
            $query->when($this->busca, fn (Builder $q, string $v) => $q->where(function ($q2) use ($v) {
                $q2->where('number', 'like', "%{$v}%")
                    ->orWhereHas('customer', fn ($q3) => $q3->where('legal_name', 'like', "%{$v}%")
                        ->orWhere('document', 'like', "%{$v}%"));
            }));
        }

        return $query;
    }

    /**
     * @return BaseCollection<int, string>
     */
    #[Computed]
    public function origemOptions(): BaseCollection
    {
        return OrderAttribution::query()
            ->whereNotNull('utm_source')
            ->distinct()
            ->orderBy('utm_source')
            ->pluck('utm_source');
    }

    /**
     * Comparativo entre produtos (RF-03) — agrupado por item, não por pedido, para que um
     * pedido com itens de mais de 1 produto não vaze linhas do produto não filtrado quando
     * "Produto" está selecionado.
     *
     * @return BaseCollection<int, array{produto: string, quantidade: int, faturamento: float, ticket_medio: float}>
     */
    #[Computed]
    public function vendasPorProduto(): BaseCollection
    {
        $items = $this->filteredPaidOrdersQuery(applyBusca: false)
            ->with('items.productVariant.product')
            ->get()
            ->flatMap(fn (Order $o) => $o->items);

        if ($this->produto) {
            $items = $items->filter(fn (OrderItem $item) => $item->productVariant->product_id === $this->produto);
        }

        return $items
            ->groupBy(fn (OrderItem $item) => $item->productVariant->product_id)
            ->map(function (BaseCollection $productItems) {
                $quantidade = (int) $productItems->sum('quantity');
                $faturamento = (float) $productItems->sum('total');

                return [
                    'produto' => $productItems->first()->productVariant->product->name,
                    'quantidade' => $quantidade,
                    'faturamento' => $faturamento,
                    'ticket_medio' => $quantidade > 0 ? $faturamento / $quantidade : 0.0,
                ];
            })
            ->values();
    }

    /**
     * Reproduz a semântica de funilStages() de ⚡visao-geral.blade.php (verified —
     * "Pagos"/faturamento por paid_at, não status.slug; "Enviados ao GFSIS"/"Emitidos" via
     * items.gfsis.status.slug; "Dados completos" inclui send_failed; percentuais passo a
     * passo), mas escopada pelos filtros ativos desta tela (período, produto, forma de
     * pagamento, origem).
     *
     * @return array<int, array{name: string, quantity: int, percentage?: string}>
     */
    #[Computed]
    public function funilOperacionalFiltrado(): array
    {
        $criados = Order::query()
            ->when($this->periodo, fn (Builder $q, string $v) => match ($v) {
                '7d' => $q->where('created_at', '>=', now()->subDays(7)),
                '30d' => $q->where('created_at', '>=', now()->subDays(30)),
                '90d' => $q->where('created_at', '>=', now()->subDays(90)),
                default => $q,
            })
            ->when($this->origem, fn (Builder $q, string $v) => $q->whereHas('attribution', fn ($q2) => $q2->where('utm_source', $v)))
            ->count();

        $pagos = $this->filteredPaidOrdersQuery(applyBusca: false)->count();

        $dadosCompletos = $this->filteredPaidOrdersQuery(applyBusca: false)
            ->whereHas('fulfillmentStatus', fn (Builder $q) => $q->whereIn('slug', ['data_complete', 'sent_to_gfsis', 'send_failed']))
            ->count();

        $enviadosGfsis = $this->filteredPaidOrdersQuery(applyBusca: false)
            ->whereHas('items.gfsis.status', fn (Builder $q) => $q->whereIn('slug', ['enviado_gfsis', 'aprovado', 'emitido']))
            ->count();

        $emitidos = $this->filteredPaidOrdersQuery(applyBusca: false)
            ->whereHas('items.gfsis.status', fn (Builder $q) => $q->where('slug', 'emitido'))
            ->count();

        $pct = fn (int $atual, int $anterior) => $anterior > 0 ? round(($atual / $anterior) * 100).'%' : '—';

        return [
            ['name' => 'Pedidos criados', 'quantity' => $criados],
            ['name' => 'Pagos', 'quantity' => $pagos, 'percentage' => $pct($pagos, $criados)],
            ['name' => 'Dados completos', 'quantity' => $dadosCompletos, 'percentage' => $pct($dadosCompletos, $pagos)],
            ['name' => 'Enviados ao GFSIS', 'quantity' => $enviadosGfsis, 'percentage' => $pct($enviadosGfsis, $dadosCompletos)],
            ['name' => 'Emitidos', 'quantity' => $emitidos, 'percentage' => $pct($emitidos, $enviadosGfsis)],
        ];
    }

    /**
     * Reproduz a base de pagosSemDadosQueue() de ⚡visao-geral.blade.php (status=paid E
     * fulfillmentStatus=awaiting_data), estendida com os filtros ativos desta tela.
     *
     * @return Collection<int, Order>
     */
    #[Computed]
    public function pagosSemDadosFiltrado(): Collection
    {
        return $this->filteredPaidOrdersQuery(applyBusca: false)
            ->whereHas('status', fn (Builder $q) => $q->where('slug', 'paid'))
            ->whereHas('fulfillmentStatus', fn (Builder $q) => $q->where('slug', 'awaiting_data'))
            ->with(['customer', 'items.productVariant.product'])
            ->orderBy('paid_at')
            ->get();
    }

    /**
     * Agrupa pedidos pagos por origem (RF-06). Ressalva já fechada na SPEC: em produção,
     * até uma feature futura de captura de UTM existir no checkout, `order_attribution`
     * fica sempre vazia e 100% dos pedidos caem em "Direto/Não identificado" — comportamento
     * esperado, não bug.
     *
     * @return BaseCollection<int, array{origem: string, pedidos: int, faturamento: float}>
     */
    #[Computed]
    public function atribuicao(): BaseCollection
    {
        return $this->filteredPaidOrdersQuery(applyBusca: false)
            ->with('attribution')
            ->get()
            ->groupBy(fn (Order $o) => $o->attribution?->utm_source ?? 'Direto/Não identificado')
            ->map(fn (Collection $orders, string $origem) => [
                'origem' => $origem,
                'pedidos' => $orders->count(),
                'faturamento' => (float) $orders->sum('total'),
            ])
            ->values();
    }

    /**
     * Pedidos estornados no período (RF-08). `Refund` não pertence a `Order` diretamente
     * (`refund.payment.order`) — reaproveita os mesmos 5 filtros de filteredPaidOrdersQuery()
     * via whereHas('payment.order', ...), duplicados aqui intencionalmente (FLEXIBLE da SPEC
     * não exige extração de closure compartilhada).
     *
     * @return Collection<int, Refund>
     */
    #[Computed]
    public function estornos(): Collection
    {
        return Refund::query()
            ->with(['reason', 'payment.order.customer'])
            ->whereHas('payment.order', fn (Builder $q) => $q
                ->when($this->periodo, fn (Builder $q2, string $v) => match ($v) {
                    '7d' => $q2->where('paid_at', '>=', now()->subDays(7)),
                    '30d' => $q2->where('paid_at', '>=', now()->subDays(30)),
                    '90d' => $q2->where('paid_at', '>=', now()->subDays(90)),
                    default => $q2,
                })
                ->when($this->produto, fn (Builder $q2, int $v) => $q2->whereHas('items.productVariant', fn ($q3) => $q3->where('product_id', $v)))
                ->when($this->formaPagamento, fn (Builder $q2, int $v) => $q2->where('payment_method_id', $v))
                ->when($this->origem, fn (Builder $q2, string $v) => $q2->whereHas('attribution', fn ($q3) => $q3->where('utm_source', $v)))
                ->when($this->busca, fn (Builder $q2, string $v) => $q2->where(function ($q3) use ($v) {
                    $q3->where('number', 'like', "%{$v}%")
                        ->orWhereHas('customer', fn ($q4) => $q4->where('legal_name', 'like', "%{$v}%")
                            ->orWhere('document', 'like', "%{$v}%"));
                })))
            ->orderByDesc('requested_at')
            ->get();
    }

    /**
     * Uso e desempenho por cupom (RF-09). Sem filtro por "Buscar" (RF-02 — relatório agregado).
     *
     * @return BaseCollection<int, array{codigo: string, tipo: string, usos: int, desconto_total: float}>
     */
    #[Computed]
    public function cuponsUsados(): BaseCollection
    {
        return CouponUse::query()
            ->with(['coupon.type'])
            ->whereHas('order', fn (Builder $q) => $q
                ->when($this->periodo, fn (Builder $q2, string $v) => match ($v) {
                    '7d' => $q2->where('paid_at', '>=', now()->subDays(7)),
                    '30d' => $q2->where('paid_at', '>=', now()->subDays(30)),
                    '90d' => $q2->where('paid_at', '>=', now()->subDays(90)),
                    default => $q2,
                })
                ->when($this->produto, fn (Builder $q2, int $v) => $q2->whereHas('items.productVariant', fn ($q3) => $q3->where('product_id', $v)))
                ->when($this->formaPagamento, fn (Builder $q2, int $v) => $q2->where('payment_method_id', $v))
                ->when($this->origem, fn (Builder $q2, string $v) => $q2->whereHas('attribution', fn ($q3) => $q3->where('utm_source', $v))))
            ->get()
            ->groupBy('coupon_id')
            ->map(fn (BaseCollection $uses) => [
                'codigo' => $uses->first()->coupon->code,
                'tipo' => $uses->first()->coupon->type->name,
                'usos' => $uses->count(),
                'desconto_total' => (float) $uses->sum('discount_applied'),
            ])
            ->values();
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

    public function exportarCsv(string $reportKey = 'vendas-por-periodo'): StreamedResponse
    {
        [$header, $rows] = match ($reportKey) {
            'vendas-por-produto' => [
                ['Produto', 'Quantidade', 'Faturamento', 'Ticket médio'],
                $this->vendasPorProduto->map(fn (array $row) => [
                    $row['produto'],
                    $row['quantidade'],
                    number_format($row['faturamento'], 2, '.', ''),
                    number_format($row['ticket_medio'], 2, '.', ''),
                ]),
            ],
            'funil-operacional' => [
                ['Estágio', 'Quantidade', 'Percentual'],
                collect($this->funilOperacionalFiltrado)->map(fn (array $stage) => [
                    $stage['name'],
                    $stage['quantity'],
                    $stage['percentage'] ?? '',
                ]),
            ],
            'pagos-sem-dados' => [
                ['Pedido', 'Cliente', 'Produto', 'Pago em'],
                $this->pagosSemDadosFiltrado->map(fn (Order $order) => [
                    $order->number,
                    $order->customer->legal_name,
                    $order->items->pluck('productVariant.product.name')->unique()->implode(', '),
                    $order->paid_at->format('d/m/Y'),
                ]),
            ],
            'atribuicao' => [
                ['Origem', 'Pedidos', 'Faturamento'],
                $this->atribuicao->map(fn (array $row) => [
                    $row['origem'],
                    $row['pedidos'],
                    number_format($row['faturamento'], 2, '.', ''),
                ]),
            ],
            'estornos' => [
                ['Pedido', 'Cliente', 'Motivo', 'Valor', 'Data'],
                $this->estornos->map(fn (Refund $refund) => [
                    $refund->payment->order->number,
                    $refund->payment->order->customer->legal_name,
                    $refund->reason->name,
                    number_format((float) $refund->amount, 2, '.', ''),
                    $refund->requested_at->format('d/m/Y'),
                ]),
            ],
            'cupons' => [
                ['Código', 'Tipo', 'Usos', 'Desconto total'],
                $this->cuponsUsados->map(fn (array $row) => [
                    $row['codigo'],
                    $row['tipo'],
                    $row['usos'],
                    number_format($row['desconto_total'], 2, '.', ''),
                ]),
            ],
            default => [
                ['Dia', 'Pedidos', 'Faturamento', 'Ticket médio', 'Desconto'],
                $this->vendasPorDia->map(fn (array $row) => [
                    $row['dia'],
                    $row['pedidos'],
                    number_format($row['faturamento'], 2, '.', ''),
                    number_format($row['ticket_medio'], 2, '.', ''),
                    number_format($row['desconto'], 2, '.', ''),
                ]),
            ],
        };

        return response()->streamDownload(function () use ($header, $rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $reportKey.'-'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
                    wire:click="$set('activeReport', '{{ $report['key'] }}')"
                    data-report="{{ $report['key'] }}"
                    @class([
                        'cursor-pointer rounded-xl p-4 text-left',
                        'border-2 border-brand bg-highlight' => $report['key'] === $this->activeReport,
                        'border border-border-light bg-white' => $report['key'] !== $this->activeReport,
                    ])
                >
                    <div class="font-heading text-sm font-bold text-ink">{{ $report['name'] }}</div>
                    <div class="mt-1 font-sans text-xs text-muted">{{ $report['description'] }}</div>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Bloco: Filtros (compartilhado por todos os relatórios) --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
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
                <select wire:model.live="origem" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todas</option>
                    @foreach ($this->origemOptions as $utmSource)
                        <option value="{{ $utmSource }}">{{ $utmSource }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Vendas por período --}}
    @if ($this->activeReport === 'vendas-por-periodo')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por período</h2>

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
    @endif

    {{-- Vendas por produto --}}
    @if ($this->activeReport === 'vendas-por-produto')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por produto</h2>

        <x-comparison-table
            :columns="['Produto', 'Quantidade', 'Faturamento', 'Ticket médio']"
            :rows="$this->vendasPorProduto->map(fn ($row) => [
                $row['produto'],
                $row['quantidade'],
                Number::currency($row['faturamento'], in: 'BRL', locale: 'pt_BR'),
                Number::currency($row['ticket_medio'], in: 'BRL', locale: 'pt_BR'),
            ])->all()"
        />

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('vendas-por-produto')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Funil operacional --}}
    @if ($this->activeReport === 'funil-operacional')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Funil operacional</h2>
        <x-funil-operacional :stages="$this->funilOperacionalFiltrado" />

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('funil-operacional')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Pagos sem dados --}}
    @if ($this->activeReport === 'pagos-sem-dados')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Pagos sem dados</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pago em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pagosSemDadosFiltrado as $order)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->number }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->items->pluck('productVariant.product.name')->unique()->implode(', ') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->paid_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum pedido pago sem dados de emissão no período selecionado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('pagos-sem-dados')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Atribuição --}}
    @if ($this->activeReport === 'atribuicao')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Atribuição</h2>

        <x-comparison-table
            :columns="['Origem', 'Pedidos', 'Faturamento']"
            :rows="$this->atribuicao->map(fn ($row) => [
                $row['origem'],
                $row['pedidos'],
                Number::currency($row['faturamento'], in: 'BRL', locale: 'pt_BR'),
            ])->all()"
        />

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('atribuicao')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Conciliação do gateway --}}
    @if ($this->activeReport === 'conciliacao-do-gateway')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Conciliação do gateway</h2>
        <div class="rounded-lg border border-border-light bg-surface-alt p-5">
            <p class="font-heading text-sm font-bold text-ink">Relatório indisponível — em breve</p>
            <p class="mt-2 font-sans text-xs text-muted">
                Os valores de repasse (<code>gateway_fee</code>, <code>net_amount</code> e <code>expected_settlement_date</code>) nunca são persistidos por nenhuma cobrança hoje —
                decisão já registrada em <code>checkout-pagamento-safe2pay/SPEC.md</code> (Q-03): esses campos pertencem a uma futura feature de conciliação/repasse financeiro.
            </p>
        </div>
    </section>
    @endif

    {{-- Estornos --}}
    @if ($this->activeReport === 'estornos')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Estornos</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Motivo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->estornos as $refund)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $refund->payment->order->number }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $refund->payment->order->customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $refund->reason->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($refund->amount, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $refund->requested_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum estorno no período selecionado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('estornos')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Cupons --}}
    @if ($this->activeReport === 'cupons')
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Cupons</h2>

        <x-comparison-table
            :columns="['Código', 'Tipo', 'Usos', 'Desconto total']"
            :rows="$this->cuponsUsados->map(fn ($row) => [
                $row['codigo'],
                $row['tipo'],
                $row['usos'],
                Number::currency($row['desconto_total'], in: 'BRL', locale: 'pt_BR'),
            ])->all()"
        />

        <div class="mt-4 flex gap-2">
            <button type="button" wire:click="exportarCsv('cupons')" wire:loading.attr="disabled" wire:target="exportarCsv" class="cursor-pointer rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink disabled:cursor-not-allowed">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>
    @endif

    {{-- Base de renovação --}}
    @if ($this->activeReport === 'base-de-renovacao')
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
    @endif
</div>
