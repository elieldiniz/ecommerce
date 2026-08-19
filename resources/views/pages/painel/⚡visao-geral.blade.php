<?php

use App\Models\AdsConversion;
use App\Models\Order;
use App\Models\OrderItemGfsis;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'visao-geral', 'title' => 'Visão geral'])] #[Title('Visão geral')] class extends Component
{
    #[Computed]
    public function pedidosCriadosCount(): int
    {
        return Order::query()->count();
    }

    #[Computed]
    public function pedidosPagosCount(): int
    {
        return Order::query()->whereNotNull('paid_at')->count();
    }

    #[Computed]
    public function faturamento(): float
    {
        return (float) Order::query()->whereNotNull('paid_at')->sum('total');
    }

    #[Computed]
    public function ticketMedio(): float
    {
        return $this->pedidosPagosCount > 0 ? $this->faturamento / $this->pedidosPagosCount : 0.0;
    }

    #[Computed]
    public function taxaConversao(): int
    {
        return $this->pedidosCriadosCount > 0
            ? (int) round(($this->pedidosPagosCount / $this->pedidosCriadosCount) * 100)
            : 0;
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function pagosSemDadosQueue(): Collection
    {
        return Order::query()
            ->whereHas('status', fn ($q) => $q->where('slug', 'paid'))
            ->whereHas('fulfillmentStatus', fn ($q) => $q->where('slug', 'awaiting_data'))
            ->orderBy('paid_at')
            ->get();
    }

    /**
     * @return Collection<int, OrderItemGfsis>
     */
    #[Computed]
    public function falhaIntegracaoQueue(): Collection
    {
        return OrderItemGfsis::query()
            ->whereHas('status', fn ($q) => $q->where('slug', 'falha_envio'))
            ->oldest()
            ->get();
    }

    /**
     * @return Collection<int, AdsConversion>
     */
    #[Computed]
    public function conversoesNaoEnviadasQueue(): Collection
    {
        return AdsConversion::query()
            ->whereHas('status', fn ($q) => $q->whereIn('slug', ['pending', 'failed']))
            ->oldest()
            ->get();
    }

    /**
     * @return Collection<int, Refund>
     */
    #[Computed]
    public function reembolsosPendentesQueue(): Collection
    {
        return Refund::query()->whereNull('completed_at')->orderBy('requested_at')->get();
    }

    /**
     * @return Collection<int, OrderItemGfsis>
     */
    #[Computed]
    public function certificadosVencendoQueue(): Collection
    {
        return OrderItemGfsis::query()
            ->whereBetween('certificate_expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->get();
    }

    /**
     * @return array<int, array{name: string, quantity: int, percentage?: string}>
     */
    #[Computed]
    public function funilStages(): array
    {
        $criados = $this->pedidosCriadosCount;
        $pagos = $this->pedidosPagosCount;

        $dadosCompletos = Order::query()->whereNotNull('paid_at')
            ->whereHas('fulfillmentStatus', fn ($q) => $q->whereIn('slug', ['data_complete', 'sent_to_gfsis', 'send_failed']))
            ->count();

        $enviadosGfsis = Order::query()->whereNotNull('paid_at')
            ->whereHas('items.gfsis.status', fn ($q) => $q->whereIn('slug', ['enviado_gfsis', 'aprovado', 'emitido']))
            ->count();

        $emitidos = Order::query()->whereNotNull('paid_at')
            ->whereHas('items.gfsis.status', fn ($q) => $q->where('slug', 'emitido'))
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
     * @return array<int, array{queue: string, quantity: int, oldest: string, href: string|null}>
     */
    #[Computed]
    public function exigeAcaoRows(): array
    {
        $diasAtras = function (?\Illuminate\Support\Carbon $data): string {
            if ($data === null) {
                return '—';
            }

            $dias = (int) $data->diffInDays(now());

            return $dias === 1 ? '1 dia' : "{$dias} dias";
        };

        return [
            [
                'queue' => 'Pagos sem dados de emissão',
                'quantity' => $this->pagosSemDadosQueue->count(),
                'oldest' => $diasAtras($this->pagosSemDadosQueue->first()?->paid_at),
                'href' => route('painel.recuperacao'),
            ],
            [
                'queue' => 'Falha de envio ao GFSIS',
                'quantity' => $this->falhaIntegracaoQueue->count(),
                'oldest' => $diasAtras($this->falhaIntegracaoQueue->first()?->created_at),
                'href' => route('painel.recuperacao'),
            ],
            [
                'queue' => 'Conversões não enviadas',
                'quantity' => $this->conversoesNaoEnviadasQueue->count(),
                'oldest' => $diasAtras($this->conversoesNaoEnviadasQueue->first()?->created_at),
                'href' => null,
            ],
            [
                'queue' => 'Reembolsos pendentes',
                'quantity' => $this->reembolsosPendentesQueue->count(),
                'oldest' => $diasAtras($this->reembolsosPendentesQueue->first()?->requested_at),
                'href' => null,
            ],
            [
                'queue' => 'Certificados vencendo em 30 dias',
                'quantity' => $this->certificadosVencendoQueue->count(),
                'oldest' => '—',
                'href' => null,
            ],
        ];
    }
}
?>

<div>
    {{-- Bloco: Indicadores --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Indicadores</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <x-kpi-card label="Faturamento" value="{{ Number::currency($this->faturamento, in: 'BRL', locale: 'pt_BR') }}" support="{{ $this->pedidosPagosCount }} pedidos" />
            <x-kpi-card label="Ticket médio" value="{{ Number::currency($this->ticketMedio, in: 'BRL', locale: 'pt_BR') }}" />
            <x-kpi-card label="Taxa de conversão" value="{{ $this->taxaConversao }}%" support="Pedidos pagos / criados" />
            <x-kpi-card label="Aguardando dados" value="{{ $this->pagosSemDadosQueue->count() }}" support="Pagos sem dados de emissão" />
            <x-kpi-card label="Falha de integração" value="{{ $this->falhaIntegracaoQueue->count() }}" support="Envio ao GFSIS" />
        </div>
    </section>

    {{-- Bloco: Funil operacional --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Funil operacional</h2>
        <x-funil-operacional :stages="$this->funilStages" />
        <p class="mt-3 font-sans text-xs text-muted-light">A maior queda revela o gargalo.</p>
    </section>

    {{-- Bloco: Exige ação --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Exige ação</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Fila</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Quantidade</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Mais antigo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->exigeAcaoRows as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['queue'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['quantity'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['oldest'] }}</td>
                            <td class="border border-border px-3 py-2.5">
                                @if ($row['href'])
                                    <a href="{{ $row['href'] }}" class="inline-block rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt" wire:navigate>Abrir</a>
                                @else
                                    <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Abrir</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Vendas por dia --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por dia</h2>
        <div class="flex h-48 items-center justify-center rounded-lg border border-border-light bg-surface-alt font-sans text-xs text-muted-light">
            Gráfico de barras · vendas por dia
        </div>
    </section>
</div>
