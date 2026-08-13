@php
    $queue = [
        ['number' => '#1039', 'customer' => 'Roberto Lima Castro', 'amount' => 'R$ 420,00', 'days' => 5, 'contacts' => 2, 'action' => 'Ligar'],
        ['number' => '#1044', 'customer' => 'Camila Rocha Nunes', 'amount' => 'R$ 250,00', 'days' => 4, 'contacts' => 1, 'action' => 'Reenviar link'],
        ['number' => '#1046', 'customer' => 'Diego Santos Pereira', 'amount' => 'R$ 190,00', 'days' => 3, 'contacts' => 1, 'action' => 'Reenviar link'],
        ['number' => '#1048', 'customer' => 'Beatriz Alves Martins', 'amount' => 'R$ 350,00', 'days' => 1, 'contacts' => 0, 'action' => 'Ligar'],
    ];
@endphp

<x-admin-layout active-item="recuperacao" title="Fila de recuperação">
    {{-- Bloco: Indicadores --}}
    <section>
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Indicadores</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-kpi-card label="Pagos sem dados" value="24" />
            <x-kpi-card label="Recuperados em 7 dias" value="9" support="37% de recuperação" />
            <x-kpi-card label="Mais antigo" value="5 dias" />
            <x-kpi-card label="Falha de envio" value="3" />
        </div>
    </section>

    {{-- Bloco: Fila ordenada por tempo --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Fila ordenada por tempo</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Cliente</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Dias</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Contatos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($queue as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['number'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['customer'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['amount'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['days'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['contacts'] }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $row['action'] }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Régua automática --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Régua automática</h2>
        <x-comparison-table
            :columns="['Momento', 'Canal', 'Mensagem']"
            :rows="[
                ['Imediato', 'E-mail', 'Lembrete de pagamento pendente'],
                ['2 horas', 'WhatsApp', 'Seu pedido ainda está aguardando pagamento'],
                ['24 horas', 'E-mail', 'Finalize sua compra e garanta seu Certificado Digital'],
                ['3 dias', 'WhatsApp', 'Última chance com desconto especial'],
                ['5 dias', 'Painel', 'Pedido marcado para contato manual'],
            ]"
        />
    </section>

    {{-- Bloco: Falhas de integração --}}
    <section class="mt-8 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Falhas de integração</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Erro</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tentativas</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['number' => '#1038', 'error' => 'Timeout ao enviar dados ao GFSIS', 'attempts' => 3],
                        ['number' => '#1035', 'error' => 'CPF do titular rejeitado pelo GFSIS', 'attempts' => 2],
                    ] as $row)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['number'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['error'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $row['attempts'] }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <button type="button" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Corrigir e reenviar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
