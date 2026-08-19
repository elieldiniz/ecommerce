<?php

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'formas-pagamento', 'title' => 'Formas de pagamento'])] #[Title('Novo cupom')] class extends Component
{
    public string $code = '';

    public ?int $type_id = null;

    public string $value = '';

    public ?int $usage_limit = null;

    public ?int $per_customer_limit = null;

    public ?int $restricted_variant_id = null;

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_active = true;

    /**
     * @return Collection<int, CouponType>
     */
    #[Computed]
    public function couponTypes(): Collection
    {
        return CouponType::orderBy('name')->get();
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function productVariants(): Collection
    {
        return ProductVariant::with('product')->orderBy('product_id')->orderBy('sku')->get();
    }

    public function updatedRestrictedVariantId(mixed $value): void
    {
        $this->restricted_variant_id = $value === '' ? null : (int) $value;
    }

    public function createCoupon(): void
    {
        $validated = $this->validate($this->couponRules(), $this->couponMessages());

        DB::transaction(function () use ($validated): void {
            Coupon::create([...$validated, 'uses_count' => 0]);
        });

        Flux::toast(variant: 'success', text: __('Cupom criado.'));

        $this->redirectRoute('painel.formas-pagamento');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function couponRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')],
            'type_id' => ['required', 'integer', 'exists:coupon_types,id'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'restricted_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function couponMessages(): array
    {
        return [
            'code.required' => 'Informe o código do cupom.',
            'code.unique' => 'Já existe um cupom com este código.',
            'type_id.required' => 'Selecione o tipo do cupom.',
            'value.required' => 'Informe o valor do cupom.',
            'value.min' => 'O valor deve ser maior que zero.',
            'usage_limit.integer' => 'O limite de usos deve ser um número inteiro.',
            'usage_limit.min' => 'O limite de usos deve ser pelo menos 1.',
            'per_customer_limit.integer' => 'O limite por cliente deve ser um número inteiro.',
            'per_customer_limit.min' => 'O limite por cliente deve ser pelo menos 1.',
            'starts_at.required' => 'Informe a data de início.',
            'ends_at.required' => 'Informe a data de término.',
            'ends_at.after' => 'A data de término deve ser depois da data de início.',
        ];
    }
}
?>

<div>
    <div class="flex items-center justify-between">
        <h1 class="font-heading text-xl font-bold text-ink">Novo cupom</h1>
        <a href="{{ route('painel.formas-pagamento') }}" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Voltar</a>
    </div>

    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <form wire:submit="createCoupon" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Código</label>
                <input type="text" wire:model="code" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('code') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo</label>
                <select wire:model="type_id" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Selecione</option>
                    @foreach ($this->couponTypes as $couponType)
                        <option value="{{ $couponType->id }}">{{ $couponType->name }}</option>
                    @endforeach
                </select>
                @error('type_id') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Valor</label>
                <input type="text" wire:model="value" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('value') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Limite de usos</label>
                <input type="number" wire:model="usage_limit" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('usage_limit') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Limite por cliente</label>
                <input type="number" wire:model="per_customer_limit" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('per_customer_limit') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Restrito à variante</label>
                <select wire:model="restricted_variant_id" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todas as variantes</option>
                    @foreach ($this->productVariants as $variant)
                        <option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->sku }}</option>
                    @endforeach
                </select>
                @error('restricted_variant_id') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Válido de</label>
                <input type="date" wire:model="starts_at" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('starts_at') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Válido até</label>
                <input type="date" wire:model="ends_at" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('ends_at') <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span> @enderror
            </div>
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" wire:model="is_active">
                Ativo
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Salvar cupom</button>
            </div>
        </form>
    </section>
</div>
