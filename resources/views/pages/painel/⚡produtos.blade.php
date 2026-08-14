<?php

use App\Models\HolderType;
use App\Models\Product;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'produtos', 'title' => 'Produtos'])] #[Title('Produtos')] class extends Component {
    public ?int $selectedProductId = null;

    public ?int $productId = null;

    public string $name = '';

    public string $slug = '';

    public ?int $holder_type_id = null;

    public ?string $short_description = null;

    public ?int $position = null;

    public function mount(): void
    {
        $this->selectedProductId = null;
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::with(['holderType', 'variants'])->orderBy('position')->get();
    }

    /**
     * @return Collection<int, HolderType>
     */
    #[Computed]
    public function holderTypes(): Collection
    {
        return HolderType::orderBy('name')->get();
    }

    public function startingPriceFor(Product $product): string
    {
        $currentPrices = $product->variants->map(fn (ProductVariant $variant) => $this->currentPriceFor($variant));

        if ($currentPrices->isEmpty()) {
            return '—';
        }

        return Number::currency($currentPrices->min(), in: 'BRL', locale: 'pt_BR');
    }

    private function currentPriceFor(ProductVariant $variant): float
    {
        $now = now();

        $hasActivePromotion = $variant->promotional_price !== null
            && $variant->promotion_starts_at !== null
            && $variant->promotion_ends_at !== null
            && $now->betweenIncluded($variant->promotion_starts_at, $variant->promotion_ends_at);

        return (float) ($hasActivePromotion ? $variant->promotional_price : $variant->price);
    }

    public function createProduct(): void
    {
        $validated = $this->validate($this->productRules(), $this->productMessages());

        DB::transaction(function () use ($validated): void {
            Product::create([...$validated, 'is_active' => true]);
        });

        $this->resetProductForm();

        Flux::toast(variant: 'success', text: __('Produto criado.'));
    }

    public function editProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->holder_type_id = $product->holder_type_id;
        $this->short_description = $product->short_description;
        $this->position = $product->position;
    }

    public function updateProduct(): void
    {
        $validated = $this->validate($this->productRules(), $this->productMessages());

        DB::transaction(function () use ($validated): void {
            Product::findOrFail($this->productId)->update($validated);
        });

        $this->resetProductForm();

        Flux::toast(variant: 'success', text: __('Produto atualizado.'));
    }

    public function resetProductForm(): void
    {
        $this->reset('productId', 'name', 'slug', 'holder_type_id', 'short_description', 'position');
    }

    public function toggleProductStatus(int $productId): void
    {
        DB::transaction(function () use ($productId): void {
            $product = Product::findOrFail($productId);
            $product->update(['is_active' => ! $product->is_active]);
        });
    }

    public function selectProduct(int $productId): void
    {
        $this->selectedProductId = $productId;
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function variants(): Collection
    {
        fwrite(STDERR, "DEBUG variants() selectedProductId=" . var_export($this->selectedProductId, true) . "\n");

        return ProductVariant::where('product_id', $this->selectedProductId)->with('certificateFormat')->get();
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('products', 'slug')->ignore($this->productId)],
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
}; ?>

<div>
    {{-- Bloco: Lista --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Lista</h2>
            <button type="button" wire:click="resetProductForm" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Novo produto</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Slug</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Variantes</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">A partir de</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->products as $product)
                        <tr wire:click="selectProduct({{ $product->id }})" class="cursor-pointer {{ $selectedProductId === $product->id ? 'bg-surface-alt' : '' }}">
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->holderType->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->slug }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->variants->count() }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $this->startingPriceFor($product) }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                <button type="button" wire:click.stop="editProduct({{ $product->id }})" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Editar</button>
                                <button type="button" wire:click.stop="toggleProductStatus({{ $product->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $product->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição · dados do produto --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Edição · dados do produto</h2>
        <form wire:submit="{{ $productId ? 'updateProduct' : 'createProduct' }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">{{ $productId ? 'Salvar alterações' : 'Salvar produto' }}</button>
            </div>
        </form>
    </section>

    {{-- Bloco: Variantes do produto --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Variantes do produto</h2>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Nova variante</button>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->variants as $variant)
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição de variante --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Edição de variante</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">SKU</label>
                <input type="text" value="ECPF-A1-12M" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de certificado</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option selected>A1</option>
                    <option>A3</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Validade em meses</label>
                <input type="text" value="12" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Preço</label>
                <input type="text" value="R$ 250,00" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Preço promocional</label>
                <input type="text" value="R$ 213,75" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Vigência da promoção</label>
                <input type="text" value="até 31/08/2026" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" checked>
                Variante padrão
            </label>
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" checked>
                Ativo
            </label>
        </div>
    </section>
</div>
