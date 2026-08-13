@php
    $reports = [
        ['key' => 'vendas-por-periodo', 'name' => 'Vendas por período', 'description' => 'Faturamento e pedidos por dia, semana ou mês'],
        ['key' => 'vendas-por-produto', 'name' => 'Vendas por produto', 'description' => 'Comparativo entre e-CPF, e-CNPJ e MEI'],
        ['key' => 'funil-operacional', 'name' => 'Funil operacional', 'description' => 'Conversão em cada etapa do pedido'],
        ['key' => 'pagos-sem-dados', 'name' => 'Pagos sem dados', 'description' => 'Clientes que pagaram e não preencheram dados'],
        ['key' => 'base-de-renovacao', 'name' => 'Base de renovação', 'description' => 'Certificados próximos do vencimento'],
        ['key' => 'atribuicao', 'name' => 'Atribuição', 'description' => 'Origem e campanha de cada venda'],
        ['key' => 'conciliacao-do-gateway', 'name' => 'Conciliação do gateway', 'description' => 'Comparativo entre pedidos e repasses'],
        ['key' => 'estornos', 'name' => 'Estornos', 'description' => 'Pedidos estornados no período'],
        ['key' => 'cupons', 'name' => 'Cupons', 'description' => 'Uso e desempenho de cada cupom'],
    ];
@endphp

<x-admin-layout active-item="relatorios" title="Relatórios">
    {{-- Bloco: Seleção --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seleção</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            @foreach ($reports as $report)
                <button
                    type="button"
                    data-report="{{ $report['key'] }}"
                    @class([
                        'rounded-xl p-4 text-left',
                        'border-2 border-brand bg-highlight' => $report['key'] === 'vendas-por-periodo',
                        'border border-border-light bg-white' => $report['key'] !== 'vendas-por-periodo',
                    ])
                >
                    <div class="font-heading text-sm font-bold text-ink">{{ $report['name'] }}</div>
                    <div class="mt-1 font-sans text-xs text-muted">{{ $report['description'] }}</div>
                </button>
            @endforeach
        </div>
    </section>

    {{-- Exemplo: Vendas por período --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por período</h2>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Últimos 30 dias</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Produto</label>
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
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Origem</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar</label>
                <input type="text" placeholder="Buscar..." class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-kpi-card label="Faturamento" value="R$ 78.420,00" />
            <x-kpi-card label="Pedidos" value="312" />
            <x-kpi-card label="Ticket médio" value="R$ 251,35" />
            <x-kpi-card label="Descontos" value="R$ 6.230,00" />
        </div>

        <div class="mb-4 flex h-48 items-center justify-center rounded-lg border border-border-light bg-surface-alt font-sans text-xs text-muted-light">
            Gráfico de linha · faturamento diário
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dia</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedidos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Faturamento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ticket médio</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Desconto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['day' => '10/08/2026', 'orders' => 18, 'revenue' => 'R$ 4.520,00', 'averageTicket' => 'R$ 251,11', 'discount' => 'R$ 360,00'],
                        ['day' => '09/08/2026', 'orders' => 15, 'revenue' => 'R$ 3.780,00', 'averageTicket' => 'R$ 252,00', 'discount' => 'R$ 300,00'],
                        ['day' => '08/08/2026', 'orders' => 21, 'revenue' => 'R$ 5.290,00', 'averageTicket' => 'R$ 251,90', 'discount' => 'R$ 420,00'],
                    ] as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['day'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['orders'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['revenue'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['averageTicket'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['discount'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar CSV</button>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Exportar PDF</button>
        </div>
    </section>

    {{-- Exemplo: Base de renovação --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Base de renovação</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Vence em</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dias</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Contato</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['holder' => 'Ana Beatriz Ramos', 'document' => '111.222.333-44', 'product' => 'e-CPF A1', 'dueDate' => '02/09/2026', 'days' => 21],
                        ['holder' => 'Carlos Eduardo Nogueira', 'document' => '555.666.777-88', 'product' => 'e-CPF A3', 'dueDate' => '10/09/2026', 'days' => 29],
                        ['holder' => 'Patrícia Gomes Vieira', 'document' => '999.888.777-66', 'product' => 'e-CNPJ A1', 'dueDate' => '15/09/2026', 'days' => 34],
                    ] as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['holder'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['document'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['product'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['dueDate'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['days'] }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">WhatsApp</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
