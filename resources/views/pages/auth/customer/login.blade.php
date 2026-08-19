<?php

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout')] #[Title('Login — Cliente')] class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => strtolower($this->email),
            'password' => $this->password,
        ];

        if (Auth::guard('customer')->attempt($credentials, $this->remember)) {
            $sessionCartId = session()->get('cart_session_id');

            if ($sessionCartId) {
                $customer = Auth::guard('customer')->user();
                Cart::getOrCreateForCustomer($customer)->mergeFromSession($sessionCartId);
                session()->forget('cart_session_id');
            }

            $this->redirectRoute('carrinho');

            return;
        }

        $this->addError('email', 'E-mail ou senha inválidos.');
    }
}; ?>

<main class="mx-auto max-w-md px-6 py-16">
    <h1 class="mb-2 text-center font-heading text-2xl font-bold text-ink">Entrar na sua conta</h1>
    <p class="mb-8 text-center font-sans text-sm text-muted">Acesse seu carrinho e acompanhe seus pedidos.</p>

    <form wire:submit="login" class="flex flex-col gap-4">
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
            <input type="email" wire:model="email" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink" autofocus>
            @error('email')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Senha</label>
            <input type="password" wire:model="password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            @error('password')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" wire:model="remember">
                Lembrar de mim
            </label>

            <a href="{{ route('customer.password.request') }}" class="font-sans text-xs font-semibold text-brand" wire:navigate>Esqueceu sua senha?</a>
        </div>

        <button type="submit" class="mt-2 w-full rounded-lg bg-brand px-4 py-3 font-heading text-sm font-semibold text-white">Entrar</button>
    </form>

    <p class="mt-6 text-center font-sans text-sm text-muted">
        Não tem conta?
        <a href="{{ route('customer.register') }}" class="font-semibold text-brand" wire:navigate>Criar conta</a>
    </p>

    <p class="mt-2 text-center font-sans text-sm text-muted">
        <a href="{{ route('carrinho') }}" class="text-muted" wire:navigate>Voltar ao carrinho</a>
    </p>
</main>
