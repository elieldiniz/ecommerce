@php
    $ecpf = \App\Models\Product::where('slug', 'e-cpf')->first();
    $variantA1 = $ecpf?->variants()->where('sku', 'ECPF-A1-12')->first();
    $variantA3 = $ecpf?->variants()->where('sku', 'ECPF-A3-12')->first();
@endphp

<x-layout title="Certificado Digital e-CPF A1 e A3 | Emissão Online | Digital Lock">
    <x-slot:meta_description>Emita seu e-CPF nos formatos A1 ou A3, 100% online por videoconferência. Padrão ICP-Brasil, sem taxa extra pela validação.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Certificado Digital', 'href' => '/certificado-digital/'],
        ['label' => 'e-CPF'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 md:grid-cols-[1.15fr_.85fr] md:items-start">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Certificado Digital e-CPF</h1>
                <p class="mb-4 font-sans text-base leading-relaxed text-muted">Para assinar documentos, declarar o Imposto de Renda e acessar os serviços do governo com validade jurídica.</p>
                <p class="font-sans text-xs text-muted-light">Padrão ICP-Brasil · Emissão por videoconferência · Sem taxa extra</p>
            </div>
            <x-purchase-panel
                :show-selector="true"
                preco-a1="R$ 139,90"
                preco-a1-pix="R$ 129,90"
                preco-a3="R$ 219,90"
                preco-a3-pix="R$ 199,90"
                :variant-id-a1="$variantA1?->id"
                :variant-id-a3="$variantA3?->id"
                cta-texto="Comprar agora"
            />
        </div>
    </section>

    {{-- O que você faz com o e-CPF --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que você faz com o e-CPF</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Assinar contratos, procurações e documentos</div>
                <div>Entregar a declaração do Imposto de Renda</div>
                <div>Acessar o e-CAC da Receita Federal</div>
                <div>Entrar no gov.br com conta nível ouro</div>
                <div>Acessar o INSS e o Meu INSS</div>
                <div>Assinar processos no Judiciário</div>
                <div>Ser procurador de pessoa ou empresa</div>
            </div>
        </div>
    </section>

    {{-- Qual dos dois você precisa --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Qual dos dois você precisa</h2>
            <x-comparison-table
                :columns="['Critério', 'A1', 'A3']"
                :rows="[
                    ['Onde fica', 'Arquivo instalado no computador', 'Token USB ou cartão com leitora'],
                    ['Validade', '1 ano', '1, 2 ou 3 anos'],
                    ['Uso em vários computadores', 'Sim', 'Só onde o token estiver conectado'],
                    ['Precisa de equipamento', 'Não', 'Sim, token ou cartão e leitora'],
                    ['Sistema operacional', 'Windows, Mac OS e Linux', 'Windows e Linux. Restrição no Mac'],
                ]"
            />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Na dúvida, o A1 atende a maior parte das pessoas e não exige comprar equipamento.</p>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você emite sem sair de casa</h2>
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

    {{-- Documentos: identidade e CPF --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que ter em mãos na videoconferência</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Documento de identidade oficial com foto</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Registro de identidade, passaporte, título de eleitor com foto ou CNH. Estrangeiro domiciliado no Brasil apresenta a CNE; não domiciliado apresenta o passaporte. Original físico ou digital.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">CPF</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Apenas se ele não constar no documento de identidade.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Biometria já cadastrada na base ICP-Brasil pode dispensar a apresentação dos documentos.</p>
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>

    {{-- FAQ: categorias 1, 3 e 5, priorizando contexto de pessoa física --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'O e-CPF é a mesma coisa que a conta gov.br?',
                    'resposta' => 'Não. O e-CPF é um Certificado Digital, emitido por uma Autoridade de Registro credenciada no ICP-Brasil, usado para assinar documentos com validade jurídica. A conta gov.br é um login do governo federal; ter o e-CPF instalado é uma das formas de alcançar o nível ouro nessa conta, mas são coisas diferentes.',
                    'ancora' => 'ecpf-e-conta-gov-br',
                ],
                [
                    'pergunta' => 'Posso autorizar outra pessoa a fazer a Validação por videoconferência no meu lugar?',
                    'resposta' => 'Não. A Validação por videoconferência é feita sempre pelo próprio titular do e-CPF, com apresentação dos documentos originais. Depois de emitido, o e-CPF pode ser usado para representar outra pessoa ou empresa como procurador, mas a validação de identidade nunca pode ser feita por terceiro.',
                    'ancora' => 'validacao-por-terceiro',
                ],
                [
                    'pergunta' => 'Serve para assinar documento em PDF?',
                    'resposta' => 'Sim. O e-CPF assina PDF, Word e outros formatos digitais com validade jurídica equivalente à assinatura manuscrita, reconhecida por órgãos públicos e empresas privadas.',
                    'ancora' => 'assinar-documento-em-pdf',
                ],
                [
                    'pergunta' => 'Tenho empresa. Preciso do e-CPF ou do e-CNPJ?',
                    'resposta' => 'Depende do uso. O e-CPF assina em seu nome como pessoa física, inclusive como responsável legal ou procurador de uma empresa. O e-CNPJ assina em nome da própria empresa, como na emissão de nota fiscal. Muitos sócios e responsáveis legais têm o e-CPF e o e-CNPJ, conforme a necessidade.',
                    'ancora' => 'ecpf-ou-ecnpj-com-empresa',
                ],
            ]" />
        </div>
    </section>

    {{-- Fechamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 font-heading text-xl font-bold text-ink">Compre agora e agende sua videoconferência hoje</h2>
                <p class="font-sans text-[13px] text-muted">R$ 139,90 ou R$ 129,90 no Pix</p>
            </div>
            <a href="{{ $variantA1 ? route('cart.add', $variantA1->id) : '#' }}" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CPF</a>
        </div>
    </section>
</x-layout>
