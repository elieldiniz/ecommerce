<?php

use App\Models\HolderType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'produtos', 'title' => 'Produtos'])] #[Title('Novo produto')] class extends Component
{
    public string $name = '';

    public string $slug = '';

    public ?int $holder_type_id = null;

    public ?string $short_description = null;

    public ?int $position = null;

    /**
     * @return Collection<int, HolderType>
     */
    #[Computed]
    public function holderTypes(): Collection
    {
        return HolderType::orderBy('name')->get();
    }

    public function createProduct(): void
    {
        $validated = $this->validate($this->productRules(), $this->productMessages());

        DB::transaction(function () use ($validated): void {
            Product::create([...$validated, 'is_active' => true]);
        });

        Flux::toast(variant: 'success', text: __('Produto criado.'));

        $this->redirectRoute('painel.produtos');
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    private function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('products', 'slug')],
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
        <h1 class="font-heading text-xl font-bold text-ink">Novo produto</h1>
        <a href="{{ route('painel.produtos') }}" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink hover:bg-surface-alt cursor-pointer">Voltar</a>
    </div>

    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Dados do produto</h2>
        <form wire:submit="createProduct" class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                <button type="submit" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white hover:bg-brand/90 cursor-pointer">Salvar produto</button>
            </div>
        </form>
    </section>
</div>
