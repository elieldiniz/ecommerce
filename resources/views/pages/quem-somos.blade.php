<x-layout title="Quem Somos | Autoridade de Registro Credenciada ICP-Brasil | Digital Lock">
    <x-slot:meta_description>A Digital Lock é uma Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla. Conheça como emitimos Certificados Digitais.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Quem somos'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Quem é a Digital Lock</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla. Emitimos Certificados Digitais para pessoas e empresas em todo o país, com validação 100% online.</p>
            </div>
        </div>
    </section>

    {{-- Autoridade Certificadora e Autoridade de Registro --}}
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
</x-layout>
