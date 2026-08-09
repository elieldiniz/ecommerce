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

    {{-- Documentos: da empresa e do responsável --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que ter em mãos na videoconferência</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Da empresa</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Ato constitutivo registrado no órgão competente: contrato social, requerimento de empresário ou CCMEI no caso de MEI. Documentos de eleição dos administradores, quando aplicável. Cartão CNPJ.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Do responsável</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Documento de identidade oficial com foto e CPF, se não constar no documento. Original físico ou digital.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Biometria já cadastrada na base ICP-Brasil pode dispensar a apresentação dos documentos pessoais.</p>
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>

    {{-- FAQ: categorias 1, 3 e 5, priorizando contexto empresarial --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Sou MEI, posso comprar o e-CNPJ?',
                    'resposta' => 'Sim. O MEI compra o mesmo e-CNPJ usado por qualquer empresa, com a variação A1 pré-selecionada. Não existe SKU nem preço exclusivo para MEI.',
                    'ancora' => 'sou-mei-posso-comprar-ecnpj',
                ],
                [
                    'pergunta' => 'Meu contador pode usar o Certificado Digital por mim?',
                    'resposta' => 'Não. A Validação por videoconferência é feita pelo próprio responsável legal da empresa. Depois de emitido, o arquivo A1 pode ser entregue ao seu contador para uso em nome da empresa, mas a validação de identidade não pode ser feita por terceiro.',
                    'ancora' => 'contador-pode-usar-certificado',
                ],
                [
                    'pergunta' => 'Serve para o meu sistema de emissão de notas?',
                    'resposta' => 'Sim. O e-CNPJ segue o Padrão ICP-Brasil e é aceito por qualquer sistema homologado de NF-e, NFS-e ou NFC-e. No formato A1, alguns softwares de nota fiscal podem excluir o arquivo do certificado após o uso; confirme com o seu sistema qual formato ele recomenda.',
                    'ancora' => 'serve-sistema-emissao-notas',
                ],
                [
                    'pergunta' => 'Quem é o responsável pelo certificado da empresa?',
                    'resposta' => 'É o representante legal constante no ato constitutivo da empresa, ou um responsável indicado com poderes específicos. É essa pessoa que participa da Validação por videoconferência e apresenta os documentos da empresa e os seus próprios.',
                    'ancora' => 'responsavel-pelo-certificado-da-empresa',
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
            <a href="#" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CNPJ</a>
        </div>
    </section>
</x-layout>
