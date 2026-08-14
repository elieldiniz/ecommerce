<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'produtos', 'title' => 'Produtos'])] #[Title('Produtos')] class extends Component {
    public ?int $selectedProductId = null;

    public function mount(): void
    {
        $this->selectedProductId = null;
    }
}; ?>

@php
    $products = [
        ['name' => 'e-CPF', 'type' => 'Pessoa física', 'slug' => 'certificado-digital/e-cpf', 'variants' => 2, 'startingAt' => 'R$ 213,75', 'active' => 'Sim'],
        ['name' => 'e-CNPJ', 'type' => 'Pessoa jurídica', 'slug' => 'certificado-digital/e-cnpj', 'variants' => 2, 'startingAt' => 'R$ 250,00', 'active' => 'Sim'],
        ['name' => 'Certificado Digital para MEI', 'type' => 'Pessoa jurídica', 'slug' => 'certificado-digital-para-mei', 'variants' => 1, 'startingAt' => 'R$ 190,00', 'active' => 'Sim'],
    ];

    $variants = [
        ['sku' => 'ECPF-A1-12M', 'type' => 'A1', 'validity' => '12 meses', 'price' => 'R$ 250,00', 'promotionalPrice' => 'R$ 213,75', 'validUntil' => 'até 31/08/2026', 'default' => 'Sim', 'active' => 'Sim'],
        ['sku' => 'ECPF-A3-36M', 'type' => 'A3', 'validity' => '36 meses', 'price' => 'R$ 350,00', 'promotionalPrice' => '—', 'validUntil' => '—', 'default' => 'Não', 'active' => 'Sim'],
        ['sku' => 'ECPF-A1-24M', 'type' => 'A1', 'validity' => '24 meses', 'price' => 'R$ 300,00', 'promotionalPrice' => '—', 'validUntil' => '—', 'default' => 'Não', 'active' => 'Sim'],
    ];
@endphp

<div>
    {{-- Bloco: Lista --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Lista</h2>
            <button type="button" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Novo produto</button>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['name'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['type'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['slug'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['variants'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['startingAt'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $product['active'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição · dados do produto --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Edição · dados do produto</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome</label>
                <input type="text" value="e-CPF" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Slug</label>
                <input type="text" value="certificado-digital/e-cpf" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de titular</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option selected>Pessoa física</option>
                    <option>Pessoa jurídica</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Ordem</label>
                <input type="text" value="1" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Descrição curta</label>
                <input type="text" value="Certificado Digital e-CPF para pessoa física" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" checked>
                Ativo
            </label>
        </div>
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
                    @foreach ($variants as $variant)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['sku'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['type'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['validity'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['price'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['promotionalPrice'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['validUntil'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['default'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $variant['active'] }}</td>
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
