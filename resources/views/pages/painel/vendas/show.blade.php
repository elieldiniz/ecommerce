<x-admin-layout active-item="vendas" :title="'Pedido #'.$id">
    {{-- Bloco: Cabeçalho --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-heading text-lg font-bold text-ink">Pedido #{{ $id }}</h2>
                <div class="font-sans text-xs text-muted">Criado em 10/08/2026 · 14h22</div>
                <div class="mt-1 font-sans text-sm text-ink">Maria Aparecida Souza</div>
            </div>
            <div class="flex items-center gap-2">
                <x-badge-status variant="emitido">Pago</x-badge-status>
                <x-badge-status variant="emitido">Emitido</x-badge-status>
            </div>
        </div>
    </section>

    {{-- Bloco: Itens --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Itens</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">SKU</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço tabela</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço praticado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-border px-3 py-2.5 text-ink">ECPF-A1-12M</td>
                        <td class="border border-border px-3 py-2.5 text-ink">e-CPF A1 · 12 meses</td>
                        <td class="border border-border px-3 py-2.5 text-ink">Maria Aparecida Souza</td>
                        <td class="border border-border px-3 py-2.5 text-ink">R$ 250,00</td>
                        <td class="border border-border px-3 py-2.5 text-ink">R$ 213,75</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Financeiro --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Valores</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Subtotal</dt><dd class="text-ink">R$ 250,00</dd></div>
                <div class="flex justify-between text-muted"><dt>Desconto cupom</dt><dd class="text-ink">- R$ 25,00</dd></div>
                <div class="flex justify-between text-muted"><dt>Desconto Pix</dt><dd class="text-ink">- R$ 11,25</dd></div>
                <div class="flex justify-between text-muted"><dt>Total</dt><dd class="text-ink">R$ 213,75</dd></div>
                <div class="flex justify-between text-muted"><dt>Taxa do gateway</dt><dd class="text-ink">R$ 2,14</dd></div>
                <div class="flex justify-between text-muted"><dt>Líquido previsto</dt><dd class="text-ink">R$ 211,61</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Pagamento</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Método</dt><dd class="text-ink">Pix</dd></div>
                <div class="flex justify-between text-muted"><dt>Status</dt><dd class="text-ink">Aprovado</dd></div>
                <div class="flex justify-between text-muted"><dt>ID no gateway</dt><dd class="text-ink">SF2P-88213</dd></div>
                <div class="flex justify-between text-muted"><dt>TXID</dt><dd class="text-ink">TX9F82A1</dd></div>
                <div class="flex justify-between text-muted"><dt>End-to-end</dt><dd class="text-ink">E00000000202608101422abcdef1</dd></div>
                <div class="flex justify-between text-muted"><dt>Pago em</dt><dd class="text-ink">10/08/2026 · 14h23</dd></div>
                <div class="flex justify-between text-muted"><dt>Previsão de repasse</dt><dd class="text-ink">12/08/2026</dd></div>
            </dl>
        </div>
    </section>

    {{-- Bloco: Emissão e GFSIS --}}
    <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Dados do titular</h3>
            <dl class="flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>Nome</dt><dd class="text-ink">Maria Aparecida Souza</dd></div>
                <div class="flex justify-between text-muted"><dt>CPF</dt><dd class="text-ink">123.456.789-00</dd></div>
                <div class="flex justify-between text-muted"><dt>Responsável (PJ)</dt><dd class="text-ink">—</dd></div>
                <div class="flex justify-between text-muted"><dt>E-mail</dt><dd class="text-ink">maria.souza@exemplo.com.br</dd></div>
                <div class="flex justify-between text-muted"><dt>Endereço</dt><dd class="text-ink">Av. Paulista, 1578</dd></div>
                <div class="flex justify-between text-muted"><dt>Município/UF</dt><dd class="text-ink">São Paulo/SP</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-border bg-white p-5">
            <h3 class="mb-3 font-heading text-sm font-bold text-ink">Integração</h3>
            <dl class="mb-4 flex flex-col gap-1.5 font-sans text-[13px]">
                <div class="flex justify-between text-muted"><dt>gfsis_order_id</dt><dd class="text-ink">GF-778213</dd></div>
                <div class="flex justify-between text-muted"><dt>Código GFSIS</dt><dd class="text-ink">GFS-88213</dd></div>
                <div class="flex justify-between text-muted"><dt>Status GFSIS</dt><dd class="text-ink">Concluído</dd></div>
                <div class="flex justify-between text-muted"><dt>Agendamento</dt><dd class="text-ink">10/08/2026 · 16h00</dd></div>
                <div class="flex justify-between text-muted"><dt>Validade até</dt><dd class="text-ink">14/03/2027</dd></div>
                <div class="flex justify-between text-muted"><dt>Sincronizado em</dt><dd class="text-ink">10/08/2026 · 17h05</dd></div>
                <div class="flex justify-between text-muted"><dt>Tentativas</dt><dd class="text-ink">1</dd></div>
            </dl>
            <button type="button" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-xs font-semibold text-ink">Reenviar ao GFSIS</button>
        </div>
    </section>

    {{-- Bloco: Origem da venda --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Origem da venda</h2>
        <x-comparison-table
            :columns="['Campo', 'Valor']"
            :rows="[
                ['Campanha', 'Google Ads · Certificado Digital e-CPF'],
                ['Origem e meio', 'google / cpc'],
                ['gclid', 'Cj0KCQjw_gclid_exemplo'],
                ['Página de entrada', '/certificado-digital/e-cpf/'],
                ['Dispositivo', 'Mobile'],
                ['Sessões até a compra', '3'],
                ['Status de conversão enviada', 'Enviada'],
            ]"
        />
    </section>

    {{-- Bloco: Linha do tempo --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Linha do tempo</h2>
        <x-timeline :events="[
            ['date' => '10/08/2026 14:22', 'description' => 'Pedido criado', 'origin' => 'sistema'],
            ['date' => '10/08/2026 14:23', 'description' => 'Pagamento autorizado', 'origin' => 'webhook'],
            ['date' => '10/08/2026 14:30', 'description' => 'Dados de emissão preenchidos', 'origin' => 'cliente'],
            ['date' => '10/08/2026 14:35', 'description' => 'Enviado ao GFSIS', 'origin' => 'fila'],
            ['date' => '10/08/2026 16:00', 'description' => 'Videoconferência realizada', 'origin' => 'sistema'],
            ['date' => '10/08/2026 17:05', 'description' => 'Certificado emitido', 'origin' => 'webhook'],
        ]" />
    </section>
</x-admin-layout>
