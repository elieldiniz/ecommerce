@php
    $customers = [
        ['name' => 'Maria Aparecida Souza', 'document' => '123.456.789-00', 'type' => 'Pessoa física', 'email' => 'maria.souza@exemplo.com.br', 'orders' => 2, 'lastPurchase' => '10/08/2026'],
        ['name' => 'Comércio Digital Lock Ltda', 'document' => '12.345.678/0001-90', 'type' => 'Pessoa jurídica', 'email' => 'financeiro@empresaexemplo.com.br', 'orders' => 5, 'lastPurchase' => '09/08/2026'],
        ['name' => 'Roberto Lima Castro', 'document' => '987.654.321-00', 'type' => 'Pessoa física', 'email' => 'roberto.castro@exemplo.com.br', 'orders' => 1, 'lastPurchase' => '08/08/2026'],
    ];
@endphp

<x-admin-layout active-item="clientes" title="Clientes">
    {{-- Bloco: Filtros e lista --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros e lista</h2>
        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todas</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período de cadastro</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todo o período</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Com certificado vencendo</label>
                <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option>Todos</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar</label>
                <input type="text" placeholder="Nome, documento ou e-mail" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Nome ou razão social</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">E-mail</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedidos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Última compra</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['name'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['document'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['type'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['email'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['orders'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer['lastPurchase'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 font-sans text-xs text-muted-light">Mostrando 1 a 25 de 148</div>
    </section>

    {{-- Bloco: Ficha do cliente · dados --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Ficha do cliente · dados</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social / Nome</label>
                <input type="text" value="Maria Aparecida Souza" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                <input type="text" value="Pessoa física" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Documento</label>
                <input type="text" value="123.456.789-00" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                <input type="email" value="maria.souza@exemplo.com.br" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone</label>
                <input type="text" value="(11) 98765-4321" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                <input type="text" value="01311-000" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro</label>
                <input type="text" value="Avenida Paulista" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número</label>
                <input type="text" value="1578" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Complemento</label>
                <input type="text" value="Apto 92" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro</label>
                <input type="text" value="Bela Vista" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município</label>
                <input type="text" value="São Paulo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                <input type="text" value="SP" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Bloco: Histórico de pedidos --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Histórico de pedidos</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pagamento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Emissão</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Validade até</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['number' => '#1042', 'product' => 'e-CPF A1', 'amount' => 'R$ 213,75', 'validity' => '14/03/2027'],
                        ['number' => '#0988', 'product' => 'e-CPF A1', 'amount' => 'R$ 250,00', 'validity' => '02/01/2026'],
                    ] as $order)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['number'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['product'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['amount'] }}</td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status variant="emitido">Pago</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5"><x-badge-status variant="emitido">Emitido</x-badge-status></td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $order['validity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Titulares vinculados --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Titulares vinculados</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Responsável</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Certificado até</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['holder' => 'Maria Aparecida Souza', 'document' => '123.456.789-00', 'type' => 'e-CPF A1', 'responsible' => '—', 'certificateUntil' => '14/03/2027'],
                        ['holder' => 'João Pedro Almeida', 'document' => '987.654.321-00', 'type' => 'e-CNPJ A1', 'responsible' => 'Maria Aparecida Souza', 'certificateUntil' => '20/07/2027'],
                    ] as $holder)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder['holder'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder['document'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder['type'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder['responsible'] }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder['certificateUntil'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
