<?php

use App\Actions\Fortify\CreateNewCustomer;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout')] #[Title('Criar Conta — Cliente')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $from = null;

    public function mount(): void
    {
        $this->from = request()->query('from') === 'carrinho' ? 'carrinho' : null;
    }

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = app(CreateNewCustomer::class)->execute([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        Auth::guard('customer')->login($customer);

        $sessionCartId = session()->get('cart_session_id');

        if ($sessionCartId) {
            Cart::getOrCreateForCustomer($customer)->mergeFromSession($sessionCartId);
            session()->forget('cart_session_id');
        }

        $this->redirectRoute($this->from === 'carrinho' ? 'carrinho' : 'minha-conta.pedidos');
    }
}; ?>

<main class="mx-auto max-w-md px-6 py-16">
    <h1 class="mb-2 text-center font-heading text-2xl font-bold text-ink">Criar sua conta</h1>
    <p class="mb-8 text-center font-sans text-sm text-muted">Cadastre-se para acompanhar seus pedidos.</p>

    <form wire:submit="register" class="flex flex-col gap-4">
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome completo</label>
            <input type="text" wire:model="name" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink" autofocus>
            @error('name')
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
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Senha</label>
            <input type="password" wire:model="password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            @error('password')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Confirmar senha</label>
            <input type="password" wire:model="password_confirmation" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>

        <button type="submit" class="mt-2 w-full rounded-lg bg-brand px-4 py-3 font-heading text-sm font-semibold text-white">Criar conta</button>
    </form>

    <p class="mt-6 text-center font-sans text-sm text-muted">
        Já tem conta?
        <a href="{{ route('customer.login', $from ? ['from' => $from] : []) }}" class="font-semibold text-brand" wire:navigate>Entrar</a>
    </p>

    <p class="mt-2 text-center font-sans text-sm text-muted">
        <a href="{{ route('carrinho') }}" class="text-muted" wire:navigate>Voltar ao carrinho</a>
    </p>
</main>
