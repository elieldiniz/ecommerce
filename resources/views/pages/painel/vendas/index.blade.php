@php
    $orders = [
        ['number' => '#1042', 'customer' => 'Maria Aparecida Souza', 'product' => 'e-CPF A1', 'amount' => 'R$ 213,75', 'payment' => 'emitido', 'paymentLabel' => 'Pago', 'issuance' => 'emitido', 'issuanceLabel' => 'Emitido', 'date' => '10/08/2026'],
        ['number' => '#1041', 'customer' => 'João Pedro Almeida', 'product' => 'e-CNPJ A1', 'amount' => 'R$ 250,00', 'payment' => 'emitido', 'paymentLabel' => 'Pago', 'issuance' => 'agendado', 'issuanceLabel' => 'Agendado', 'date' => '09/08/2026'],
        ['number' => '#1040', 'customer' => 'Carla Mendes Ferreira', 'product' => 'e-CPF A3', 'amount' => 'R$ 350,00', 'payment' => 'emitido', 'paymentLabel' => 'Pago', 'issuance' => 'aguardando', 'issuanceLabel' => 'Aguardando dados', 'date' => '08/08/2026'],
        ['number' => '#1039', 'customer' => 'Roberto Lima Castro', 'product' => 'e-CNPJ A3', 'amount' => 'R$ 420,00', 'payment' => 'aguardando', 'paymentLabel' => 'Aguardando', 'issuance' => 'neutro', 'issuanceLabel' => 'Pendente', 'date' => '08/08/2026'],
        ['number' => '#1038', 'customer' => 'Fernanda Costa Ribeiro', 'product' => 'e-CPF A1', 'amount' => 'R$ 213,75', 'payment' => 'erro', 'paymentLabel' => 'Falhou', 'issuance' => 'neutro', 'issuanceLabel' => 'Cancelado', 'date' => '07/08/2026'],
        ['number' => '#1037', 'customer' => 'Paulo Henrique Dias', 'product' => 'MEI A1', 'amount' => 'R$ 190,00', 'payment' => 'emitido', 'paymentLabel' => 'Pago', 'issuance' => 'emitido', 'issuanceLabel' => 'Emitido', 'date' => '06/08/2026'],
    ];
@endphp

<x-admin-layout active-item="vendas" title="Vendas">
    {{-- Bloco: Filtros --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Últimos 30 dias</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status do pagamento</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Status da emissão</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Forma de pagamento</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Produto</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Origem</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar por nome, documento ou número do pedido</label>
                <input type="text" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Bloco: Ações em lote --}}
    <section class="mt-6 flex flex-wrap gap-2">
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Exportar CSV</button>
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Reenviar ao GFSIS</button>
        <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Disparar recuperação</button>
    </section>

    {{-- Tabela de pedidos --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pagamento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Emissão</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">
                                <a href="{{ route('painel.vendas.show', ltrim($order['number'], '#')) }}" class="font-semibold text-brand">{{ $order['number'] }}</a>
                            </td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['customer'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['product'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['amount'] }}</td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$order['payment']">{{ $order['paymentLabel'] }}</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$order['issuance']">{{ $order['issuanceLabel'] }}</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['date'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 font-sans text-xs text-muted-light">Mostrando 1 a 25 de 312</div>
    </section>
</x-admin-layout>
