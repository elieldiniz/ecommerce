<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dados de emissão | Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <x-checkout-header context="Compra segura" />
    <x-checkout-steps :active-step="3" />

    <main class="mx-auto max-w-3xl px-6 py-8">
        {{-- Bloco 1: Confirmação --}}
        <section id="bloco-confirmacao" class="rounded-xl border border-border bg-white p-6 text-center">
            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-[#e4f0e8]">
                <flux:icon.check class="size-6 text-[#1e5c34]" />
            </div>
            <h1 class="mb-1 font-heading text-lg font-bold text-ink">Pagamento confirmado</h1>
            <p class="font-sans text-sm text-muted">Pedido #{{ $id }} · R$ 213,75 no Pix</p>
        </section>

        {{-- Bloco 2: dados de emissão (PF ou PJ, condicional) --}}
        <section class="mt-6">
            @if ($holderType === 'pj')
                @include('pages.pedido.partials.emissao-pj')
            @else
                @include('pages.pedido.partials.emissao-pf')
            @endif
        </section>

        {{-- Bloco 3: O que acontece agora --}}
        <section id="bloco-o-que-acontece-agora" class="mt-6 rounded-xl border border-border bg-white p-6">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">O que acontece agora</h2>
            <x-passo-a-passo
                card="surface-alt"
                :steps="[
                    ['title' => 'Recebe o e-mail', 'description' => 'Com o link de agendamento'],
                    ['title' => 'Agenda', 'description' => 'Escolhe o melhor horário'],
                    ['title' => 'Valida ao vivo', 'description' => 'Por videoconferência'],
                    ['title' => 'Baixa e instala', 'description' => 'Com suporte incluído'],
                ]"
            />
        </section>
    </main>
</body>
</html>
