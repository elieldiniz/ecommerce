<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'produtos', 'title' => 'Produtos'])] #[Title('Produtos')] class extends Component
{
    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::with(['holderType', 'variants'])->orderBy('position')->get();
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

    public function toggleProductStatus(int $productId): void
    {
        DB::transaction(function () use ($productId): void {
            $product = Product::findOrFail($productId);
            $product->update(['is_active' => ! $product->is_active]);
        });
    }
}
?>

<div>
    {{-- Bloco: Lista --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Lista</h2>
            <a href="{{ route('painel.produtos.create') }}" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white hover:bg-brand/90 cursor-pointer">Novo produto</a>
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
                        <tr class="hover:bg-surface-alt">
                            <td class="border border-border px-3 py-2.5 text-ink"><a href="{{ route('painel.produtos.show', $product->id) }}" class="font-semibold text-brand cursor-pointer">{{ $product->name }}</a></td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->holderType->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->slug }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->variants->count() }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $this->startingPriceFor($product) }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                <a href="{{ route('painel.produtos.show', $product->id) }}" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt cursor-pointer">Editar</a>
                                <button type="button" wire:click="toggleProductStatus({{ $product->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink hover:bg-surface-alt cursor-pointer">{{ $product->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
