<x-layout title="Certificado Digital A1 e A3 | e-CPF e e-CNPJ | Digital Lock">
    <x-slot:meta_description>Compare os tipos de Certificado Digital, descubra qual você precisa e emita 100% online por videoconferência. Padrão ICP-Brasil.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Certificado Digital'],
    ]" />

    {{-- Bloco 1: dobra inicial --}}
    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Certificado Digital</h1>
                <p class="mb-5.5 font-sans text-base leading-relaxed text-muted">Compare os tipos, descubra qual você precisa e emita 100% online por videoconferência.</p>
                <div class="flex flex-col gap-2.5 sm:flex-row">
                    <a href="#todos-os-certificados" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white">Ver certificados</a>
                    <a href="#" class="rounded-lg border border-border-light bg-white px-5.5 py-3 text-center font-heading text-sm font-semibold text-ink">Falar no WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Bloco 2: e-CPF vs. e-CNPJ --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Comece por aqui: é para você ou para a sua empresa?</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-card-produto
                    titulo="e-CPF"
                    descricao="Representa você como pessoa física. Assinar documentos, declarar o Imposto de Renda e acessar os serviços do governo."
                    preco="A partir de R$ 139,90"
                    cta-texto="Ver e-CPF"
                    cta-href="/certificado-digital/e-cpf/"
                />
                <x-card-produto
                    titulo="e-CNPJ"
                    descricao="Representa a sua empresa. Emitir nota fiscal, acessar o e-CAC e assinar em nome do CNPJ."
                    preco="A partir de R$ 249,90"
                    cta-texto="Ver e-CNPJ"
                    cta-href="/certificado-digital/e-cnpj/"
                />
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Tem empresa e também precisa assinar como pessoa física? São certificados independentes.</p>
        </div>
    </section>

    {{-- Bloco 3: A1 x A3 completo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Depois escolha o tipo: A1 ou A3</h2>
            <x-comparison-table
                :columns="['Critério', 'A1', 'A3']"
                :rows="[
                    ['Onde fica', 'Arquivo instalado no computador', 'Token USB ou cartão com leitora'],
                    ['Validade', '1 ano', '1, 2 ou 3 anos'],
                    ['Precisa comprar equipamento', 'Não', 'Sim'],
                    ['Uso em mais de um computador', 'Sim', 'Só onde o dispositivo estiver conectado'],
                    ['Enviar para o contador', 'Sim, é um arquivo', 'Não, depende do token físico'],
                    ['Se o computador quebrar', 'Reinstala pela cópia de segurança', 'O Certificado Digital está no token'],
                    ['Se o token for perdido', 'Não se aplica', 'Precisa emitir de novo'],
                    ['Sistema operacional', 'Windows, Mac OS e Linux', 'Windows e Linux. Restrição no Mac'],
                    ['Software de nota fiscal', 'Formato recomendado', 'Pode ser excluído pelo software'],
                ]"
            />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">O A1 atende a maior parte dos casos. O A3 faz sentido quando você quer validade mais longa.</p>
        </div>
    </section>

    {{-- Bloco 4: todos os certificados --}}
    <section id="todos-os-certificados" class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Todos os Certificados Digitais</h2>
            <table class="w-full border-collapse bg-white font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border-b-2 border-border px-3 py-2.5 text-left text-xs font-semibold text-ink">Certificado Digital</th>
                        <th class="border-b-2 border-border px-3 py-2.5 text-left text-xs font-semibold text-ink">Para quem</th>
                        <th class="border-b-2 border-border px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border-b-2 border-border px-3 py-2.5 text-left text-xs font-semibold text-ink">Validade</th>
                        <th class="border-b-2 border-border px-3 py-2.5 text-left text-xs font-semibold text-ink">Preço</th>
                        <th class="border-b-2 border-border px-3 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border-b border-border px-3 py-2.5 text-ink">e-CPF A1</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Pessoa física</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Arquivo</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">1 ano</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">R$ 139,90</td>
                        <td class="border-b border-border px-3 py-2.5"><a href="/certificado-digital/e-cpf/" class="inline-block rounded-md bg-brand px-3 py-1.5 font-heading text-xs font-semibold text-white">Comprar</a></td>
                    </tr>
                    <tr>
                        <td class="border-b border-border px-3 py-2.5 text-ink">e-CPF A3</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Pessoa física</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Token ou cartão</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">1 a 3 anos</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">R$ 219,90</td>
                        <td class="border-b border-border px-3 py-2.5"><a href="/certificado-digital/e-cpf/" class="inline-block rounded-md bg-brand px-3 py-1.5 font-heading text-xs font-semibold text-white">Comprar</a></td>
                    </tr>
                    <tr>
                        <td class="border-b border-border px-3 py-2.5 text-ink">e-CNPJ A1</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Empresa e MEI</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">Arquivo</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">1 ano</td>
                        <td class="border-b border-border px-3 py-2.5 text-ink">R$ 249,90</td>
                        <td class="border-b border-border px-3 py-2.5"><a href="/certificado-digital/e-cnpj/" class="inline-block rounded-md bg-brand px-3 py-1.5 font-heading text-xs font-semibold text-white">Comprar</a></td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2.5 text-ink">e-CNPJ A3</td>
                        <td class="px-3 py-2.5 text-ink">Empresa e MEI</td>
                        <td class="px-3 py-2.5 text-ink">Token ou cartão</td>
                        <td class="px-3 py-2.5 text-ink">1 a 3 anos</td>
                        <td class="px-3 py-2.5 text-ink">R$ 349,90</td>
                        <td class="px-3 py-2.5"><a href="/certificado-digital/e-cnpj/" class="inline-block rounded-md bg-brand px-3 py-1.5 font-heading text-xs font-semibold text-white">Comprar</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco 5: casos de uso --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Painel editável sem dev</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Ainda em dúvida? Veja pela sua situação</h2>
            <x-comparison-table
                :columns="['Sua situação', 'O que você precisa']"
                :rows="[
                    ['Sou MEI e preciso emitir nota fiscal', 'e-CNPJ A1'],
                    ['Tenho empresa e o contador pediu o certificado', 'e-CNPJ A1'],
                    ['Quero declarar o Imposto de Renda com certificado', 'e-CPF A1'],
                    ['Preciso assinar contratos com validade jurídica', 'e-CPF A1'],
                    ['Sou profissional da saúde e assino prescrição', 'e-CPF A1 ou A3'],
                    ['Advogado, preciso acessar processo eletrônico', 'e-CPF A1 ou A3'],
                    ['Vou participar de licitação', 'e-CNPJ A1'],
                    ['Preciso subir a conta gov.br para nível ouro', 'e-CPF A1'],
                    ['Uso computador com Mac OS', 'A1, em qualquer perfil'],
                    ['Preciso acessar o INSS pelo Meu INSS', 'e-CPF A1'],
                ]"
            />
        </div>
    </section>

    {{-- Bloco 6: elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Todo Certificado Digital é emitido por Validação por videoconferência</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Bloco 7: passo a passo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Do pagamento ao Certificado Digital em uso</h2>
            <x-passo-a-passo card="surface-alt" />
        </div>
    </section>

    {{-- Bloco 8: renovação --}}
    <section class="w-full bg-surface-alt px-6 py-9 md:px-10">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 font-heading text-xl font-bold text-ink">Já tem Certificado Digital e está vencendo?</h2>
                <p class="font-sans text-[13px] text-muted">Renovar segue o mesmo processo da primeira emissão.</p>
            </div>
            <a href="/renovacao-certificado-digital/" class="rounded-lg bg-cta-secondary px-5 py-2.5 text-center font-heading text-[13px] font-semibold whitespace-nowrap text-white">Renovar meu certificado</a>
        </div>
    </section>

    {{-- Bloco 9: credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>

    {{-- FAQ: categoria 1 (Antes de comprar) --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Qual a diferença entre e-CPF e e-CNPJ?',
                    'resposta' => 'O e-CPF representa você como pessoa física: assina documentos, declara o Imposto de Renda e acessa serviços do governo. O e-CNPJ representa a sua empresa: emite nota fiscal, acessa o e-CAC e assina em nome do CNPJ. São Certificados Digitais independentes.',
                    'ancora' => 'diferenca-ecpf-ecnpj',
                ],
                [
                    'pergunta' => 'Qual a diferença entre A1 e A3?',
                    'resposta' => 'O A1 é um arquivo instalado no computador, vale 1 ano e não exige equipamento. O A3 fica gravado em um token USB ou cartão com leitora, vale de 1 a 3 anos e só funciona no dispositivo em que estiver conectado.',
                    'ancora' => 'diferenca-a1-a3',
                ],
                [
                    'pergunta' => 'Qual é o mais vendido?',
                    'resposta' => 'O A1 é o mais vendido, porque atende a maior parte dos casos sem exigir a compra de um equipamento.',
                    'ancora' => 'mais-vendido',
                ],
                [
                    'pergunta' => 'Sou MEI, qual eu compro?',
                    'resposta' => 'O MEI compra o e-CNPJ, o mesmo Certificado Digital usado por qualquer empresa. Não existe versão nem preço exclusivo para MEI.',
                    'ancora' => 'sou-mei-qual-compro',
                ],
            ]" />
        </div>
    </section>

    {{-- Bloco 11: fechamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-2xl font-bold text-ink">Já sabe o que precisa?</h2>
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <a href="/certificado-digital/e-cpf/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CPF</a>
                <a href="/certificado-digital/e-cnpj/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CNPJ</a>
            </div>
        </div>
        <div class="mx-auto mt-4 max-w-6xl">
            <a href="#" class="font-sans text-[13px] font-semibold text-brand">Fale com a gente no WhatsApp →</a>
        </div>
    </section>
</x-layout>
