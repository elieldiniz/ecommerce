<x-layout title="Renovação de Certificado Digital Online | e-CPF e e-CNPJ | Digital Lock">
    <x-slot:meta_description>Renove seu e-CPF ou e-CNPJ 100% online, por videoconferência, mesmo que o certificado anterior seja de outra Autoridade de Registro.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Renovação'],
    ]" />

    {{-- Bloco 1: dobra inicial --}}
    <section class="w-full bg-linear-to-b from-highlight to-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Renovação de Certificado Digital</h1>
                <p class="mb-5.5 font-sans text-base leading-relaxed text-muted">Renove seu e-CPF ou e-CNPJ 100% online, por videoconferência, mesmo que o certificado anterior seja de outra Autoridade de Registro.</p>
            </div>
            <div class="mb-4 grid max-w-xl grid-cols-1 gap-4 sm:grid-cols-2">
                <x-card-produto
                    cta-color="secondary"
                    titulo="Renovar e-CPF"
                    descricao="Para pessoa física. Continue assinando documentos e acessando os serviços do governo."
                    preco="A partir de R$ 139,90"
                    cta-texto="Renovar e-CPF"
                    cta-href="/certificado-digital/e-cpf/"
                />
                <x-card-produto
                    cta-color="secondary"
                    titulo="Renovar e-CNPJ"
                    descricao="Para empresas. Continue emitindo nota fiscal e assinando em nome do CNPJ."
                    preco="A partir de R$ 249,90"
                    cta-texto="Renovar e-CNPJ"
                    cta-href="/certificado-digital/e-cnpj/"
                />
            </div>
            <p class="font-sans text-xs text-muted-light">Padrão ICP-Brasil · Videoconferência sem custo extra · Aceita certificado de qualquer Autoridade de Registro</p>
        </div>
    </section>

    {{-- Bloco 2: não precisa ser na mesma Autoridade de Registro --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3 font-heading text-2xl font-bold text-ink">Não precisa ser na mesma Autoridade de Registro</h2>
            <p class="mb-2.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">Certificado digital não tem fidelidade. Você pode renovar aqui mesmo que o anterior tenha sido emitido em qualquer outra Autoridade de Registro.</p>
            <p class="max-w-2xl font-sans text-sm leading-relaxed text-muted">Também não existe transferência ou migração. A renovação é uma nova emissão, com nova validade, no mesmo padrão ICP-Brasil.</p>
        </div>
    </section>

    {{-- Bloco 3: passo a passo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">O processo é o mesmo da primeira emissão</h2>
            <x-passo-a-passo card="surface-alt" />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Por questões de segurança, toda a documentação é apresentada novamente. Não há processo simplificado por já ter sido cliente.</p>
        </div>
    </section>

    {{-- Bloco 4: quando renovar --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Quando renovar</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Antes de vencer · ideal</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Você mantém o certificado antigo funcionando enquanto resolve o novo, sem parar de emitir nota ou assinar documento.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Depois de vencer</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Também é possível, e o processo é o mesmo. A diferença é que você fica sem certificado até concluir a emissão.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Certificado vencido não é renovado nem reativado. Em ambos os casos é emitido um certificado novo.</p>
        </div>
    </section>

    {{-- Bloco 5: elegibilidade para videoconferência --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você renova sem sair de onde está</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Bloco 6: renove no mesmo tipo ou mude --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Renove no mesmo tipo ou mude</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Estava no A3 e quer o A1?</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Faz sentido se você cansou de depender do token e usa o certificado sempre no mesmo computador ou em sistema de gestão.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Estava no A1 e quer o A3?</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Faz sentido se você precisa de validade mais longa.</p>
                </div>
            </div>
            <a href="/certificado-digital/" class="mt-4 inline-block font-sans text-[13px] font-semibold text-brand">Ver comparativo entre A1 e A3 →</a>
        </div>
    </section>

    {{-- Bloco 7: documentos --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">O que ter em mãos na videoconferência</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Documentos para e-CPF</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Documento de identidade oficial com foto e CPF, se não constar no documento.</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Documentos para e-CNPJ</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Ato constitutivo registrado ou CCMEI no caso de MEI, cartão CNPJ e identidade do responsável.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Bloco 8: credenciamento --}}
    <section class="w-full bg-surface-alt px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>

    {{-- Bloco 9: FAQ categoria 6 (Renovação e vencimento) --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Meu certificado é de outra Autoridade de Registro. Posso renovar aqui?',
                    'resposta' => 'Sim. Certificado digital não tem fidelidade a nenhuma Autoridade de Registro. Você pode renovar aqui mesmo que o certificado anterior tenha sido emitido em qualquer outra empresa credenciada no ICP-Brasil.',
                    'ancora' => 'certificado-de-outra-autoridade-posso-renovar-aqui',
                ],
                [
                    'pergunta' => 'Meu certificado já venceu. Ainda posso renovar?',
                    'resposta' => 'Sim, o processo é o mesmo. A diferença é que você fica sem certificado válido até concluir a nova emissão, já que um certificado vencido não é reativado nem renovado — é sempre emitido um certificado novo.',
                    'ancora' => 'certificado-vencido-ainda-posso-renovar',
                ],
                [
                    'pergunta' => 'Posso trocar de A1 para A3 na renovação?',
                    'resposta' => 'Sim. Você pode mudar de tipo livremente na renovação, tanto de A1 para A3 quanto de A3 para A1, conforme a sua necessidade.',
                    'ancora' => 'posso-trocar-de-a1-para-a3-na-renovacao',
                ],
            ]" />
        </div>
    </section>

    {{-- Bloco 10: fechamento --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 font-heading text-xl font-bold text-ink">Pronto para renovar?</h2>
                <p class="font-sans text-[13px] text-muted">Escolha o seu certificado e agende a videoconferência hoje.</p>
            </div>
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <a href="/certificado-digital/e-cpf/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Renovar e-CPF</a>
                <a href="/certificado-digital/e-cnpj/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Renovar e-CNPJ</a>
            </div>
        </div>
    </section>
</x-layout>
