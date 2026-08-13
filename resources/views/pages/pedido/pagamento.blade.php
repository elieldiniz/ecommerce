<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aguardando pagamento | Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <x-checkout-header context="Compra segura" />
    <x-checkout-steps :active-step="2" />

    <main class="mx-auto max-w-xl px-6 py-8">
        {{-- Bloco: Escaneie para pagar --}}
        <section class="rounded-xl border border-border bg-white p-6 text-center">
            <h1 class="mb-4 font-heading text-lg font-bold text-ink">Escaneie para pagar</h1>

            <div class="mx-auto mb-4 flex size-44 items-center justify-center rounded-lg border border-border-light bg-surface-alt font-sans text-xs text-muted-light">
                QR Code Pix
            </div>

            <div class="mb-4 font-sans text-sm text-ink">Pedido #{{ $id }} · R$ 213,75</div>

            <div class="mb-2 flex items-center gap-2">
                <input type="text" readonly value="00020126580014br.gov.bcb.pix0136a629...5204000053039865802BR5913DIGITAL LOCK" class="w-full truncate rounded-lg border border-border-light px-3 py-2.5 font-sans text-xs text-muted">
                <button type="button" class="flex-none rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Copiar código</button>
            </div>

            <div class="mb-3 font-sans text-xs font-semibold text-cta-secondary">Expira em 29:47</div>

            <p class="font-sans text-xs text-muted-light">Aguardando confirmação. Esta tela avança sozinha assim que o pagamento cair.</p>
        </section>

        {{-- Bloco: Variação boleto --}}
        <section class="mt-6 rounded-xl border border-border bg-white p-6">
            <h2 class="mb-2 font-heading text-sm font-bold text-ink">Prefere pagar com boleto?</h2>
            <p class="mb-4 font-sans text-xs leading-relaxed text-muted">O QR Code acima é substituído pela linha digitável do boleto. Baixe o boleto e pague em qualquer banco ou casa lotérica. A compensação ocorre em 1 a 3 dias úteis.</p>
            <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-xs font-semibold text-ink">Baixar boleto</button>
        </section>
    </main>
</body>
</html>
