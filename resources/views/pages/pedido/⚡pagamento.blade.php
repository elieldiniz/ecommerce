<?php

use App\Actions\Payments\ChargeBoletoPayment;
use App\Actions\Payments\ChargeCardPayment;
use App\Actions\Payments\ChargePixPayment;
use App\Exceptions\Payments\InstallmentLimitExceededException;
use App\Exceptions\Payments\MissingVisitorIdException;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Exceptions\Payments\Safe2PayChargeFailedException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.checkout-layout', ['activeStep' => 2])] #[Title('Aguardando pagamento')] class extends Component {
    public int $id;

    public string $visitorId = '';

    public string $cardToken = '';

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    #[Computed]
    public function order(): ?Order
    {
        return Order::query()->with(['paymentMethod', 'status', 'items.issuanceData'])->find($this->id);
    }

    /**
     * Pagamento mais recente do pedido (RF-24 — um pedido pode ter múltiplas
     * tentativas); recomputado a cada `wire:poll` para refletir `status_id`
     * sem nenhuma ação do cliente.
     */
    #[Computed]
    public function payment(): ?Payment
    {
        return Payment::query()
            ->where('order_id', $this->id)
            ->with('status')
            ->latest('id')
            ->first();
    }

    /**
     * Nova tentativa de pagamento após expiração (RF-11): sempre cria uma nova
     * linha em `payments` via `ChargePixPayment`/`ChargeCardPayment`/`ChargeBoletoPayment`
     * (T11-T13), nunca reaproveitando o `qr_code_payload` do pagamento expirado.
     */
    public function tentarNovamente(): void
    {
        $this->resetErrorBag();

        $order = $this->order;

        if ($order === null) {
            return;
        }

        $paymentMethod = $order->paymentMethod;
        $confirmedTotal = (string) $order->total;

        try {
            match ($paymentMethod->slug) {
                'pix' => (new ChargePixPayment)->execute($order, $confirmedTotal),
                'cartao' => (new ChargeCardPayment)->execute($order, $confirmedTotal, $this->cardToken, 1, $this->visitorId),
                'boleto' => (new ChargeBoletoPayment)->execute($order, $confirmedTotal),
                default => throw new \RuntimeException('Forma de pagamento não suportada.'),
            };
        } catch (PaymentTotalMismatchException) {
            $this->addError('geral', 'O valor do pedido mudou. Atualize a página e tente novamente.');

            return;
        } catch (InstallmentLimitExceededException|MissingVisitorIdException $e) {
            $this->addError('geral', $e->getMessage());

            return;
        } catch (Safe2PayChargeFailedException $e) {
            Log::error('safe2pay.charge_failed', [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'response' => $e->rawResponse(),
            ]);
            $this->addError('geral', 'Não foi possível concluir o pagamento. Tente novamente em instantes.');

            return;
        }

        unset($this->payment);
    }
}; ?>

<main class="mx-auto max-w-xl px-6 py-8">
    @php
        $order = $this->order;
        $payment = $this->payment;
        $statusSlug = $payment?->status?->slug;

        if ($order !== null && $order->status?->slug === 'paid') {
            $issuanceData = $order->items->first()?->issuanceData;

            if ($issuanceData !== null) {
                $this->redirect(route('pedido.emissao', ['id' => $order->id, 'token' => $issuanceData->access_token]));
            }
        }
    @endphp

    @error('geral')
        <div class="mb-4 rounded-lg border border-[#8f2020] bg-[#fbe9e9] px-3 py-2.5 font-sans text-xs text-[#8f2020]">{{ $message }}</div>
    @enderror

    @if ($order === null)
        <section class="rounded-xl border border-border bg-white p-6 text-center">
            <p class="font-sans text-sm text-muted">Pedido não encontrado.</p>
        </section>
    @elseif ($payment === null)
        <section class="rounded-xl border border-border bg-white p-6 text-center">
            <p class="font-sans text-sm text-muted">Nenhum pagamento encontrado para este pedido.</p>
        </section>
    @elseif ($statusSlug === 'expired')
        {{-- Pagamento expirado: nunca reapresenta o qr_code_payload/linha digitável
             deste pagamento (RF-11) — apenas oferece nova tentativa. --}}
        <section wire:poll.5s class="rounded-xl border border-border bg-white p-6 text-center" data-payment-status="expired">
            <h1 class="mb-4 font-heading text-lg font-bold text-ink">Esse pagamento expirou</h1>

            <div class="mb-4 font-sans text-sm text-ink">Pedido #{{ $order->number }} · {{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</div>

            <p class="mb-4 font-sans text-xs text-muted-light">Gere uma nova cobrança para continuar.</p>

            @if ($order->paymentMethod->slug === 'cartao')
                <input type="hidden" wire:model="visitorId" id="s2p-visitor-id">
                <input type="hidden" wire:model="cardToken" id="s2p-card-token">
            @endif

            <button type="button" wire:click="tentarNovamente" data-action="tentar-novamente" class="rounded-lg bg-brand px-4 py-3 font-heading text-sm font-semibold text-white">Tentar novamente</button>
        </section>
    @elseif ($order->paymentMethod->slug === 'pix')
        <section wire:poll.5s class="rounded-xl border border-border bg-white p-6 text-center" data-payment-status="{{ $statusSlug }}">
            <h1 class="mb-4 font-heading text-lg font-bold text-ink">Escaneie para pagar</h1>

            <div class="mx-auto mb-4 flex size-44 items-center justify-center overflow-hidden rounded-lg border border-border-light bg-surface-alt">
                @if ($payment->qr_code_image_url)
                    <img src="{{ $payment->qr_code_image_url }}" alt="QR Code Pix" class="h-full w-full object-contain">
                @else
                    <span class="font-sans text-xs text-muted-light">QR Code Pix</span>
                @endif
            </div>

            <div class="mb-4 font-sans text-sm text-ink">Pedido #{{ $order->number }} · {{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</div>

            <div class="mb-2 flex items-center gap-2">
                <input type="text" readonly value="{{ $payment->qr_code_payload }}" data-field="qr-code-payload" class="w-full truncate rounded-lg border border-border-light px-3 py-2.5 font-sans text-xs text-muted">
                <button type="button" class="flex-none rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Copiar código</button>
            </div>

            @if ($payment->expires_at)
                <div class="mb-3 font-sans text-xs font-semibold text-cta-secondary" data-field="expires-at">Expira às {{ $payment->expires_at->format('H:i') }}</div>
            @endif

            <p class="font-sans text-xs text-muted-light">Aguardando confirmação. Esta tela avança sozinha assim que o pagamento cair.</p>
        </section>
    @elseif ($order->paymentMethod->slug === 'boleto')
        <section wire:poll.5s class="rounded-xl border border-border bg-white p-6 text-center" data-payment-status="{{ $statusSlug }}">
            <h1 class="mb-4 font-heading text-lg font-bold text-ink">Pague com boleto</h1>

            <div class="mb-4 font-sans text-sm text-ink">Pedido #{{ $order->number }} · {{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</div>

            <div class="mb-4 flex items-center gap-2">
                <input type="text" readonly value="{{ $payment->boleto_digitable_line }}" data-field="boleto-digitable-line" class="w-full truncate rounded-lg border border-border-light px-3 py-2.5 font-sans text-xs text-muted">
                <button type="button" class="flex-none rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Copiar código</button>
            </div>

            <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener" data-field="receipt-url" class="inline-block rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Baixar boleto</a>

            <p class="mt-4 font-sans text-xs text-muted-light">Aguardando confirmação. Esta tela avança sozinha assim que o pagamento compensar.</p>
        </section>
    @else
        <section wire:poll.5s class="rounded-xl border border-border bg-white p-6 text-center" data-payment-status="{{ $statusSlug }}">
            <h1 class="mb-4 font-heading text-lg font-bold text-ink">Processando seu cartão</h1>

            <div class="mb-4 font-sans text-sm text-ink">Pedido #{{ $order->number }} · {{ Number::currency($order->total, in: 'BRL', locale: 'pt_BR') }}</div>

            <p class="font-sans text-xs text-muted-light">Aguardando confirmação da operadora. Esta tela avança sozinha assim que o pagamento for aprovado.</p>
        </section>
    @endif
</main>
