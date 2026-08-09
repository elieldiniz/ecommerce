<x-layout title="Suporte ao Certificado Digital | Digital Lock">
    <x-slot:meta_description>Travou em alguma etapa do seu Certificado Digital? Encontre a resposta aqui ou fale direto com a nossa equipe.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Suporte'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Suporte</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Travou em alguma etapa? Encontre a resposta aqui embaixo ou fale direto com a nossa equipe.</p>
            </div>
        </div>
    </section>

    {{-- Já uso e deu problema --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Já uso e deu problema</h2>
            <x-comparison-table
                :columns="['Situação', 'Resposta']"
                :rows="[
                    ['O sistema não reconhece o Certificado Digital', 'Confira instalação e validade. No A3, confira token conectado e driver instalado.'],
                    ['Errei a senha várias vezes', 'No A1 não bloqueia, mas não recupera. No A3 o PIN bloqueia e o PUK desbloqueia.'],
                    ['Meu Certificado Digital venceu', 'Não é reativado nem renovado. É emitido um novo.'],
                    ['Configurar no sistema de nota fiscal', 'Chame no WhatsApp com o nome do sistema.'],
                    ['Meu sistema excluiu o certificado A3', 'Acontece com gerenciador mal instalado. O A1 é o formato recomendado.'],
                    ['Enviar o Certificado Digital para o contador', 'No A1, arquivo e senha por canais separados e seguros.'],
                    ['Perdi o token', 'O certificado se perde junto. Emitir novo e revogar o anterior.'],
                    ['Mudou o responsável legal', 'É preciso emitir um novo Certificado Digital.'],
                    ['Preciso revogar meu certificado', 'A revogação é permanente e não pode ser desfeita. Fale com a equipe.'],
                ]"
            />
        </div>
    </section>
</x-layout>
