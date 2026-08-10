<x-layout title="Suporte ao Certificado Digital | Digital Lock">
    <x-slot:meta_description>Travou em alguma etapa do seu Certificado Digital? Encontre a resposta aqui ou fale direto com a nossa equipe.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Suporte'],
    ]" />

    {{-- Bloco 1: dobra inicial --}}
    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Suporte</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Travou em alguma etapa? Encontre a resposta aqui embaixo ou fale direto com a nossa equipe.</p>
            </div>
        </div>
    </section>

    {{-- Bloco 2: triagem por âncora --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Em que ponto você está?</h2>
            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-4">
                <a href="#bloco-3" class="rounded-xl border border-border bg-white p-4.5 hover:border-border-light">
                    <div class="mb-1.5 font-heading text-sm font-bold text-ink">Comprei e não recebi nada</div>
                    <span class="font-sans text-xs font-semibold text-brand">Vai para o bloco 3 →</span>
                </a>
                <a href="#bloco-4" class="rounded-xl border border-border bg-white p-4.5 hover:border-border-light">
                    <div class="mb-1.5 font-heading text-sm font-bold text-ink">Vou fazer a videoconferência</div>
                    <span class="font-sans text-xs font-semibold text-brand">Vai para o bloco 4 →</span>
                </a>
                <a href="#bloco-5" class="rounded-xl border border-border bg-white p-4.5 hover:border-border-light">
                    <div class="mb-1.5 font-heading text-sm font-bold text-ink">Preciso instalar</div>
                    <span class="font-sans text-xs font-semibold text-brand">Vai para o bloco 5 →</span>
                </a>
                <a href="#bloco-6" class="rounded-xl border border-border bg-white p-4.5 hover:border-border-light">
                    <div class="mb-1.5 font-heading text-sm font-bold text-ink">Já uso e deu problema</div>
                    <span class="font-sans text-xs font-semibold text-brand">Vai para o bloco 6 →</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Bloco 3: comprei e ainda não recebi as instruções --}}
    <section id="bloco-3" class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Comprei e ainda não recebi as instruções</h2>
            <div class="flex flex-col gap-3 font-sans text-[13px] leading-relaxed text-muted">
                <div><strong class="text-ink">Pagou no Pix?</strong> Confirmação em poucos minutos. Se passou disso, chame no WhatsApp com o número do pedido.</div>
                <div><strong class="text-ink">Pagou no cartão?</strong> A aprovação depende da operadora.</div>
                <div><strong class="text-ink">Não veio o e-mail de agendamento?</strong> Confira spam e promoções. Se não estiver lá, reenviamos.</div>
                <div><strong class="text-ink">Não sabe o número do pedido?</strong> Está no e-mail de confirmação e em minha conta.</div>
            </div>
        </div>
    </section>

    {{-- Bloco 4: vou fazer a validação por videoconferência --}}
    <section id="bloco-4" class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Vou fazer a validação por videoconferência</h2>
            <div class="mb-3.5 grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Documentos originais em mãos</div>
                <div>Conexão estável, rede fixa</div>
                <div>Ambiente iluminado, luz de frente</div>
                <div>Câmera e microfone testados</div>
                <div>Rosto descoberto</div>
            </div>
            <p class="font-sans text-[13px] text-muted-light">Perdeu o horário? É só reagendar. Validação não concluída não faz você perder a compra.</p>
        </div>
    </section>

    {{-- Bloco 5: preciso instalar o Certificado Digital --}}
    <section id="bloco-5" class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Preciso instalar o Certificado Digital</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Certificado Digital A1</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Senha de emissão vale uma vez. Senha de instalação é usada em cada computador. Não tem PIN. As senhas não podem ser recuperadas. Faça cópia de segurança do arquivo.</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-alt p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Certificado Digital A3</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Instale o driver do dispositivo antes. Você define o PIN e recebe o PUK para desbloqueio.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">O A1 funciona em Windows, Mac OS e Linux. O A3 tem restrição de versão no Mac.</p>
        </div>
    </section>

    {{-- Bloco 6: já uso e deu problema --}}
    <section id="bloco-6" class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Já uso e deu problema</h2>
            <x-comparison-table
                :columns="['Situação', 'Resposta']"
                :rows="[
                    ['O sistema não reconhece o Certificado Digital', 'Confira instalação e validade. No A3, confira token conectado e driver instalado.'],
                    ['Errei a senha várias vezes', 'No A1 não bloqueia, mas não recupera. No A3 o PIN bloqueia e o PUK desbloqueia.'],
                    ['Meu Certificado Digital venceu', 'Não é reativado nem renovado. É emitido um novo.'],
                    ['Configurar no sistema de nota fiscal', 'Chame no WhatsApp com o nome do sistema.'],
                    ['Meu sistema excluiu o Certificado Digital A3', 'Acontece com gerenciador mal instalado. O A1 é o formato recomendado.'],
                    ['Enviar o Certificado Digital para o contador', 'No A1, arquivo e senha por canais separados e seguros.'],
                    ['Perdi o token', 'O Certificado Digital se perde junto. Emitir novo e revogar o anterior.'],
                    ['Mudou o responsável legal', 'É preciso emitir um novo Certificado Digital.'],
                    ['Preciso usar o Certificado Digital em outro computador', 'No A1, repita a instalação com o mesmo arquivo e a senha de instalação. No A3, basta conectar o token ao novo computador.'],
                    ['Preciso revogar meu Certificado Digital', 'A revogação é permanente e não pode ser desfeita. Fale com a equipe.'],
                ]"
                :row-ids="[
                    'sistema-nao-reconhece',
                    'senha-errada-varias-vezes',
                    'certificado-venceu',
                    'configurar-sistema-nota-fiscal',
                    'sistema-excluiu-certificado-a3',
                    'enviar-certificado-contador',
                    'perdi-o-token',
                    'mudou-responsavel-legal',
                    'usar-certificado-outro-computador',
                    'preciso-revogar-certificado',
                ]"
            />
        </div>
    </section>

    {{-- Bloco 7: fale com a nossa equipe --}}
    <section id="bloco-7" class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Fale com a nossa equipe</h2>
            <div class="mb-3.5 grid grid-cols-1 gap-x-6 gap-y-4 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-4">
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">WhatsApp</div>
                    (11) 91234-5678
                </div>
                <div>
                    <div class="mb-1 font-heading text-[13px] font-semibold text-ink">Telefone</div>
                    (11) 4000-1234
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
            <p class="font-sans text-[13px] text-muted-light">Para agilizar, tenha em mãos o número do pedido ou o CPF ou CNPJ do titular.</p>
        </div>
    </section>
</x-layout>
