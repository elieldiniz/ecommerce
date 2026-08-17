<?php

use App\Actions\Checkout\CreateOrderFromCart;
use App\Actions\Checkout\RecalculateOrderTotals;
use App\Actions\Payments\ChargeBoletoPayment;
use App\Actions\Payments\ChargeCardPayment;
use App\Actions\Payments\ChargePixPayment;
use App\Exceptions\Payments\InstallmentLimitExceededException;
use App\Exceptions\Payments\MissingVisitorIdException;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\HolderType;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.checkout-layout', ['activeStep' => 2])] #[Title('Checkout')] class extends Component {
    public ?int $variant = null;

    public ?int $orderId = null;

    public string $paymentMethodSlug = 'pix';

    public int $installments = 1;

    public string $couponCode = '';

    public string $confirmedTotal = '0.00';

    public string $personType = 'pj';

    public string $document = '';

    public string $legalName = '';

    public string $email = '';

    public string $phone = '';

    public string $postalCode = '';

    public bool $marketingOptIn = true;

    public string $visitorId = '';

    public string $cardToken = '';

    public function mount(?int $variant = null): void
    {
        $this->variant = $variant ?? ProductVariant::query()->where('is_active', true)->orderBy('id')->value('id');
        $this->paymentMethodSlug = PaymentMethod::query()->where('is_active', true)->orderBy('position')->value('slug') ?? 'pix';
        $this->confirmedTotal = $this->totals['total'];
    }

    #[Computed]
    public function productVariant(): ?ProductVariant
    {
        if ($this->variant === null) {
            return null;
        }

        return ProductVariant::query()->with(['product', 'certificateFormat'])->find($this->variant);
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    #[Computed]
    public function paymentMethods(): Collection
    {
        return PaymentMethod::query()->where('is_active', true)->orderBy('position')->get();
    }

    #[Computed]
    public function selectedPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethods->firstWhere('slug', $this->paymentMethodSlug);
    }

    #[Computed]
    public function coupon(): ?Coupon
    {
        if (trim($this->couponCode) === '') {
            return null;
        }

        return Coupon::query()->where('code', strtoupper(trim($this->couponCode)))->where('is_active', true)->first();
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function installmentOptions(): array
    {
        $paymentMethod = $this->selectedPaymentMethod;

        if ($paymentMethod === null || $paymentMethod->slug !== 'cartao') {
            return [1];
        }

        return range(1, max(1, $paymentMethod->max_installments));
    }

    /**
     * Itens do carrinho para recálculo de totais (UI-01, RF-28) — checkout de item único,
     * sem infraestrutura de carrinho multi-item (fora do escopo desta SPEC).
     *
     * @return Collection<int, array{unit_price: string, quantity: int}>
     */
    #[Computed]
    public function cartItemsForTotals(): Collection
    {
        $variant = $this->productVariant;

        if ($variant === null) {
            return collect();
        }

        return collect([
            ['unit_price' => $this->currentUnitPrice($variant), 'quantity' => 1],
        ]);
    }

    /**
     * @return array{subtotal: string, coupon_discount: string, payment_method_discount: string, total: string}
     */
    #[Computed]
    public function totals(): array
    {
        $paymentMethod = $this->selectedPaymentMethod;

        if ($paymentMethod === null) {
            return ['subtotal' => '0.00', 'coupon_discount' => '0.00', 'payment_method_discount' => '0.00', 'total' => '0.00'];
        }

        return (new RecalculateOrderTotals)->execute($this->cartItemsForTotals, $paymentMethod, $this->coupon);
    }

    /**
     * WHEN o cliente seleciona uma forma de pagamento, recalcula e exibe os 3 valores
     * sem recarregar a página (UI-01); parcelas voltam a 1 para nunca ultrapassar
     * `max_installments` da forma recém-selecionada (UI-02).
     */
    public function selecionarFormaPagamento(string $slug): void
    {
        $this->paymentMethodSlug = $slug;
        $this->installments = 1;
        $this->confirmedTotal = $this->totals['total'];
    }

    public function aplicarCupom(): void
    {
        $this->confirmedTotal = $this->totals['total'];
    }

    /**
     * Cria o pedido (RF-01) e a cobrança correspondente (RF-04..RF-08, RF-13, RF-14,
     * RF-16, RF-28, RF-30, RF-31). Se já existir um pedido `awaiting_payment` desta
     * sessão de checkout e a forma de pagamento mudou, atualiza o pedido e cria uma
     * nova cobrança sem cancelar a anterior (RF-25).
     */
    public function finalizarCompra(): void
    {
        $this->resetErrorBag();

        $variant = $this->productVariant;
        $paymentMethod = $this->selectedPaymentMethod;

        if ($variant === null || $paymentMethod === null) {
            $this->addError('geral', 'Não foi possível processar o pedido. Tente novamente em instantes.');

            return;
        }

        $this->validate($this->customerRules(), $this->customerMessages());

        if ($paymentMethod->slug === 'cartao') {
            $this->validate([
                'cardToken' => ['required', 'string'],
                'visitorId' => ['required', 'string'],
            ], [
                'cardToken.required' => 'Não foi possível capturar os dados do cartão. Tente novamente.',
                'visitorId.required' => 'Não foi possível validar o dispositivo. Recarregue a página.',
            ]);
        }

        $customer = Customer::query()->updateOrCreate(
            ['document' => $this->document],
            [
                'holder_type_id' => HolderType::query()->where('slug', $this->personType)->value('id'),
                'legal_name' => $this->legalName,
                'email' => $this->email,
                'phone' => $this->phone,
                'terms_accepted_at' => now(),
                'marketing_opt_in' => $this->marketingOptIn,
            ],
        );

        $coupon = $this->coupon;
        $order = $this->orderId !== null ? Order::query()->find($this->orderId) : null;

        if ($order === null || $order->status->slug !== 'awaiting_payment') {
            $order = (new CreateOrderFromCart)->execute(
                $customer,
                collect([['product_variant_id' => $variant->id, 'quantity' => 1]]),
                $paymentMethod,
                $coupon,
                request()->ip() ?? '127.0.0.1',
                (string) (request()->userAgent() ?? 'desconhecido'),
            );
            $this->orderId = $order->id;
        } elseif ((int) $order->payment_method_id !== $paymentMethod->id) {
            $totals = (new RecalculateOrderTotals)->execute(
                $order->items->map(fn ($item) => ['unit_price' => (string) $item->unit_price, 'quantity' => $item->quantity]),
                $paymentMethod,
                $coupon,
            );
            $order->update([
                'payment_method_id' => $paymentMethod->id,
                'subtotal' => $totals['subtotal'],
                'coupon_discount' => $totals['coupon_discount'],
                'payment_method_discount' => $totals['payment_method_discount'],
                'total' => $totals['total'],
            ]);
            $order->refresh();
        }

        try {
            match ($paymentMethod->slug) {
                'pix' => (new ChargePixPayment)->execute($order, $this->confirmedTotal),
                'cartao' => (new ChargeCardPayment)->execute($order, $this->confirmedTotal, $this->cardToken, $this->installments, $this->visitorId),
                'boleto' => (new ChargeBoletoPayment)->execute($order, $this->confirmedTotal),
                default => throw new \RuntimeException('Forma de pagamento não suportada.'),
            };
        } catch (PaymentTotalMismatchException) {
            $this->addError('geral', 'O valor do pedido mudou. Atualize a página e tente novamente.');

            return;
        } catch (InstallmentLimitExceededException|MissingVisitorIdException $e) {
            $this->addError('geral', $e->getMessage());

            return;
        }

        $this->redirectRoute('pedido.pagamento', ['id' => $order->id]);
    }

    private function currentUnitPrice(ProductVariant $variant): string
    {
        $now = now();

        $hasActivePromotion = $variant->promotional_price !== null
            && ($variant->promotion_starts_at === null || $variant->promotion_starts_at->lte($now))
            && ($variant->promotion_ends_at === null || $variant->promotion_ends_at->gte($now));

        return (string) ($hasActivePromotion ? $variant->promotional_price : $variant->price);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function customerRules(): array
    {
        return [
            'personType' => ['required', Rule::in(['pf', 'pj'])],
            'document' => ['required', 'string', 'max:14'],
            'legalName' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:20'],
            'postalCode' => ['required', 'string', 'max:9'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function customerMessages(): array
    {
        return [
            'document.required' => 'Informe o CPF ou CNPJ.',
            'legalName.required' => 'Informe a razão social ou o nome completo.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'phone.required' => 'Informe o telefone com DDD.',
            'postalCode.required' => 'Informe o CEP.',
        ];
    }
}; ?>

<main class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-6 py-8 md:grid-cols-3">
    <div class="flex flex-col gap-6 md:col-span-2">
        {{-- Bloco: Seus dados --}}
        <section class="rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seus dados</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                    <select wire:model="personType" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                        <option value="pf">Pessoa física</option>
                        <option value="pj">Pessoa jurídica</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">{{ $personType === 'pf' ? 'CPF' : 'CNPJ' }}</label>
                    <input type="text" wire:model="document" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    @error('document')
                        <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social</label>
                    <input type="text" wire:model="legalName" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    @error('legalName')
                        <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    @error('email')
                        <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone com DDD</label>
                    <input type="text" wire:model="phone" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    @error('phone')
                        <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                    <input type="text" wire:model="postalCode" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    @error('postalCode')
                        <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <label class="mt-4 flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" wire:model="marketingOptIn">
                Quero receber novidades e promoções por e-mail
            </label>
        </section>

        {{-- Bloco: Como você prefere pagar --}}
        <section class="rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Como você prefere pagar</h2>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($this->paymentMethods as $method)
                    <button
                        type="button"
                        wire:click="selecionarFormaPagamento('{{ $method->slug }}')"
                        data-payment-method="{{ $method->slug }}"
                        @class([
                            'rounded-lg p-4 text-left',
                            'border-2 border-brand bg-highlight' => $paymentMethodSlug === $method->slug,
                            'border border-border-light bg-white' => $paymentMethodSlug !== $method->slug,
                        ])
                    >
                        <div class="font-heading text-sm font-bold text-ink">{{ $method->name }}</div>
                        <div class="mt-1 font-sans text-xs text-muted">
                            @if ($method->slug === 'pix')
                                Confirmação imediata
                            @elseif ($method->slug === 'cartao')
                                Até {{ $method->max_installments }}x
                            @else
                                Compensa em 1 a 3 dias úteis
                            @endif
                            · {{ (float) $method->discount_percentage > 0 ? rtrim(rtrim(number_format((float) $method->discount_percentage, 2, ',', '.'), '0'), ',').'% de desconto' : 'Sem desconto' }}
                        </div>
                    </button>
                @endforeach
            </div>

            @if ($paymentMethodSlug === 'cartao')
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Parcelas</label>
                        <select wire:model="installments" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                            @foreach ($this->installmentOptions as $option)
                                <option value="{{ $option }}">{{ $option }}x</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número do cartão</label>
                        <input type="text" id="s2p-card-number" autocomplete="cc-number" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome impresso no cartão</label>
                        <input type="text" id="s2p-card-holder" autocomplete="cc-name" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Validade</label>
                        <input type="text" id="s2p-card-expiry" placeholder="MM/AA" autocomplete="cc-exp" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">CVV</label>
                        <input type="text" id="s2p-card-cvv" autocomplete="cc-csc" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                </div>
            @endif

            {{-- Campos ocultos preenchidos pelo script de antifraude/tokenização da Safe2Pay
                 (Cofre de Chaves) antes do submit — nunca `CardNumber`/`SecurityCode`/`ExpirationDate`
                 chegam ao backend (RF-13, RNF-01). Nome exato do snippet oficial da Safe2Pay a
                 confirmar na documentação (Q-08); os campos abaixo já formam a interface estável
                 consumida por `finalizarCompra()`. --}}
            <input type="hidden" wire:model="visitorId" id="s2p-visitor-id">
            <input type="hidden" wire:model="cardToken" id="s2p-card-token">
        </section>
    </div>

    {{-- Bloco: Seu pedido (resumo lateral) --}}
    <aside class="h-fit rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seu pedido</h2>

        <div class="mb-4 font-sans text-sm text-ink">
            @if ($this->productVariant)
                {{ trim($this->productVariant->product->name.' '.$this->productVariant->certificateFormat->name) }} · {{ $this->productVariant->validity_months }} meses
            @else
                Nenhum item disponível no momento.
            @endif
        </div>

        <div class="mb-4 flex gap-2">
            <input type="text" wire:model="couponCode" placeholder="Código do cupom" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            <button type="button" wire:click="aplicarCupom" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Aplicar</button>
        </div>

        <dl class="flex flex-col gap-2 border-t border-border pt-4 font-sans text-sm">
            <div class="flex justify-between text-muted">
                <dt>Subtotal</dt>
                <dd data-total="subtotal">{{ Number::currency($this->totals['subtotal'], in: 'BRL', locale: 'pt_BR') }}</dd>
            </div>
            <div class="flex justify-between text-muted">
                <dt>Desconto do cupom</dt>
                <dd data-total="coupon-discount">- {{ Number::currency($this->totals['coupon_discount'], in: 'BRL', locale: 'pt_BR') }}</dd>
            </div>
            <div class="flex justify-between text-muted">
                <dt>Desconto da forma de pagamento</dt>
                <dd data-total="payment-method-discount">- {{ Number::currency($this->totals['payment_method_discount'], in: 'BRL', locale: 'pt_BR') }}</dd>
            </div>
            <div class="flex justify-between border-t border-border pt-2 font-heading font-bold text-ink">
                <dt>Total</dt>
                <dd data-total="total">{{ Number::currency($this->totals['total'], in: 'BRL', locale: 'pt_BR') }}</dd>
            </div>
        </dl>

        @error('geral')
            <div class="mt-4 rounded-lg border border-[#8f2020] bg-[#fbe9e9] px-3 py-2.5 font-sans text-xs text-[#8f2020]">{{ $message }}</div>
        @enderror

        <button type="button" wire:click="finalizarCompra" class="mt-4 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white">Finalizar compra</button>
    </aside>
</main>

@script
<script>
    // Antifraude Safe2Pay (RF-31): gera um identificador estável de dispositivo/sessão
    // enquanto o snippet oficial do Cofre de Chaves não é confirmado (Q-08).
    if (! $wire.visitorId) {
        $wire.set('visitorId', crypto.randomUUID());
    }
</script>
@endscript
