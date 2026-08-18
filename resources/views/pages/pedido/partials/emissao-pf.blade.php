<form method="POST" action="{{ route('pedido.emissao', ['id' => $id, 'token' => request()->query('token')]) }}">
    @csrf

    {{-- Seção: Titular --}}
    <div class="rounded-xl border border-border bg-white p-6">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Titular</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Nome completo</label>
                <input type="text" name="holder_name" value="{{ old('holder_name', $issuanceData->holder_name) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('holder_name')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CPF</label>
                <input type="text" name="document" value="{{ old('document', $issuanceData->document) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('document')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Data de nascimento</label>
                <input type="text" name="birth_date" value="{{ old('birth_date', optional($issuanceData->birth_date)->format('d/m/Y')) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('birth_date')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
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

    {{-- Seção: Endereço --}}
    <div class="mt-6 rounded-xl border border-border bg-white p-6">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Endereço</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $issuanceData->postal_code) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('postal_code')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro</label>
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
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro</label>
                <input type="text" name="neighborhood" value="{{ old('neighborhood', $issuanceData->neighborhood) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('neighborhood')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município</label>
                <input type="text" name="city" value="{{ old('city', $issuanceData->city) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('city')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                <input type="text" name="state" value="{{ old('state', $issuanceData->state) }}" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                @error('state')
                    <span class="mt-1 block font-sans text-xs text-[#8f2020]">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <button type="submit" class="mt-6 w-full rounded-lg bg-brand px-4 py-3 text-center font-heading text-sm font-semibold text-white">Concluir e enviar para emissão</button>
</form>
