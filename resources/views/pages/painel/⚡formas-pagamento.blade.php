<?php

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'formas-pagamento', 'title' => 'Formas de pagamento'])] #[Title('Formas de pagamento')] class extends Component
{
    public ?int $paymentMethodId = null;

    public string $name = '';

    public string $discount_percentage = '';

    public int $max_installments = 1;

    public int $position = 0;

    public ?int $couponId = null;

    public string $code = '';

    public ?int $type_id = null;

    public string $value = '';

    public ?int $usage_limit = null;

    public ?int $per_customer_limit = null;

    public ?int $restricted_variant_id = null;

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $firstMethod = PaymentMethod::orderBy('position')->first();

        if ($firstMethod !== null) {
            $this->editPaymentMethod($firstMethod->id);
        }
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    #[Computed]
    public function paymentMethods(): Collection
    {
        return PaymentMethod::orderBy('position')->get();
    }

    /**
     * @return Collection<int, Coupon>
     */
    #[Computed]
    public function coupons(): Collection
    {
        return Coupon::with(['type', 'restrictedVariant'])->orderBy('code')->get();
    }

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

    public function editPaymentMethod(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);

        $this->paymentMethodId = $method->id;
        $this->name = $method->name;
        $this->discount_percentage = (string) $method->discount_percentage;
        $this->max_installments = $method->max_installments;
        $this->position = $method->position;
    }

    public function updatePaymentMethod(): void
    {
        $validated = $this->validate($this->paymentMethodRules(), $this->paymentMethodMessages());

        DB::transaction(function () use ($validated): void {
            PaymentMethod::findOrFail($this->paymentMethodId)->update($validated);
        });

        Flux::toast(variant: 'success', text: __('Forma de pagamento atualizada.'));
    }

    public function togglePaymentMethodStatus(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $method = PaymentMethod::findOrFail($id);
            $method->update(['is_active' => ! $method->is_active]);
        });
    }

    public function resetCouponForm(): void
    {
        $this->couponId = null;
        $this->code = '';
        $this->type_id = null;
        $this->value = '';
        $this->usage_limit = null;
        $this->per_customer_limit = null;
        $this->restricted_variant_id = null;
        $this->starts_at = '';
        $this->ends_at = '';
        $this->is_active = true;
    }

    public function editCoupon(int $id): void
    {
        $coupon = Coupon::findOrFail($id);

        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->type_id = $coupon->type_id;
        $this->value = (string) $coupon->value;
        $this->usage_limit = $coupon->usage_limit;
        $this->per_customer_limit = $coupon->per_customer_limit;
        $this->restricted_variant_id = $coupon->restricted_variant_id;
        $this->starts_at = $coupon->starts_at->format('Y-m-d');
        $this->ends_at = $coupon->ends_at->format('Y-m-d');
        $this->is_active = $coupon->is_active;
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

        $this->resetCouponForm();
    }

    public function updateCoupon(): void
    {
        $validated = $this->validate($this->couponRules(), $this->couponMessages());

        DB::transaction(function () use ($validated): void {
            Coupon::findOrFail($this->couponId)->update($validated);
        });

        Flux::toast(variant: 'success', text: __('Cupom atualizado.'));

        $this->resetCouponForm();
    }

    public function toggleCouponStatus(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $coupon = Coupon::findOrFail($id);
            $coupon->update(['is_active' => ! $coupon->is_active]);
        });
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

    /**
     * @return array<string, array<int, mixed>>
     */
    private function couponRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($this->couponId)],
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
    {{-- Bloco: Formas de pagamento --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Formas de pagamento</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Código</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Nome exibido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Desconto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Máx. parcelas</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ordem</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->paymentMethods as $method)
                        <tr data-payment-method-row="{{ $method->id }}">
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->slug }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ number_format($method->discount_percentage, 2, ',', '.') }}%</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->max_installments }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->position }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" wire:click.stop="editPaymentMethod({{ $method->id }})" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Editar</button>
                                <button type="button" wire:click.stop="togglePaymentMethodStatus({{ $method->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $method->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($paymentMethodId)
            <form wire:submit="updatePaymentMethod" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
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
        @endif
    </section>

    {{-- Bloco: Cupons --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Cupons</h2>
            <button type="button" wire:click="resetCouponForm" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Novo cupom</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Código</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Usos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Limite</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Vigência</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Restrição</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->coupons as $coupon)
                        <tr data-coupon-row="{{ $coupon->id }}">
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->code }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->type->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->type->slug === 'percentage' ? number_format($coupon->value, 2, ',', '.').'%' : Number::currency($coupon->value, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->uses_count }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->usage_limit ?? 'Sem limite' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->starts_at->format('d/m/Y') }} a {{ $coupon->ends_at->format('d/m/Y') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->restrictedVariant?->sku ?? 'Todas as variantes' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" wire:click.stop="editCoupon({{ $coupon->id }})" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Editar</button>
                                <button type="button" wire:click.stop="toggleCouponStatus({{ $coupon->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $coupon->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum cupom encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição de cupom --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Edição de cupom</h2>
        <form wire:submit="{{ $couponId ? 'updateCoupon' : 'createCoupon' }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">{{ $couponId ? 'Salvar alterações' : 'Salvar cupom' }}</button>
            </div>
        </form>
    </section>
</div>
