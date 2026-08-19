<x-admin-layout active-item="visao-geral" title="Visão geral">
    {{-- Bloco: Indicadores --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Indicadores</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <x-kpi-card label="Faturamento" value="R$ 78.420,00" support="312 pedidos" />
            <x-kpi-card label="Ticket médio" value="R$ 251,35" />
            <x-kpi-card label="Taxa de conversão" value="68%" support="Pedidos pagos / criados" />
            <x-kpi-card label="Aguardando dados" value="24" support="Pagos sem dados de emissão" />
            <x-kpi-card label="Falha de integração" value="6" support="Envio ao GFSIS" />
        </div>
    </section>

    {{-- Bloco: Funil operacional --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Funil operacional</h2>
        <x-funil-operacional :stages="[
            ['name' => 'Pedidos criados', 'quantity' => 460],
            ['name' => 'Pagos', 'quantity' => 380, 'percentage' => '83%'],
            ['name' => 'Dados completos', 'quantity' => 340, 'percentage' => '89%'],
            ['name' => 'Enviados ao GFSIS', 'quantity' => 330, 'percentage' => '97%'],
            ['name' => 'Emitidos', 'quantity' => 312, 'percentage' => '95%'],
        ]" />
        <p class="mt-3 font-sans text-xs text-muted-light">A maior queda revela o gargalo.</p>
    </section>

    {{-- Bloco: Exige ação --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Exige ação</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Fila</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Quantidade</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Mais antigo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['queue' => 'Pagos sem dados de emissão', 'quantity' => 24, 'oldest' => '3 dias'],
                        ['queue' => 'Falha de envio ao GFSIS', 'quantity' => 6, 'oldest' => '1 dia'],
                        ['queue' => 'Conversões não enviadas', 'quantity' => 11, 'oldest' => '2 dias'],
                        ['queue' => 'Reembolsos pendentes', 'quantity' => 3, 'oldest' => '5 dias'],
                        ['queue' => 'Certificados vencendo em 30 dias', 'quantity' => 18, 'oldest' => '—'],
                    ] as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['queue'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['quantity'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['oldest'] }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Abrir</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Vendas por dia --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Vendas por dia</h2>
        <div class="flex h-48 items-center justify-center rounded-lg border border-border-light bg-surface-alt font-sans text-xs text-muted-light">
            Gráfico de barras · vendas por dia
        </div>
    </section>
</x-admin-layout>
