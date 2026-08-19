<?php

use App\Models\PaymentMethod;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'formas-pagamento', 'title' => 'Formas de pagamento'])] #[Title('Editar forma de pagamento')] class extends Component
{
    public int $id;

    public string $name = '';

    public string $discount_percentage = '';

    public int $max_installments = 1;

    public int $position = 0;

    public function mount(int $id): void
    {
        $method = PaymentMethod::find($id);

        if ($method === null) {
            abort(404);
        }

        $this->id = $method->id;
        $this->name = $method->name;
        $this->discount_percentage = (string) $method->discount_percentage;
        $this->max_installments = $method->max_installments;
        $this->position = $method->position;
    }

    public function updatePaymentMethod(): void
    {
        $validated = $this->validate($this->paymentMethodRules(), $this->paymentMethodMessages());

        DB::transaction(function () use ($validated): void {
            PaymentMethod::findOrFail($this->id)->update($validated);
        });

        Flux::toast(variant: 'success', text: __('Forma de pagamento atualizada.'));

        $this->redirectRoute('painel.formas-pagamento');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function paymentMethodRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'discount_percentage' => ['required', 'numeric', 'between:0,100'],
            'max_installments' => ['required', 'integer', 'min:1'],
            'position' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paymentMethodMessages(): array
    {
        return [
            'name.required' => 'Informe o nome exibido.',
            'discount_percentage.required' => 'Informe o desconto.',
            'discount_percentage.between' => 'O desconto deve estar entre 0 e 100.',
            'max_installments.required' => 'Informe o número máximo de parcelas.',
            'max_installments.min' => 'O número máximo de parcelas deve ser pelo menos 1.',
            'position.required' => 'Informe a ordem de exibição.',
        ];
    }
}
?>

<div>
    <div class="flex items-center justify-between">
        <h1 class="font-heading text-xl font-bold text-ink">Editar forma de pagamento</h1>
        <a href="{{ route('painel.formas-pagamento') }}" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Voltar</a>
    </div>

    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <form wire:submit="updatePaymentMethod" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome exibido</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('name') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Desconto (%)</label>
                <input type="text" wire:model="discount_percentage" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('discount_percentage') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Máx. parcelas</label>
                <input type="number" wire:model="max_installments" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('max_installments') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Ordem</label>
                <input type="number" wire:model="position" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('position') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Salvar forma de pagamento</button>
            </div>
        </form>
    </section>
</div>
