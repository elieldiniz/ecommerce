<form method="POST" action="{{ route('pedido.emissao', ['id' => $id, 'token' => request()->query('token'), 'tipo' => 'pj']) }}">
    @csrf

    {{-- Seção: Empresa --}}
    <div class="rounded-xl border border-border bg-white p-6">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Empresa</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social</label>
                <input type="text" name="holder_name" value="{{ old('holder_name', $issuanceData->holder_name) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('holder_name')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CNPJ</label>
                <input type="text" name="document" value="{{ old('document', $issuanceData->document) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('document')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail da empresa</label>
                <input type="email" name="email" value="{{ old('email', $issuanceData->email) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('email')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone com DDD</label>
                <input type="text" name="phone" value="{{ old('phone', $issuanceData->phone) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('phone')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Seção: Responsável pelo uso do certificado --}}
    <div class="mt-6 rounded-xl border border-border bg-white p-6">
        <h2 class="mb-1 font-heading text-lg font-bold text-ink">Responsável pelo uso do certificado</h2>
        <p class="mb-4 font-sans text-xs text-muted">É esta pessoa quem faz a validação por videoconferência.</p>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome completo</label>
                <input type="text" name="responsible_name" value="{{ old('responsible_name', $issuanceData->responsible_name) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('responsible_name')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CPF</label>
                <input type="text" name="responsible_document" value="{{ old('responsible_document', $issuanceData->responsible_document) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('responsible_document')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Data de nascimento</label>
                <input type="text" name="responsible_birth_date" value="{{ old('responsible_birth_date', optional($issuanceData->responsible_birth_date)->format('d/m/Y')) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('responsible_birth_date')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                <input type="email" name="responsible_email" value="{{ old('responsible_email', $issuanceData->responsible_email) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('responsible_email')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone com DDD</label>
                <input type="text" name="responsible_phone" value="{{ old('responsible_phone', $issuanceData->responsible_phone) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('responsible_phone')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Seção: Endereço da empresa --}}
    <div class="mt-6 rounded-xl border border-border bg-white p-6">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Endereço da empresa</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP da empresa</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $issuanceData->postal_code) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('postal_code')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro da empresa</label>
                <input type="text" name="street" value="{{ old('street', $issuanceData->street) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('street')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número</label>
                <input type="text" name="number" value="{{ old('number', $issuanceData->number) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('number')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Complemento</label>
                <input type="text" name="complement" value="{{ old('complement', $issuanceData->complement) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('complement')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro da empresa</label>
                <input type="text" name="neighborhood" value="{{ old('neighborhood', $issuanceData->neighborhood) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('neighborhood')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município da empresa</label>
                <input type="text" name="city" value="{{ old('city', $issuanceData->city) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('city')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF da empresa</label>
                <input type="text" name="state" value="{{ old('state', $issuanceData->state) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('state')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <button type="submit" class="mt-6 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white">Concluir e enviar para emissão</button>
</form>
