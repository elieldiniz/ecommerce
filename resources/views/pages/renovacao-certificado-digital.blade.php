<x-layout title="Renovação de Certificado Digital Online | e-CPF e e-CNPJ | Digital Lock">
    <x-slot:meta_description>Renove seu e-CPF ou e-CNPJ 100% online, por videoconferência, mesmo que o certificado anterior seja de outra Autoridade de Registro.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Renovação'],
    ]" />

    <section class="w-full bg-highlight px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Renovação de Certificado Digital</h1>
                <p class="mb-5.5 font-sans text-base leading-relaxed text-muted">Renove seu e-CPF ou e-CNPJ 100% online, por videoconferência, mesmo que o certificado anterior seja de outra Autoridade de Registro.</p>
            </div>
            <div class="grid max-w-xl grid-cols-1 gap-4 md:grid-cols-2">
                <x-card-produto
                    titulo="Renovar e-CPF"
                    descricao="Para pessoa física. Continue assinando documentos e acessando os serviços do governo."
                    preco="A partir de R$ 139,90"
                    cta-texto="Renovar e-CPF"
                    cta-href="/certificado-digital/e-cpf/"
                />
                <x-card-produto
                    titulo="Renovar e-CNPJ"
                    descricao="Para empresas. Continue emitindo nota fiscal e assinando em nome do CNPJ."
                    preco="A partir de R$ 249,90"
                    cta-texto="Renovar e-CNPJ"
                    cta-href="/certificado-digital/e-cnpj/"
                />
            </div>
        </div>
    </section>

    {{-- Passo a passo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">O processo é o mesmo da primeira emissão</h2>
            <x-passo-a-passo card="surface-alt" />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Por questões de segurança, toda a documentação é apresentada novamente. Não há processo simplificado por já ter sido cliente.</p>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Você renova sem sair de onde está</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>
</x-layout>
