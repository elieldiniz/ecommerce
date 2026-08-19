<?php

use App\Actions\Gfsis\RegisterOrderItemWithGfsis;
use App\Actions\Gfsis\ResendIssuanceAccessLink;
use App\Models\Order;
use App\Models\OrderItemGfsis;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'recuperacao', 'title' => 'Fila de recuperação'])] #[Title('Fila de recuperação')] class extends Component
{
    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function filaRecuperacao(): Collection
    {
        return Order::query()
            ->with('customer')
            ->whereHas('status', fn ($q) => $q->where('slug', 'paid'))
            ->whereHas('fulfillmentStatus', fn ($q) => $q->where('slug', 'awaiting_data'))
            ->orderBy('paid_at')
            ->get();
    }

    #[Computed]
    public function recuperados7DiasPercent(): int
    {
        $entrados = Order::query()
            ->whereHas('status', fn ($q) => $q->where('slug', 'paid'))
            ->where('paid_at', '>=', now()->subDays(7))
            ->count();

        if ($entrados === 0) {
            return 0;
        }

        $recuperados = Order::query()
            ->whereHas('status', fn ($q) => $q->where('slug', 'paid'))
            ->where('paid_at', '>=', now()->subDays(7))
            ->whereHas('fulfillmentStatus', fn ($q) => $q->where('slug', '!=', 'awaiting_data'))
            ->count();

        return (int) round(($recuperados / $entrados) * 100);
    }

    #[Computed]
    public function maisAntigoDias(): ?int
    {
        $maisAntigo = $this->filaRecuperacao->first();

        if ($maisAntigo === null) {
            return null;
        }

        return (int) ($maisAntigo->paid_at ?? $maisAntigo->created_at)->diffInDays(now());
    }

    /**
     * @return Collection<int, OrderItemGfsis>
     */
    #[Computed]
    public function falhasIntegracao(): Collection
    {
        return OrderItemGfsis::query()
            ->with('orderItem.order.customer')
            ->whereHas('status', fn ($q) => $q->where('slug', 'falha_envio'))
            ->get();
    }

    public function resendLink(int $orderId): void
    {
        $order = Order::query()->with(['items', 'customer'])->findOrFail($orderId);

        $sucesso = app(ResendIssuanceAccessLink::class)->execute($order);

        if ($sucesso) {
            Flux::toast(variant: 'success', text: __('Link reenviado.'));

            return;
        }

        Flux::toast(variant: 'danger', text: __('Não foi possível enviar o e-mail para :cliente. Verifique o endereço cadastrado.', ['cliente' => $order->customer->email]));
    }

    public function fixAndResend(int $orderItemGfsisId): void
    {
        $orderItemGfsis = OrderItemGfsis::query()->with('orderItem.order')->findOrFail($orderItemGfsisId);

        $sucesso = app(RegisterOrderItemWithGfsis::class)->execute($orderItemGfsis->orderItem->order);

        if ($sucesso) {
            Flux::toast(variant: 'success', text: __('Reenviado ao GFSIS com sucesso.'));

            return;
        }

        $erro = $orderItemGfsis->fresh()->last_error ?? __('erro desconhecido.');

        Flux::toast(variant: 'danger', text: __('Falha ao reenviar ao GFSIS: :erro', ['erro' => $erro]));
    }
}
?>

<div>
    {{-- Bloco: Indicadores --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Indicadores</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-kpi-card label="Pagos sem dados" value="{{ $this->filaRecuperacao->count() }}" support="{{ Number::currency($this->filaRecuperacao->sum('total'), in: 'BRL', locale: 'pt_BR') }} parados" />
            <x-kpi-card label="Recuperados em 7 dias" value="{{ $this->recuperados7DiasPercent }}%" />
            <x-kpi-card label="Mais antigo" value="{{ $this->maisAntigoDias !== null ? $this->maisAntigoDias.' dias' : '—' }}" />
            <x-kpi-card label="Falha de envio" value="{{ $this->falhasIntegracao->count() }}" />
        </div>
    </section>

    {{-- Bloco: Fila ordenada por tempo --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Fila ordenada por tempo</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dias</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->filaRecuperacao as $order)
                        @php $diasNaFila = (int) ($order->paid_at ?? $order->created_at)->diffInDays(now()); @endphp
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->number }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order->customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                {{ $diasNaFila }}
                                @if ($diasNaFila >= 5)
                                    <x-badge-status variant="erro">Contato manual</x-badge-status>
                                @endif
                            </td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Ligar</button>
                                <button
                                    type="button"
                                    wire:click="resendLink({{ $order->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="resendLink({{ $order->id }})"
                                    class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink"
                                >Reenviar link</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum pedido na fila</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Régua automática --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Régua automática</h2>
        <x-comparison-table
            :columns="['Momento', 'Canal', 'Mensagem']"
            :rows="[
                ['Imediato', 'E-mail', 'Confirmação de pagamento com link do formulário'],
                ['2 horas', 'WhatsApp', 'Lembrete curto com link'],
                ['24 horas', 'E-mail', 'Reforço explicando que a emissão não começa sem os dados'],
                ['3 dias', 'WhatsApp', 'Contato com oferta de preenchimento assistido'],
                ['5 dias', 'Painel', 'Marca para contato humano'],
            ]"
        />
    </section>

    {{-- Bloco: Falhas de integração --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Falhas de integração</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Erro</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tentativas</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->falhasIntegracao as $orderItemGfsis)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $orderItemGfsis->orderItem->order->number }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $orderItemGfsis->last_error ?? '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $orderItemGfsis->attempts }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button
                                    type="button"
                                    wire:click="fixAndResend({{ $orderItemGfsis->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="fixAndResend({{ $orderItemGfsis->id }})"
                                    class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink"
                                >Corrigir e reenviar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhuma falha registrada</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
