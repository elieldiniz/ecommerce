<?php

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.checkout-layout', ['activeStep' => 1])] #[Title('Carrinho')] class extends Component {
    #[Computed]
    public function cart(): ?Cart
    {
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            return Cart::where('customer_id', $customer->id)
                ->with('items.productVariant.product', 'items.productVariant.certificateFormat')
                ->first();
        }

        $sessionId = session()->get('cart_session_id') ?? Session::getId();

        return Cart::where('session_id', $sessionId)
            ->with('items.productVariant.product', 'items.productVariant.certificateFormat')
            ->first();
    }

    #[Computed]
    public function cartItems(): array
    {
        $cart = $this->cart;

        if ($cart === null) {
            return [];
        }

        return $cart->items->map(function (CartItem $item) {
            $variant = $item->productVariant;

            return [
                'id' => $item->id,
                'product_name' => $variant->product->name . ' ' . $variant->certificateFormat->name,
                'validity' => $variant->validity_months . ' meses',
                'price' => $variant->price,
                'quantity' => $item->quantity,
                'subtotal' => $variant->price * $item->quantity,
            ];
        })->toArray();
    }

    #[Computed]
    public function total(): float
    {
        return collect($this->cartItems)->sum('subtotal');
    }

    public function removerItem(int $cartItemId): void
    {
        $cart = $this->cart;

        if ($cart) {
            app(\App\Actions\Cart\RemoveFromCart::class)->execute($cart, $cartItemId);
        }
    }

    public function continuar(): void
    {
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            $this->redirectRoute('checkout');
        } else {
            $this->redirectRoute('customer.login');
        }
    }
}; ?>

<main class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-6 py-8 md:grid-cols-3">
    <div class="flex flex-col gap-6 md:col-span-2">
        <section class="rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seu carrinho</h2>

            @if (empty($this->cartItems))
                <p class="font-sans text-sm text-muted">Seu carrinho está vazio.</p>
                <a href="{{ route('certificado-digital') }}" class="mt-4 inline-block font-sans text-sm font-semibold text-brand">Ver certificados</a>
            @else
                <div class="flex flex-col gap-4">
                    @foreach ($this->cartItems as $item)
                        <div class="flex items-center justify-between rounded-lg border border-border-light p-4">
                            <div>
                                <div class="font-heading text-sm font-bold text-ink">{{ $item['product_name'] }}</div>
                                <div class="font-sans text-xs text-muted">{{ $item['validity'] }}</div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="font-sans text-sm font-semibold text-ink">
                                    R$ {{ number_format($item['subtotal'], 2, ',', '.') }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="removerItem({{ $item['id'] }})"
                                    class="font-sans text-xs text-[#8f2020] hover:underline"
                                >
                                    Remover
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <aside class="h-fit rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Resumo</h2>

        <dl class="flex flex-col gap-2 border-t border-border pt-4 font-sans text-sm">
            <div class="flex justify-between text-muted">
                <dt>Subtotal</dt>
                <dd>R$ {{ number_format($this->total, 2, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between border-t border-border pt-2 font-heading font-bold text-ink">
                <dt>Total</dt>
                <dd>R$ {{ number_format($this->total, 2, ',', '.') }}</dd>
            </div>
        </dl>

        <button
            type="button"
            wire:click="continuar"
            @disabled(empty($this->cartItems))
            class="mt-4 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white disabled:opacity-50"
        >
            Continuar
        </button>
    </aside>
</main>
