<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layout')] #[Title('Redefinir senha — Cliente')] class extends Component {
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('customers')->reset(
            [
                'token' => $this->token,
                'email' => strtolower($this->email),
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', 'Senha redefinida com sucesso. Faça login com sua nova senha.');

            $hasCartIntent = session()->pull('customer_auth_intent') === 'carrinho';
            $this->redirectRoute('customer.login', $hasCartIntent ? ['from' => 'carrinho'] : []);

            return;
        }

        $this->addError('email', 'Este link de redefinição de senha é inválido ou expirou.');
    }
}; ?>

<main class="mx-auto max-w-md px-6 py-16">
    <h1 class="mb-2 text-center font-heading text-2xl font-bold text-ink">Redefinir senha</h1>
    <p class="mb-8 text-center font-sans text-sm text-muted">Digite sua nova senha abaixo.</p>

    <form wire:submit="resetPassword" class="flex flex-col gap-4">
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
            <input type="email" wire:model="email" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink" autofocus>
            @error('email')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nova senha</label>
            <input type="password" wire:model="password" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            @error('password')
                <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Confirmar nova senha</label>
            <input type="password" wire:model="password_confirmation" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>

        <button type="submit" class="mt-2 w-full rounded-lg bg-brand px-4 py-3 font-heading text-sm font-semibold text-white">Redefinir senha</button>
    </form>
</main>
