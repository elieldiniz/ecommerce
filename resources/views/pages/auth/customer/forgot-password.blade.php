<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout')] #[Title('Esqueci minha senha — Cliente')] class extends Component {
    public string $email = '';
    public string $status = '';
    public ?string $from = null;

    public function mount(): void
    {
        $this->from = request()->query('from') === 'carrinho' ? 'carrinho' : null;
    }

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('customers')->sendResetLink([
            'email' => strtolower($this->email),
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            // O link de redefinição vai por e-mail (assíncrono) e não carrega query
            // string — a intenção de voltar pro carrinho precisa atravessar essa
            // lacuna via sessão; reset-password.blade.php lê e apaga essa chave
            // quando a redefinição é concluída.
            if ($this->from === 'carrinho') {
                session(['customer_auth_intent' => 'carrinho']);
            }

            $this->status = 'Enviamos um link de redefinição de senha para o seu e-mail.';
            $this->email = '';

            return;
        }

        $this->addError('email', 'Não encontramos uma conta com esse e-mail.');
    }
}; ?>

<main class="mx-auto max-w-md px-6 py-16">
    <h1 class="mb-2 text-center font-heading text-2xl font-bold text-ink">Esqueci minha senha</h1>
    <p class="mb-8 text-center font-sans text-sm text-muted">Digite seu e-mail para receber o link de redefinição de senha.</p>

    @if ($status)
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 font-sans text-sm text-green-700">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="flex flex-col gap-4">
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
            <input type="email" wire:model="email" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink" autofocus>
            @error('email')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="mt-2 w-full rounded-lg bg-brand px-4 py-3 font-heading text-sm font-semibold text-white">Enviar link de redefinição</button>
    </form>

    <p class="mt-6 text-center font-sans text-sm text-muted">
        <a href="{{ route('customer.login', $from ? ['from' => $from] : []) }}" class="font-semibold text-brand" wire:navigate>Voltar ao login</a>
    </p>
</main>
