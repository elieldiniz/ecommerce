<x-layout title="Como Emitir Certificado Digital Online | Passo a Passo | Digital Lock">
    <x-slot:meta_description>Veja o passo a passo completo para emitir seu Certificado Digital pela internet, por videoconferência, do pagamento à instalação.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Como emitir'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Como emitir seu Certificado Digital</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Todo o processo é feito pela internet, por videoconferência, no dia e no horário que você escolher. Veja como funciona do começo ao fim.</p>
            </div>
        </div>
    </section>

    {{-- O processo em quatro etapas --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">O processo em quatro etapas</h2>
            <x-passo-a-passo card="white" />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Do pagamento à instalação, o tempo depende principalmente do horário que você escolher para a validação.</p>
        </div>
    </section>

    {{-- Etapa 1: elegibilidade --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 1</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Confira se você se enquadra</h2>
            <x-elegibilidade-videoconferencia />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Se você não se enquadra em nenhuma das duas, fale com a nossa equipe antes de comprar.</p>
        </div>
    </section>
</x-layout>
