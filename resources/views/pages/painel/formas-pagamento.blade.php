@php
    $coupons = [
        ['code' => 'BEMVINDO10', 'type' => 'Percentual', 'amount' => '10%', 'uses' => 128, 'limit' => 500, 'validUntil' => 'até 31/08/2026', 'restriction' => 'Todas as variantes', 'active' => 'Sim'],
        ['code' => 'ECNPJ25', 'type' => 'Percentual', 'amount' => '25%', 'uses' => 42, 'limit' => 100, 'validUntil' => 'até 20/08/2026', 'restriction' => 'e-CNPJ A1', 'active' => 'Sim'],
        ['code' => 'PARCEIRO50', 'type' => 'Valor fixo', 'amount' => 'R$ 50,00', 'uses' => 9, 'limit' => 50, 'validUntil' => 'até 15/09/2026', 'restriction' => 'Todas as variantes', 'active' => 'Não'],
    ];
@endphp

<x-admin-layout active-item="formas-pagamento" title="Formas de pagamento">
    {{-- Bloco: Formas de pagamento --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Formas de pagamento</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Código</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Nome exibido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Desconto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Máx. parcelas</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ordem</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['code' => 'pix', 'name' => 'Pix', 'discount' => '5%', 'installments' => 1, 'order' => 1, 'active' => 'Sim'],
                        ['code' => 'cartao', 'name' => 'Cartão de crédito', 'discount' => '0%', 'installments' => 12, 'order' => 2, 'active' => 'Sim'],
                        ['code' => 'boleto', 'name' => 'Boleto', 'discount' => '0%', 'installments' => 1, 'order' => 3, 'active' => 'Sim'],
                    ] as $method)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['code'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['name'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['discount'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['installments'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['order'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method['active'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Cupons --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Cupons</h2>
            <button type="button" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Novo cupom</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse font-sans text-[13px]">
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coupons as $coupon)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['code'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['type'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['amount'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['uses'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['limit'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['validUntil'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['restriction'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon['active'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Edição de cupom --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Edição de cupom</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Código</label>
                <input type="text" value="BEMVINDO10" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option selected>Percentual</option>
                    <option>Valor fixo</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Valor</label>
                <input type="text" value="10%" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Limite de usos</label>
                <input type="text" value="500" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Limite por cliente</label>
                <input type="text" value="1" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Restrito à variante</label>
                <input type="text" value="Todas as variantes" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Válido de</label>
                <input type="text" value="01/08/2026" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Válido até</label>
                <input type="text" value="31/08/2026" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <label class="flex items-center gap-2 font-sans text-xs text-muted">
                <input type="checkbox" checked>
                Ativo
            </label>
        </div>
    </section>
</x-admin-layout>
