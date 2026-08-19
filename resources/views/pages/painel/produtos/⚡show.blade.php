<?php

use App\Models\CertificateFormat;
use App\Models\HolderType;
use App\Models\Product;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'produtos', 'title' => 'Produtos'])] #[Title('Editar produto')] class extends Component
{
    public int $id;

    public string $name = '';

    public string $slug = '';

    public ?int $holder_type_id = null;

    public ?string $short_description = null;

    public ?int $position = null;

    public ?int $variantId = null;

    public string $sku = '';

    public ?int $certificate_format_id = null;

    public ?int $gfsis_certificado_id = null;

    public ?int $validity_months = null;

    public ?string $price = null;

    public ?string $promotional_price = null;

    public ?string $promotion_starts_at = null;

    public ?string $promotion_ends_at = null;

    public bool $is_default = false;

    public function mount(int $id): void
    {
        $product = Product::find($id);

        if ($product === null) {
            abort(404);
        }

        $this->id = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->holder_type_id = $product->holder_type_id;
        $this->short_description = $this->short_description ?? $product->short_description;
        $this->position = $product->position;
    }

    #[Computed]
    public function product(): ?Product
    {
        return Product::with(['holderType'])->find($this->id);
    }

    /**
     * @return Collection<int, HolderType>
     */
    #[Computed]
    public function holderTypes(): Collection
    {
        return HolderType::orderBy('name')->get();
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function variants(): Collection
    {
        return ProductVariant::where('product_id', $this->id)->with('certificateFormat')->get();
    }

    /**
     * @return Collection<int, CertificateFormat>
     */
    #[Computed]
    public function certificateFormats(): Collection
    {
        return CertificateFormat::orderBy('name')->get();
    }

    public function updateProduct(): void
    {
        $validated = $this->validate($this->productRules(), $this->productMessages());

        DB::transaction(function () use ($validated): void {
            Product::findOrFail($this->id)->update($validated);
        });

        Flux::toast(variant: 'success', text: __('Produto atualizado.'));
    }

    public function createVariant(): void
    {
        $validated = $this->validate($this->variantRules(), $this->variantMessages());

        try {
            DB::transaction(function () use ($validated): void {
                $variant = ProductVariant::create([
                    ...$validated,
                    'product_id' => $this->id,
                    'is_active' => true,
                ]);

                if ($this->is_default) {
                    $this->markVariantAsDefault($variant);
                }
            });
        } catch (QueryException) {
            $this->addError('certificate_format_id', 'Este produto já possui uma variante com este formato.');

            return;
        }

        $this->resetVariantForm();

        Flux::toast(variant: 'success', text: __('Variante criada.'));
    }

    public function editVariant(int $variantId): void
    {
        $variant = ProductVariant::findOrFail($variantId);

        $this->variantId = $variant->id;
        $this->sku = $variant->sku;
        $this->certificate_format_id = $variant->certificate_format_id;
        $this->gfsis_certificado_id = $variant->gfsis_certificado_id;
        $this->validity_months = $variant->validity_months;
        $this->price = (string) $variant->price;
        $this->promotional_price = $variant->promotional_price !== null ? (string) $variant->promotional_price : null;
        $this->promotion_starts_at = $variant->promotion_starts_at?->format('Y-m-d');
        $this->promotion_ends_at = $variant->promotion_ends_at?->format('Y-m-d');
        $this->is_default = $variant->is_default;
    }

    public function updateVariant(): void
    {
        $validated = $this->validate($this->variantRules(), $this->variantMessages());

        try {
            DB::transaction(function () use ($validated): void {
                $variant = ProductVariant::findOrFail($this->variantId);
                $variant->update($validated);

                if ($this->is_default) {
                    $this->markVariantAsDefault($variant);
                }
            });
        } catch (QueryException) {
            $this->addError('certificate_format_id', 'Este produto já possui uma variante com este formato.');

            return;
        }

        $this->resetVariantForm();

        Flux::toast(variant: 'success', text: __('Variante atualizada.'));
    }

    public function resetVariantForm(): void
    {
        $this->reset('variantId', 'sku', 'certificate_format_id', 'gfsis_certificado_id', 'validity_months', 'price', 'promotional_price', 'promotion_starts_at', 'promotion_ends_at', 'is_default');
    }

    public function setDefaultVariant(int $variantId): void
    {
        DB::transaction(function () use ($variantId): void {
            $variant = ProductVariant::findOrFail($variantId);
            $this->markVariantAsDefault($variant);
        });

        Flux::toast(variant: 'success', text: __('Variante definida como padrão.'));
    }

    private function markVariantAsDefault(ProductVariant $variant): void
    {
        ProductVariant::where('product_id', $variant->product_id)->update(['is_default' => false]);
        $variant->update(['is_default' => true]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    private function variantRules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:40', Rule::unique('product_variants', 'sku')->ignore($this->variantId)],
            'certificate_format_id' => [
                'required',
                'integer',
                Rule::unique('product_variants')->where('product_id', $this->id)->ignore($this->variantId),
            ],
            'gfsis_certificado_id' => ['nullable', 'integer', 'min:1'],
            'validity_months' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'promotional_price' => ['nullable', 'numeric', 'min:0'],
            'promotion_starts_at' => ['nullable', 'date', 'required_with:promotional_price'],
            'promotion_ends_at' => ['nullable', 'date', 'required_with:promotional_price'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function variantMessages(): array
    {
        return [
            'sku.required' => 'Informe o SKU da variante.',
            'sku.max' => 'O SKU deve ter no máximo 40 caracteres.',
            'sku.unique' => 'Este SKU já está em uso por outra variante.',
            'certificate_format_id.required' => 'Selecione o formato do certificado.',
            'certificate_format_id.integer' => 'Selecione um formato de certificado válido.',
            'certificate_format_id.unique' => 'Este produto já possui uma variante com este formato.',
            'gfsis_certificado_id.integer' => 'O ID do certificado GFSIS deve ser um número inteiro.',
            'gfsis_certificado_id.min' => 'O ID do certificado GFSIS deve ser maior que zero.',
            'validity_months.required' => 'Informe a validade em meses.',
            'validity_months.integer' => 'A validade deve ser um número inteiro.',
            'validity_months.min' => 'A validade deve ser de pelo menos 1 mês.',
            'price.required' => 'Informe o preço.',
            'price.numeric' => 'O preço deve ser um valor numérico.',
            'promotional_price.numeric' => 'O preço promocional deve ser um valor numérico.',
            'promotion_starts_at.required_with' => 'Informe o início da vigência da promoção.',
            'promotion_starts_at.date' => 'Informe uma data válida para o início da promoção.',
            'promotion_ends_at.required_with' => 'Informe o fim da vigência da promoção.',
            'promotion_ends_at.date' => 'Informe uma data válida para o fim da promoção.',
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('products', 'slug')->ignore($this->id)],
            'holder_type_id' => ['required', 'integer'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function productMessages(): array
    {
        return [
            'name.required' => 'Informe o nome do produto.',
            'name.max' => 'O nome deve ter no máximo 120 caracteres.',
            'slug.required' => 'Informe o slug do produto.',
            'slug.max' => 'O slug deve ter no máximo 120 caracteres.',
            'slug.unique' => 'Este slug já está em uso por outro produto.',
            'holder_type_id.required' => 'Selecione o tipo de titular.',
            'holder_type_id.integer' => 'Selecione um tipo de titular válido.',
            'short_description.max' => 'A descrição curta deve ter no máximo 255 caracteres.',
            'position.required' => 'Informe a ordem de exibição.',
            'position.integer' => 'A ordem de exibição deve ser um número inteiro.',
        ];
    }
}
?>

<div>
    <div class="flex items-center justify-between">
        <h1 class="font-heading text-xl font-bold text-ink">{{ $this->product->name }}</h1>
        <a href="{{ route('painel.produtos') }}" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink hover:bg-surface-alt cursor-pointer">Voltar</a>
    </div>

    {{-- Bloco: Edição · dados do produto --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Dados do produto</h2>
        <form wire:submit="updateProduct" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('name')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Slug</label>
                <input type="text" wire:model="slug" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('slug')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de titular</label>
                <select wire:model="holder_type_id" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Selecione</option>
                    @foreach ($this->holderTypes as $holderType)
                        <option value="{{ $holderType->id }}">{{ $holderType->name }}</option>
                    @endforeach
                </select>
                @error('holder_type_id')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Ordem</label>
                <input type="number" wire:model="position" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('position')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Descrição curta</label>
                <input type="text" wire:model="short_description" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('short_description')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white hover:bg-brand/90 cursor-pointer">Salvar alterações</button>
            </div>
        </form>
    </section>

    {{-- Bloco: Variantes do produto --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Variantes do produto</h2>
            <button type="button" wire:click="resetVariantForm" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt cursor-pointer">Nova variante</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">SKU</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Validade</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Promocional</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Vigência</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Padrão</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->variants as $variant)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->sku }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->certificateFormat->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->validity_months }} meses</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ Number::currency($variant->price, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->promotional_price !== null ? Number::currency($variant->promotional_price, in: 'BRL', locale: 'pt_BR') : '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                @if ($variant->promotion_starts_at && $variant->promotion_ends_at)
                                    {{ $variant->promotion_starts_at->format('d/m/Y') }} a {{ $variant->promotion_ends_at->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->is_default ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                <button type="button" wire:click="editVariant({{ $variant->id }})" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt cursor-pointer">Editar</button>
                                @unless ($variant->is_default)
                                    <button type="button" wire:click="setDefaultVariant({{ $variant->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt cursor-pointer">Marcar como padrão</button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhuma variante encontrada</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição de variante --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">{{ $variantId ? 'Edição de variante' : 'Nova variante' }}</h2>
        <form wire:submit="{{ $variantId ? 'updateVariant' : 'createVariant' }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">SKU</label>
                <input type="text" wire:model="sku" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('sku')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de certificado</label>
                <select wire:model="certificate_format_id" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Selecione</option>
                    @foreach ($this->certificateFormats as $certificateFormat)
                        <option value="{{ $certificateFormat->id }}">{{ $certificateFormat->name }}</option>
                    @endforeach
                </select>
                @error('certificate_format_id')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Validade em meses</label>
                <input type="number" wire:model="validity_months" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('validity_months')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">ID do certificado GFSIS</label>
                <input type="number" wire:model="gfsis_certificado_id" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('gfsis_certificado_id')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Preço</label>
                <input type="text" wire:model="price" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('price')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Preço promocional</label>
                <input type="text" wire:model="promotional_price" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('promotional_price')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Início da vigência da promoção</label>
                <input type="date" wire:model="promotion_starts_at" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('promotion_starts_at')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Fim da vigência da promoção</label>
                <input type="date" wire:model="promotion_ends_at" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('promotion_ends_at')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex items-center gap-2 md:col-span-2">
                <input type="checkbox" wire:model="is_default" id="is_default" class="rounded border-border-light">
                <label for="is_default" class="font-sans text-xs font-semibold text-muted">Variante padrão</label>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white hover:bg-brand/90 cursor-pointer">{{ $variantId ? 'Salvar variante' : 'Salvar variante' }}</button>
            </div>
        </form>
    </section>
</div>
