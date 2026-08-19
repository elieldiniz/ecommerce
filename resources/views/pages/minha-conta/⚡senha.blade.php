<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.conta-layout', ['activePage' => 'senha', 'title' => 'Trocar senha'])] #[Title('Trocar senha')] class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $successMessage = '';

    public function save(): void
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return;
        }

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $customer->password)) {
            $this->addError('current_password', 'A senha atual está incorreta.');

            return;
        }

        $customer->update([
            'password' => $validated['password'],
        ]);

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->successMessage = 'Senha alterada com sucesso!';
    }
}
?>

<div>
    <div class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Alterar senha</h2>

        @if ($this->successMessage)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 font-sans text-sm text-green-700">
                {{ $this->successMessage }}
            </div>
        @endif

        <form wire:submit="save" class="max-w-md space-y-4">
            <div>
                <label for="current_password" class="mb-1 block font-sans text-xs font-semibold text-muted">Senha atual</label>
                <input
                    type="password"
                    id="current_password"
                    wire:model="current_password"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
                @error('current_password') <p class="mt-1 font-sans text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block font-sans text-xs font-semibold text-muted">Nova senha</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
                @error('password') <p class="mt-1 font-sans text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block font-sans text-xs font-semibold text-muted">Confirmar nova senha</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 font-heading text-sm font-semibold text-white hover:bg-brand/90">Alterar senha</button>
            </div>
        </form>
    </div>
</div>
