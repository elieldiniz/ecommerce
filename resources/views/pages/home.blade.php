<x-layout title="Certificado Digital A1 e A3 | e-CPF e e-CNPJ Online | Digital Lock">
    <x-slot:meta_description>Emita seu Certificado Digital e-CPF ou e-CNPJ, nos formatos A1 e A3, 100% online por videoconferência. Padrão ICP-Brasil, com suporte na instalação e Pix com confirmação imediata.</x-slot:meta_description>

    {{-- Bloco 1: dobra inicial --}}
    <section class="relative w-full overflow-hidden bg-ink px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-white md:text-4xl">Certificado Digital sem sair de onde você está</h1>
                <p class="mb-5.5 max-w-md font-sans text-base leading-relaxed text-white/80">Emissão 100% online por videoconferência, no dia e no horário que você escolher. Padrão ICP-Brasil.</p>
                <div class="mb-4.5 flex flex-col gap-2.5 sm:flex-row">
                    <a href="/certificado-digital/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white">Ver certificados</a>
                    <a href="#" class="rounded-lg border border-white/40 bg-white/10 px-5.5 py-3 text-center font-heading text-sm font-semibold text-white">Falar no WhatsApp</a>
                </div>
                <p class="font-sans text-xs text-white/65">Autoridade de Registro credenciada no ICP-Brasil · Validação por videoconferência sem custo extra · Suporte na instalação</p>
            </div>
        </div>
    </section>

    {{-- Bloco 2: escolha de perfil --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Qual certificado você precisa</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-card-produto
                    titulo="e-CPF"
                    descricao="Para pessoa física. Assinar documentos, declarar Imposto de Renda e acessar serviços do governo."
                    preco="A partir de R$ 139,90"
                    cta-texto="Ver e-CPF"
                    cta-href="/certificado-digital/e-cpf/"
                />
                <x-card-produto
                    :featured="true"
                    titulo="e-CNPJ"
                    descricao="Para empresas. Emitir nota fiscal eletrônica, acessar o e-CAC, enviar eSocial e assinar em nome da empresa."
                    preco="A partir de R$ 249,90"
                    cta-texto="Ver e-CNPJ"
                    cta-href="/certificado-digital/e-cnpj/"
                />
                <x-card-produto
                    titulo="Sou MEI"
                    descricao="Para microempreendedor individual. O Certificado Digital para emitir nota fiscal e cumprir as obrigações do CNPJ."
                    preco="A partir de R$ 189,90"
                    cta-texto="Ver opções para MEI"
                    cta-href="/certificado-digital-para-mei/"
                />
            </div>
            <a href="/certificado-digital/" class="mt-4 inline-block font-sans text-[13px] font-semibold text-brand">Não sabe qual escolher? Compare os tipos de Certificado Digital →</a>
        </div>
    </section>

    {{-- Blocos 3-4: elegibilidade e passo a passo (ordem invertida no mobile) --}}
    <div class="flex flex-col md:contents">
        <section class="order-2 w-full bg-white px-6 py-11 md:order-none md:px-10 md:py-14">
            <div class="mx-auto max-w-6xl">
                <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você emite sem ir a nenhuma loja</h2>
                <p class="mb-4.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">A validação da sua identidade é feita por videoconferência, com agendamento no dia e no horário que você escolher. Você pode usar esse formato se:</p>
                <x-elegibilidade-videoconferencia />
                <p class="mt-3.5 font-sans text-[13px] text-muted-light">Não há custo adicional pela Validação por videoconferência.</p>
            </div>
        </section>

        <section class="order-1 w-full bg-surface-alt px-6 py-11 md:order-none md:px-10 md:py-14">
            <div class="mx-auto max-w-6xl">
                <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Do pagamento ao Certificado Digital em uso</h2>
                <x-passo-a-passo card="white" />
                <a href="/como-emitir-certificado-digital/" class="mt-4.5 inline-block font-sans text-[13px] font-semibold text-brand">Veja o passo a passo completo →</a>
            </div>
        </section>
    </div>

    {{-- Bloco 5: A1 ou A3 --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">A diferença entre A1 e A3</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-[15px] font-bold text-ink">A1</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Arquivo instalado no computador. Vale 1 ano, não exige equipamento e pode ser instalado em mais de uma máquina.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-[15px] font-bold text-ink">A3</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Gravado em token USB ou cartão com leitora. Vale 1, 2 ou 3 anos e só funciona onde o dispositivo estiver conectado.</p>
                </div>
            </div>
            <a href="/certificado-digital/" class="mt-4 inline-block font-sans text-[13px] font-semibold text-brand">Ver comparativo completo →</a>
        </div>
    </section>

    {{-- Bloco 6: diferenciais --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">O que você encontra aqui</h2>
            <div class="grid grid-cols-2 gap-2.5 md:grid-cols-5">
                <div class="rounded-[10px] border border-border bg-white px-1.5 py-3.5 text-center">
                    <div class="mb-1 font-sans text-[12.5px] font-semibold text-ink">AR credenciada</div>
                    <div class="font-sans text-[11px] leading-tight text-muted-light">Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla</div>
                </div>
                <div class="rounded-[10px] border border-border bg-white px-1.5 py-3.5 text-center">
                    <div class="mb-1 font-sans text-[12.5px] font-semibold text-ink">Preço direto</div>
                    <div class="font-sans text-[11px] leading-tight text-muted-light">O valor que aparece é o final</div>
                </div>
                <div class="rounded-[10px] border border-border bg-white px-1.5 py-3.5 text-center">
                    <div class="mb-1 font-sans text-[12.5px] font-semibold text-ink">Pix imediato</div>
                    <div class="font-sans text-[11px] leading-tight text-muted-light">Pagou, já pode agendar</div>
                </div>
                <div class="rounded-[10px] border border-border bg-white px-1.5 py-3.5 text-center">
                    <div class="mb-1 font-sans text-[12.5px] font-semibold text-ink">Suporte real</div>
                    <div class="font-sans text-[11px] leading-tight text-muted-light">Ajuda na instalação</div>
                </div>
                <div class="rounded-[10px] border border-border bg-white px-1.5 py-3.5 text-center">
                    <div class="mb-1 font-sans text-[12.5px] font-semibold text-ink">Todo o Brasil</div>
                    <div class="font-sans text-[11px] leading-tight text-muted-light">Processo 100% remoto</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Bloco 7: renovação --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="mb-1 font-heading text-xl font-bold text-ink">Seu Certificado Digital está vencendo?</h2>
                <p class="font-sans text-[13px] text-muted">Renovar é o mesmo processo da primeira emissão e leva o mesmo tempo.</p>
            </div>
            <a href="/renovacao-certificado-digital/" class="rounded-lg bg-cta-secondary px-5 py-2.5 text-center font-heading text-[13px] font-semibold whitespace-nowrap text-white">Renovar meu Certificado Digital</a>
        </div>
    </section>

    {{-- Bloco 8: FAQ curto --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Quanto tempo leva para eu ter o Certificado Digital?',
                    'resposta' => 'Depende da sua agenda: assim que o pagamento é confirmado (imediato no Pix), você recebe o link para marcar a Validação por videoconferência no dia e horário que preferir. Depois da validação, a emissão costuma ser liberada em poucos minutos.',
                    'ancora' => 'quanto-tempo-leva',
                ],
                [
                    'pergunta' => 'A Validação por videoconferência tem custo?',
                    'resposta' => 'Não. A Validação por videoconferência está incluída no valor do Certificado Digital, sem taxa adicional.',
                    'ancora' => 'videoconferencia-tem-custo',
                ],
                [
                    'pergunta' => 'A Digital Lock é uma Autoridade de Registro ou uma Autoridade Certificadora?',
                    'resposta' => 'A Digital Lock é uma Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla. A AR valida a identidade do titular e autoriza a emissão; quem emite tecnicamente o certificado é a AC Digital Múltipla.',
                    'ancora' => 'sao-autoridade-de-registro',
                ],
                [
                    'pergunta' => 'Quem pode fazer a Validação por videoconferência?',
                    'resposta' => 'Quem já teve certificado digital emitido a partir de 2018, com coleta de biometria facial e digital, em qualquer Autoridade de Registro, ou tem CNH emitida ou renovada a partir de 2017.',
                    'ancora' => 'quem-pode-validar-por-videoconferencia',
                ],
            ]" />
            <a href="/como-emitir-certificado-digital/" class="mt-4 inline-block font-sans text-[13px] font-semibold text-brand">Ver todas as dúvidas →</a>
        </div>
    </section>

    {{-- Bloco 9: contato --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-2.5 font-heading text-2xl font-bold text-ink">Ficou com dúvida antes de comprar?</h2>
            <p class="mb-5 max-w-lg font-sans text-sm leading-relaxed text-muted">Fale com a nossa equipe pelo WhatsApp ou pelo formulário. Respondemos em horário comercial.</p>
            <div class="flex max-w-md flex-col gap-2.5">
                <label for="home-contato-nome" class="sr-only">Nome</label>
                <input id="home-contato-nome" type="text" placeholder="Nome" class="w-full rounded-lg border border-border px-3.5 py-2.5 font-sans text-[13px] text-ink placeholder:text-muted-light">

                <label for="home-contato-email" class="sr-only">E-mail</label>
                <input id="home-contato-email" type="email" placeholder="E-mail" class="w-full rounded-lg border border-border px-3.5 py-2.5 font-sans text-[13px] text-ink placeholder:text-muted-light">

                <label for="home-contato-whatsapp" class="sr-only">WhatsApp</label>
                <input id="home-contato-whatsapp" type="tel" placeholder="WhatsApp" class="w-full rounded-lg border border-border px-3.5 py-2.5 font-sans text-[13px] text-ink placeholder:text-muted-light">

                <label for="home-contato-mensagem" class="sr-only">Mensagem</label>
                <textarea id="home-contato-mensagem" placeholder="Mensagem" rows="3" class="w-full rounded-lg border border-border px-3.5 py-2.5 font-sans text-[13px] text-ink placeholder:text-muted-light"></textarea>

                <button type="button" class="self-start rounded-lg bg-brand px-5.5 py-2.5 font-heading text-[13px] font-semibold text-white">Enviar</button>
            </div>
        </div>
    </section>
</x-layout>
