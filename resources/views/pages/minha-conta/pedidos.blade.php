<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus pedidos | Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <x-checkout-header context="Minha conta" />

    <main class="mx-auto max-w-4xl px-6 py-8">
        <h1 class="mb-5 font-heading text-2xl font-bold text-ink">Meus pedidos</h1>

        {{-- 3 cartões de pedido de exemplo, um por estado --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-border bg-white p-4.5">
                <x-badge-status variant="emitido">Emitido</x-badge-status>
                <div class="mt-3 font-sans text-sm font-semibold text-ink">Maria Aparecida Souza</div>
                <dl class="mt-2 flex flex-col gap-1 font-sans text-xs text-muted">
                    <div class="flex justify-between"><dt>Validade até</dt><dd>14/03/2027</dd></div>
                    <div class="flex justify-between"><dt>Pago em</dt><dd>10/08/2026</dd></div>
                </dl>
                <div class="mt-3 flex flex-col gap-2">
                    <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Ver nota fiscal</button>
                    <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Baixar certificado</button>
                    <button type="button" class="rounded-lg bg-cta-secondary px-3 py-2 font-sans text-xs font-semibold text-white">Renovar</button>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-white p-4.5">
                <x-badge-status variant="agendado">Agendado</x-badge-status>
                <div class="mt-3 font-sans text-sm font-semibold text-ink">João Pedro Almeida</div>
                <dl class="mt-2 flex flex-col gap-1 font-sans text-xs text-muted">
                    <div class="flex justify-between"><dt>Videoconferência</dt><dd>13/08/2026 · 15h30</dd></div>
                    <div class="flex justify-between"><dt>Pago em</dt><dd>09/08/2026</dd></div>
                </dl>
                <div class="mt-3 flex flex-col gap-2">
                    <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Ver o que levar</button>
                    <button type="button" class="rounded-lg border border-border-light px-3 py-2 font-sans text-xs font-semibold text-ink">Reagendar</button>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-white p-4.5">
                <x-badge-status variant="aguardando">Faltam seus dados</x-badge-status>
                <dl class="mt-3 flex flex-col gap-1 font-sans text-xs text-muted">
                    <div class="flex justify-between"><dt>Pago em</dt><dd>11/08/2026</dd></div>
                    <div class="flex justify-between"><dt>Situação</dt><dd>Aguardando dados de emissão</dd></div>
                </dl>
                <div class="mt-3">
                    <button type="button" class="w-full rounded-lg bg-brand px-3 py-2 font-sans text-xs font-semibold text-white">Preencher agora</button>
                </div>
            </div>
        </div>

        {{-- Tabela: Estados possíveis --}}
        <section class="mt-8">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Estados possíveis</h2>
            <x-comparison-table
                :columns="['Situação', 'Origem']"
                :rows="[
                    ['Faltam seus dados', 'order_item_gfsis.status'],
                    ['Em processamento', 'order_item_gfsis.status'],
                    ['Agendado', 'order_item_gfsis.scheduled_at'],
                    ['Emitido', 'order_item_gfsis.issued_at'],
                    ['Vencendo', 'issuance_data.validity_until'],
                ]"
            />
        </section>
    </main>
</body>
</html>
