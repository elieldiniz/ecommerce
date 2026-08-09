<x-layout title="Certificado Digital A1 e A3 | e-CPF e e-CNPJ | Digital Lock">
    <x-slot:meta_description>Compare os tipos de Certificado Digital, descubra qual você precisa e emita 100% online por videoconferência. Padrão ICP-Brasil.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Certificado Digital'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Certificado Digital</h1>
                <p class="font-sans text-base leading-relaxed text-muted">Compare os tipos, descubra qual você precisa e emita 100% online por videoconferência.</p>
            </div>
        </div>
    </section>

    {{-- Comece por aqui: e-CPF ou e-CNPJ --}}
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
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Tem empresa e também precisa assinar como pessoa física? São Certificados Digitais independentes.</p>
        </div>
    </section>

    {{-- Depois escolha o tipo: A1 ou A3 --}}
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
                    ['Sistema operacional', 'Windows, Mac OS e Linux', 'Windows e Linux. Restrição no Mac'],
                ]"
            />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">O A1 atende a maior parte dos casos. O A3 faz sentido quando você quer validade mais longa.</p>
        </div>
    </section>

    {{-- Elegibilidade para videoconferência --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-3.5 font-heading text-2xl font-bold text-ink">Todos os certificados são emitidos por videoconferência</h2>
            <x-elegibilidade-videoconferencia />
        </div>
    </section>

    {{-- Passo a passo --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">Do pagamento ao Certificado Digital em uso</h2>
            <x-passo-a-passo card="surface-alt" />
        </div>
    </section>

    {{-- Credenciamento --}}
    <section class="w-full bg-white px-6 py-9 md:px-10">
        <div class="mx-auto max-w-6xl">
            <x-credenciamento />
        </div>
    </section>
</x-layout>
