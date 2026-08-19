<?php

use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.conta-layout', ['activePage' => 'pedidos', 'title' => 'Meus pedidos'])] #[Title('Meus pedidos')] class extends Component
{
    /**
     * @return \Illuminate\Support\Collection<int, array{
     *   order: Order,
     *   item: OrderItem,
     *   gfsis: ?OrderItemGfsis,
     *   issuance: ?IssuanceData,
     *   state: string,
     *   badge: string,
     *   titular: ?string,
     *   validade: ?string,
     *   videoconferencia: ?string,
     *   pagoEm: ?string,
     *   situacao: ?string,
     *   emissaoToken: ?string,
     * }>
     */
    #[Computed]
    public function cards(): \Illuminate\Support\Collection
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return collect();
        }

        $orders = Order::where('customer_id', $customer->id)
            ->with([
                'status',
                'fulfillmentStatus',
                'items.productVariant.product',
                'items.productVariant.certificateFormat',
                'items.issuanceData',
                'items.gfsis.status',
            ])
            ->orderByDesc('created_at')
            ->get();

        $cards = collect();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $gfsis = $item->gfsis;
                $issuance = $item->issuanceData;
                $state = $this->resolveItemState($order, $gfsis);

                $cards->push([
                    'order' => $order,
                    'item' => $item,
                    'gfsis' => $gfsis,
                    'issuance' => $issuance,
                    'state' => $state,
                    'badge' => $this->resolveBadge($state),
                    'produto' => $item->name_snapshot ?? $item->productVariant?->product?->name,
                    'titular' => $issuance?->holder_name,
                    'validade' => $gfsis?->certificate_expires_at?->format('d/m/Y'),
                    'videoconferencia' => $gfsis && $gfsis->appointment_date
                        ? $gfsis->appointment_date->format('d/m/Y').' às '.$gfsis->appointment_time
                        : null,
                    'pagoEm' => $order->paid_at?->format('d/m/Y'),
                    'situacao' => $this->resolveSituacao($state),
                    'emissaoToken' => $issuance?->access_token,
                ]);
            }
        }

        return $cards;
    }

    private function resolveItemState(Order $order, ?OrderItemGfsis $gfsis): string
    {
        $fulfillmentSlug = $order->fulfillmentStatus?->slug;
        $orderStatusSlug = $order->status?->slug;
        $gfsisStatusSlug = $gfsis?->status?->slug;

        if ($fulfillmentSlug === 'awaiting_data' && $orderStatusSlug === 'paid') {
            return 'faltam_dados';
        }

        if (in_array($gfsisStatusSlug, ['enviado_gfsis', 'aprovado']) && $gfsis?->appointment_date === null) {
            return 'em_processamento';
        }

        if ($gfsis?->appointment_date !== null && $gfsis?->certificate_expires_at === null) {
            return 'agendado';
        }

        if ($gfsis?->certificate_expires_at !== null && $gfsis->certificate_expires_at->isPast()) {
            return 'vencido';
        }

        if ($gfsis?->certificate_expires_at !== null && $gfsis->certificate_expires_at->lte(now()->addDays(60))) {
            return 'vencendo';
        }

        if ($gfsisStatusSlug === 'emitido') {
            return 'emitido';
        }

        return 'em_processamento';
    }

    private function resolveBadge(string $state): string
    {
        return match ($state) {
            'emitido' => 'emitido',
            'agendado' => 'agendado',
            'vencendo' => 'emitido',
            'vencido' => 'erro',
            default => 'aguardando',
        };
    }

    private function resolveSituacao(string $state): string
    {
        return match ($state) {
            'faltam_dados' => 'Aguardando dados do titular',
            'em_processamento' => 'Aguardando aprovação do GFSIS',
            'agendado' => 'Videoconferência agendada',
            'emitido' => 'Certificado emitido',
            'vencendo' => 'Certificado vencendo',
            'vencido' => 'Certificado vencido',
            default => 'Em processamento',
        };
    }
}
?>

<main class="mx-auto max-w-4xl px-6 py-8">
    <h1 class="mb-5 font-heading text-2xl font-bold text-ink">Meus pedidos</h1>

    @if ($this->cards->isEmpty())
        <div class="rounded-xl border border-border bg-white p-8 text-center">
            <p class="font-sans text-sm text-muted">Você ainda não possui pedidos.</p>
            <a href="{{ route('certificado-digital') }}" class="mt-4 inline-block rounded-lg bg-brand px-4 py-2.5 font-heading text-sm font-semibold text-white">Ver certificados</a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach ($this->cards as $card)
                <div class="rounded-xl border border-border bg-white p-4.5">
                    <x-badge-status :variant="$card['badge']">{{ str($card['state'])->replace('_', ' ')->title() }}</x-badge-status>

                    @if ($card['produto'])
                        <div class="mt-3 font-sans text-sm font-semibold text-ink">{{ $card['produto'] }}</div>
                    @endif

                    @if ($card['titular'])
                        <div class="mt-3 font-sans text-sm font-semibold text-ink">{{ $card['titular'] }}</div>
                    @endif

                    <dl class="mt-2 flex flex-col gap-1 font-sans text-xs text-muted">
                        @if ($card['validade'])
                            <div class="flex justify-between"><dt>Validade até</dt><dd>{{ $card['validade'] }}</dd></div>
                        @endif

                        @if ($card['videoconferencia'])
                            <div class="flex justify-between"><dt>Videoconferência</dt><dd>{{ $card['videoconferencia'] }}</dd></div>
                        @endif

                        @if ($card['pagoEm'])
                            <div class="flex justify-between"><dt>Pago em</dt><dd>{{ $card['pagoEm'] }}</dd></div>
                        @endif

                        @if ($card['situacao'])
                            <div class="flex justify-between"><dt>Situação</dt><dd>{{ $card['situacao'] }}</dd></div>
                        @endif
                    </dl>

                    <div class="mt-3 flex flex-col gap-2">
                        @if ($card['state'] === 'emitido' || $card['state'] === 'vencendo' || $card['state'] === 'vencido')
                            <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Ver nota fiscal</button>
                            <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Baixar certificado</button>
                            <a href="{{ route('renovacao-certificado-digital') }}" class="rounded-lg bg-cta-secondary px-3 py-2 text-center font-sans text-xs font-semibold text-white">Renovar</a>
                        @elseif ($card['state'] === 'agendado')
                            <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Ver o que levar</button>
                            <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Reagendar</button>
                        @elseif ($card['state'] === 'faltam_dados')
                            <a href="/pedido/{{ $card['order']->id }}/emissao/?token={{ $card['emissaoToken'] }}" class="w-full rounded-lg bg-brand px-3 py-2 text-center font-sans text-xs font-semibold text-white">Preencher agora</a>
                        @else
                            <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Acompanhar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</main>
