<x-layout title="Certificado Digital para MEI | e-CNPJ com Emissão Online | Digital Lock">
    <x-slot:meta_description>O Certificado Digital que o MEI precisa para emitir nota fiscal pelo CNPJ e resolver obrigações pela internet. Emissão 100% online.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Certificado para MEI'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 md:grid-cols-[1.15fr_.85fr] md:items-start">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Certificado Digital para MEI</h1>
                <p class="mb-4 font-sans text-base leading-relaxed text-muted">O certificado que você precisa para emitir nota fiscal pelo seu CNPJ e resolver as obrigações do MEI pela internet.</p>
                <p class="font-sans text-xs text-muted-light">Padrão ICP-Brasil · Emissão por videoconferência · Sem taxa extra</p>
            </div>
            <x-purchase-panel
                :show-selector="false"
                preco-a1="R$ 189,90"
                preco-a1-pix="R$ 169,90"
                cta-texto="Comprar agora"
                cta-href="#"
            />
        </div>
    </section>

    {{-- O MEI compra o e-CNPJ --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-1 font-heading text-2xl font-bold text-ink">O MEI compra o e-CNPJ</h2>
            <p class="mb-4.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">O Certificado Digital do MEI é o e-CNPJ, o mesmo usado por qualquer empresa. Não existe versão específica de MEI e não existe preço diferente por ser microempreendedor.</p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-card-produto
                    :featured="true"
                    titulo="A1 · recomendado"
                    descricao="Arquivo no computador. Não exige token nem leitora, e pode ser enviado ao seu contador."
                    preco="R$ 189,90"
                    cta-texto="Comprar A1"
                    cta-href="#"
                />
                <x-card-produto
                    titulo="A3"
                    descricao="Token USB. Vale mais tempo, mas exige comprar o equipamento."
                    preco="R$ 279,90"
                    cta-texto="Comprar A3"
                    cta-href="#"
                />
            </div>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você emite sem fechar o seu dia de trabalho</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Passo a passo --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Do pagamento ao certificado em uso</h2>
            <x-passo-a-passo card="white" />
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>
</x-layout>
