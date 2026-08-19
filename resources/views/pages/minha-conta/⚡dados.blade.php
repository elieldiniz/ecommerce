<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.conta-layout', ['activePage' => 'dados', 'title' => 'Meus dados'])] #[Title('Meus dados')] class extends Component
{
    public string $legal_name = '';
    public string $email = '';
    public string $phone = '';
    public bool $marketing_opt_in = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            abort(404);
        }

        $this->legal_name = $customer->legal_name ?? '';
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
        $this->marketing_opt_in = $customer->marketing_opt_in ?? false;
    }

    public function save(): void
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return;
        }

        $validated = $this->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email,' . $customer->id],
            'phone' => ['required', 'string', 'max:20'],
            'marketing_opt_in' => ['boolean'],
        ]);

        $customer->update($validated);

        $this->successMessage = 'Dados atualizados com sucesso!';
    }
}
?>

<div>
    <div class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Dados pessoais</h2>

        @if ($this->successMessage)
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 font-sans text-sm text-green-700">
                {{ $this->successMessage }}
            </div>
        @endif

        <form wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="legal_name" class="mb-1 block font-sans text-xs font-semibold text-muted">Nome completo</label>
                <input
                    type="text"
                    id="legal_name"
                    wire:model="legal_name"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
                @error('legal_name') <p class="mt-1 font-sans text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
                @error('email') <p class="mt-1 font-sans text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone</label>
                <input
                    type="text"
                    id="phone"
                    wire:model="phone"
                    class="w-full rounded-lg border border-border-light bg-white px-3 py-2.5 font-sans text-sm text-ink focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                >
                @error('phone') <p class="mt-1 font-sans text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="marketing_opt_in" class="size-4 rounded border-border-light text-brand focus:ring-brand">
                    <span class="font-sans text-sm text-ink">Receber novidades e promoções por e-mail</span>
                </label>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 font-heading text-sm font-semibold text-white hover:bg-brand/90">Salvar alterações</button>
            </div>
        </form>
    </div>

    <div class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Informações da conta</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                <input type="text" value="{{ Auth::guard('customer')->user()->holderType?->name ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Documento (CPF/CNPJ)</label>
                <input type="text" value="{{ Auth::guard('customer')->user()->document }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
        <p class="mt-3 font-sans text-xs text-muted">Para alterar CPF/CNPJ ou tipo de pessoa, entre em contato com o suporte.</p>
    </div>
</div>
