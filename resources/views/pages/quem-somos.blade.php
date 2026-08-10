<x-layout title="Quem Somos | Autoridade de Registro Credenciada ICP-Brasil | Digital Lock">
    <x-slot:meta_description>A Digital Lock é uma Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla. Conheça como emitimos Certificados Digitais.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Quem somos'],
    ]" />

    {{-- Bloco 1: dobra inicial --}}
    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Quem é a Digital Lock</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla. Emitimos Certificados Digitais para pessoas e empresas em todo o país, com validação 100% online.</p>
            </div>
        </div>
    </section>

    {{-- Bloco 2: o que a Digital Lock faz --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3 font-heading text-2xl font-bold text-ink">O que a Digital Lock faz</h2>
            <p class="mb-2.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">Somos uma Autoridade de Registro, a AR. É a AR que recebe o pedido, confere a identidade do titular e autoriza a emissão do Certificado Digital.</p>
            <p class="max-w-2xl font-sans text-sm leading-relaxed text-muted">Trabalhamos com Certificados Digitais no Padrão ICP-Brasil, o mesmo exigido pela Receita Federal, pelos tribunais e pelos sistemas de nota fiscal do país.</p>
        </div>
    </section>

    {{-- Bloco 3: somos credenciados. Confira você mesmo. --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Argumento mais forte da página</x-eyebrow>
            <h2 class="mb-3 font-heading text-2xl font-bold text-ink">Somos credenciados. Confira você mesmo.</h2>
            <p class="mb-4.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">O credenciamento de uma Autoridade de Registro é público. O ITI, órgão do governo federal que gere o ICP-Brasil, mantém a lista oficial de todas as Autoridades de Registro autorizadas no país. A Digital Lock está nessa lista.</p>
            <a href="https://www.gov.br/iti/pt-br" target="_blank" rel="noopener noreferrer" class="inline-block rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Ver a listagem oficial no site do ITI</a>
            <p class="mt-4 max-w-2xl font-sans text-[13px] text-muted-light">Antes de comprar Certificado Digital em qualquer lugar, vale conferir se a empresa aparece nessa lista. Nossas práticas estão na Declaração de Práticas de Negócio, disponível no rodapé.</p>
        </div>
    </section>

    {{-- Bloco 4: Autoridade Certificadora e Autoridade de Registro --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Autoridade Certificadora e Autoridade de Registro</h2>
            <x-comparison-table
                :columns="['Critério', 'Autoridade Certificadora (AC)', 'Autoridade de Registro (AR)']"
                :rows="[
                    ['O que faz', 'Emite tecnicamente o Certificado Digital', 'Valida a identidade e autoriza a emissão'],
                    ['Contato com o cliente', 'Indireto, na maioria dos casos', 'Direto, é quem atende você'],
                    ['Padrão do certificado', 'ICP-Brasil', 'ICP-Brasil'],
                ]"
            />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">O que muda entre as empresas é preço e atendimento, não a validade do documento.</p>
        </div>
    </section>

    {{-- Bloco 5: como a gente trabalha --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Como a gente trabalha</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Preço no site, sem orçamento</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Você vê o valor e compra. Não pedimos seus dados para depois informar quanto custa.</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Validação online, em todo o Brasil</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">A videoconferência é agendada por você. Não é preciso se deslocar.</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Sem taxa escondida</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">O valor do Certificado Digital inclui a videoconferência e o suporte na instalação.</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Suporte que resolve</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Se travar na instalação ou na configuração do sistema, nossa equipe faz junto com você.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Bloco 6: nossos dados --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Nossos dados</h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 font-sans text-sm leading-relaxed text-muted md:grid-cols-4">
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">Razão social</div>
                    Digital Lock Autoridade de Registro Ltda.
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">CNPJ</div>
                    12.345.678/0001-90
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">Endereço completo</div>
                    Av. Paulista, 1106, 8º andar, Bela Vista, São Paulo/SP, CEP 01310-100
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">Telefone</div>
                    (11) 4000-1234
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">WhatsApp</div>
                    (11) 91234-5678
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">E-mail</div>
                    contato@digitallock.com.br
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">Horário de atendimento</div>
                    Segunda a sexta, das 9h às 18h
                </div>
            </div>
        </div>
    </section>

    {{-- Bloco 7: fechamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-2xl font-bold text-ink">Pronto para emitir seu Certificado Digital?</h2>
            <a href="/certificado-digital/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Ver certificados</a>
        </div>
    </section>
</x-layout>
