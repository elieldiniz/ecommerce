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
                preco-a1="R$ 249,90"
                preco-a1-pix="R$ 229,90"
                cta-texto="Comprar agora"
                cta-href="/certificado-digital/e-cnpj/"
            />
        </div>
    </section>

    {{-- Certificado Digital não é o mesmo que CCMEI --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Exclusivo desta página</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Certificado Digital não é o mesmo que CCMEI</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">CCMEI</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Comprova que você é MEI. Sai de graça no Portal do Empreendedor.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Certificado Digital</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">É a sua identidade eletrônica. Permite emitir nota fiscal e assinar pelo CNPJ.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">São documentos diferentes e você provavelmente vai precisar dos dois.</p>
        </div>
    </section>

    {{-- O que o MEI faz com o certificado digital --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que o MEI faz com o Certificado Digital</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Emitir nota fiscal para empresas e governo</div>
                <div>Entrar no e-CAC da Receita Federal</div>
                <div>Consultar e resolver pendências do CNPJ</div>
                <div>Assinar documentos em nome da empresa</div>
                <div>Dar acesso ao contador sem entregar senhas</div>
                <div>Participar de licitações</div>
            </div>
        </div>
    </section>

    {{-- Você precisa de certificado se --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Exclusivo desta página</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Você precisa de certificado se:</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Vende para empresas ou para o governo</div>
                <div>Emite NFS-e pela prefeitura ou pelo sistema nacional</div>
                <div>Quer emitir nota sem depender de terceiros</div>
                <div>Precisa acessar o e-CAC</div>
                <div>Seu contador pediu o certificado</div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Se você só emite nota para pessoa física de vez em quando, talvez ainda não precise. Fale com a gente antes.</p>
        </div>
    </section>

    {{-- O MEI compra o e-CNPJ --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-1 font-heading text-2xl font-bold text-ink">O MEI compra o e-CNPJ</h2>
            <p class="mb-4.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">O Certificado Digital do MEI é o e-CNPJ, o mesmo usado por qualquer empresa. Não existe versão específica de MEI e não existe preço diferente por ser microempreendedor.</p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-card-produto
                    :featured="true"
                    titulo="A1 · recomendado"
                    descricao="Arquivo no computador. Não exige token nem leitora, e pode ser enviado ao seu contador."
                    preco="R$ 249,90"
                    cta-texto="Comprar A1"
                    cta-href="/certificado-digital/e-cnpj/"
                />
                <x-card-produto
                    titulo="A3"
                    descricao="Token USB. Vale mais tempo, mas exige comprar o equipamento."
                    preco="R$ 349,90"
                    cta-texto="Comprar A3"
                    cta-href="/certificado-digital/e-cnpj/"
                />
            </div>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você emite sem fechar o seu dia de trabalho</h2>
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

    {{-- Documentos --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que ter em mãos na videoconferência</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>CCMEI, baixado no Portal do Empreendedor</div>
                <div>Cartão CNPJ</div>
                <div>Documento de identidade com foto</div>
                <div>CPF, se não constar no documento</div>
            </div>
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>

    {{-- FAQ: categoria 7 (MEI), com itens selecionados de 1 e 3 --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Sou MEI, pago mais barato?',
                    'resposta' => 'Não. O MEI compra o mesmo e-CNPJ vendido para qualquer empresa, com a variação A1 pré-selecionada. Não existe SKU nem preço exclusivo para MEI.',
                    'ancora' => 'sou-mei-pago-mais-barato',
                ],
                [
                    'pergunta' => 'Preciso do certificado para abrir MEI?',
                    'resposta' => 'Não. Para abrir o MEI você só precisa do CCMEI, emitido gratuitamente no Portal do Empreendedor. O Certificado Digital entra depois, quando você precisa emitir nota fiscal ou assinar em nome do CNPJ.',
                    'ancora' => 'preciso-certificado-para-abrir-mei',
                ],
                [
                    'pergunta' => 'Sou MEI e não tenho contador. Consigo usar sozinho?',
                    'resposta' => 'Sim. Depois de emitido, o e-CNPJ pode ser usado diretamente por você para emitir nota fiscal e acessar o e-CAC, sem depender de um contador. Se preferir, o arquivo A1 também pode ser compartilhado com um contador quando você contratar um.',
                    'ancora' => 'mei-sem-contador-consigo-usar-sozinho',
                ],
                [
                    'pergunta' => 'Meu MEI está com pendência. Posso comprar?',
                    'resposta' => 'Sim, a compra e a emissão do certificado não dependem da regularidade do CNPJ. Mas pendências podem impedir o uso do certificado para algumas finalidades, como emitir nota fiscal, então recomendamos regularizar o quanto antes.',
                    'ancora' => 'mei-com-pendencia-posso-comprar',
                ],
                [
                    'pergunta' => 'O que é um certificado digital?',
                    'resposta' => 'É a sua identidade eletrônica. Ele permite assinar documentos, emitir nota fiscal e acessar sistemas do governo com a mesma validade jurídica de uma assinatura em papel com firma reconhecida em cartório.',
                    'ancora' => 'o-que-e-certificado-digital',
                ],
                [
                    'pergunta' => 'Posso ter o e-CPF e o e-CNPJ ao mesmo tempo?',
                    'resposta' => 'Sim. São certificados independentes, com finalidades diferentes.',
                    'ancora' => 'posso-ter-ecpf-e-ecnpj-ao-mesmo-tempo',
                ],
                [
                    'pergunta' => 'A videoconferência tem custo?',
                    'resposta' => 'Não. Você paga apenas o valor do certificado.',
                    'ancora' => 'videoconferencia-tem-custo',
                ],
                [
                    'pergunta' => 'Outra pessoa pode fazer a validação por mim?',
                    'resposta' => 'Não. A validação é feita pelo próprio titular, ou pelo responsável legal no caso de pessoa jurídica. As exceções são procuração pública lavrada em cartório, com poder específico para validação de certificado ICP-Brasil e validade de 90 dias, e curatela nos casos previstos em lei.',
                    'ancora' => 'outra-pessoa-pode-fazer-validacao',
                ],
            ]" />
        </div>
    </section>

    {{-- Fechamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 font-heading text-xl font-bold text-ink">Compre agora e agende sua videoconferência hoje</h2>
                <p class="font-sans text-[13px] text-muted">R$ 249,90 ou R$ 229,90 no Pix</p>
            </div>
            <a href="/certificado-digital/e-cnpj/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar certificado para MEI</a>
        </div>
    </section>
</x-layout>
