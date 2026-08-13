<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <x-checkout-header context="Compra segura" />
    <x-checkout-steps :active-step="2" />

    <main class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-6 py-8 md:grid-cols-3">
        <div class="flex flex-col gap-6 md:col-span-2">
            {{-- Bloco: Seus dados --}}
            <section class="rounded-xl border border-border bg-white p-5">
                <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seus dados</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                        <select class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                            <option>Pessoa física</option>
                            <option selected>Pessoa jurídica</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">CNPJ</label>
                        <input type="text" value="12.345.678/0001-90" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social</label>
                        <input type="text" value="Comércio Digital Lock Ltda" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                        <input type="email" value="contato@empresaexemplo.com.br" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone com DDD</label>
                        <input type="text" value="(11) 91234-5678" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                    <div>
                        <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                        <input type="text" value="01311-000" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    </div>
                </div>

                <label class="mt-4 flex items-center gap-2 font-sans text-xs text-muted">
                    <input type="checkbox" checked>
                    Quero receber novidades e promoções por e-mail
                </label>
            </section>

            {{-- Bloco: Como você prefere pagar --}}
            <section class="rounded-xl border border-border bg-white p-5">
                <h2 class="mb-4 font-heading text-lg font-bold text-ink">Como você prefere pagar</h2>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <button type="button" data-payment-method="pix" class="rounded-lg border-2 border-brand bg-highlight p-4 text-left">
                        <div class="font-heading text-sm font-bold text-ink">Pix</div>
                        <div class="mt-1 font-sans text-xs text-muted">Confirmação imediata · 5% de desconto</div>
                    </button>
                    <button type="button" data-payment-method="cartao" class="rounded-lg border border-border-light bg-white p-4 text-left">
                        <div class="font-heading text-sm font-bold text-ink">Cartão de crédito</div>
                        <div class="mt-1 font-sans text-xs text-muted">Até 12x · Sem desconto</div>
                    </button>
                    <button type="button" data-payment-method="boleto" class="rounded-lg border border-border-light bg-white p-4 text-left">
                        <div class="font-heading text-sm font-bold text-ink">Boleto</div>
                        <div class="mt-1 font-sans text-xs text-muted">Compensa em 1 a 3 dias úteis · Sem desconto</div>
                    </button>
                </div>
            </section>
        </div>

        {{-- Bloco: Seu pedido (resumo lateral) --}}
        <aside class="h-fit rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Seu pedido</h2>

            <div class="mb-4 font-sans text-sm text-ink">Certificado Digital e-CNPJ A1 · 1 ano</div>

            <div class="mb-4 flex gap-2">
                <input type="text" placeholder="Código do cupom" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                <button type="button" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Aplicar</button>
            </div>

            <dl class="flex flex-col gap-2 border-t border-border pt-4 font-sans text-sm">
                <div class="flex justify-between text-muted">
                    <dt>Subtotal</dt>
                    <dd>R$ 250,00</dd>
                </div>
                <div class="flex justify-between text-muted">
                    <dt>Desconto do cupom</dt>
                    <dd>- R$ 25,00</dd>
                </div>
                <div class="flex justify-between text-muted">
                    <dt>Desconto do Pix</dt>
                    <dd>- R$ 11,25</dd>
                </div>
                <div class="flex justify-between border-t border-border pt-2 font-heading font-bold text-ink">
                    <dt>Total</dt>
                    <dd>R$ 213,75</dd>
                </div>
            </dl>

            <button type="button" class="mt-4 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white">Finalizar compra</button>
        </aside>
    </main>
</body>
</html>
