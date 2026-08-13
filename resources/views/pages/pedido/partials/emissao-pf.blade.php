{{-- Seção: Titular --}}
<div class="rounded-xl border border-border bg-white p-6">
    <h2 class="mb-4 font-heading text-lg font-bold text-ink">Titular</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome completo</label>
            <input type="text" value="Maria Aparecida Souza" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">CPF</label>
            <input type="text" value="123.456.789-00" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Data de nascimento</label>
            <input type="text" value="14/03/1988" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
            <input type="email" value="maria.souza@exemplo.com.br" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone com DDD</label>
            <input type="text" value="(11) 98765-4321" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
    </div>
</div>

{{-- Seção: Endereço --}}
<div class="mt-6 rounded-xl border border-border bg-white p-6">
    <h2 class="mb-4 font-heading text-lg font-bold text-ink">Endereço</h2>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
            <input type="text" value="01311-000" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro</label>
            <input type="text" value="Avenida Paulista" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número</label>
            <input type="text" value="1578" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Complemento</label>
            <input type="text" value="Apto 92" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro</label>
            <input type="text" value="Bela Vista" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município</label>
            <input type="text" value="São Paulo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
        <div>
            <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
            <input type="text" value="SP" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
        </div>
    </div>
</div>

<button type="button" class="mt-6 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white">Concluir e enviar para emissão</button>
