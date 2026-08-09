<x-layout title="Certificado Digital e-CNPJ A1 e A3 | Emissão Online | Digital Lock">
    <x-slot:meta_description>Emita seu e-CNPJ nos formatos A1 ou A3, 100% online por videoconferência. Padrão ICP-Brasil, sem taxa extra pela validação.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Certificado Digital', 'href' => '/certificado-digital/'],
        ['label' => 'e-CNPJ'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 md:grid-cols-[1.15fr_.85fr] md:items-start">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Certificado Digital e-CNPJ</h1>
                <p class="mb-4 font-sans text-base leading-relaxed text-muted">Para sua empresa emitir nota fiscal, acessar o e-CAC e assinar documentos com validade jurídica.</p>
                <p class="font-sans text-xs text-muted-light">Padrão ICP-Brasil · Emissão por videoconferência · Sem taxa extra</p>
            </div>
            <x-purchase-panel
                :show-selector="true"
                preco-a1="R$ 249,90"
                preco-a1-pix="R$ 229,90"
                preco-a3="R$ 349,90"
                preco-a3-pix="R$ 329,90"
                cta-texto="Comprar agora"
                cta-href="#"
            />
        </div>
    </section>

    {{-- O que sua empresa faz com o e-CNPJ --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que sua empresa faz com o e-CNPJ</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Emitir nota fiscal eletrônica: NF-e, NFS-e e NFC-e</div>
                <div>Acessar o e-CAC da Receita Federal</div>
                <div>Enviar eSocial, EFD-Reinf e demais obrigações</div>
                <div>Assinar contratos com validade jurídica</div>
                <div>Usar Conectividade Social e FGTS Digital</div>
                <div>Participar de licitações e pregões</div>
                <div>Integrar com o seu sistema de gestão</div>
            </div>
        </div>
    </section>

    {{-- Qual dos dois a sua empresa precisa --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Qual dos dois a sua empresa precisa</h2>
            <x-comparison-table
                :columns="['Critério', 'A1', 'A3']"
                :rows="[
                    ['Onde fica', 'Arquivo instalado no computador', 'Token USB ou cartão com leitora'],
                    ['Validade', '1 ano', '1, 2 ou 3 anos'],
                    ['Uso em vários computadores', 'Sim', 'Só onde o token estiver conectado'],
                    ['Precisa de equipamento', 'Não', 'Sim, token ou cartão e leitora'],
                    ['Software de nota fiscal', 'Formato recomendado', 'Pode ser excluído pelo software'],
                ]"
            />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Na dúvida, o A1 atende a maior parte das empresas e não exige comprar equipamento.</p>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você emite sem sair da empresa</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Passo a passo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Do pagamento ao certificado em uso</h2>
            <x-passo-a-passo card="surface-alt" />
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>
</x-layout>
