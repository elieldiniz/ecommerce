<?php

use App\Models\Customer;
use App\Models\HolderType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'clientes', 'title' => 'Clientes'])] #[Title('Clientes')] class extends Component
{
    public ?string $tipoPessoa = null;

    public ?string $uf = null;

    public ?string $periodo = null;

    public ?string $certificadoVencendo = null;

    public string $busca = '';

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['holderType', 'addresses' => fn ($q) => $q->where('is_primary', true)])
            ->when($this->tipoPessoa, fn (Builder $q, string $v) => $q->whereHas('holderType', fn ($q2) => $q2->where('slug', $v)))
            ->when($this->uf, fn (Builder $q, string $v) => $q->whereHas('addresses', fn ($q2) => $q2->where('is_primary', true)->where('state', $v)))
            ->when($this->periodo, fn (Builder $q, string $v) => match ($v) {
                '7d' => $q->where('created_at', '>=', now()->subDays(7)),
                '30d' => $q->where('created_at', '>=', now()->subDays(30)),
                '90d' => $q->where('created_at', '>=', now()->subDays(90)),
                default => $q,
            })
            ->when($this->certificadoVencendo === 'sim', fn (Builder $q) => $q->whereHas('orders.items.gfsis', fn ($q2) => $q2->where('certificate_expires_at', '>', now())->where('certificate_expires_at', '<=', now()->addDays(90))))
            ->when($this->busca, fn (Builder $q, string $v) => $q->where(function ($q2) use ($v) {
                $q2->where('legal_name', 'like', "%{$v}%")
                    ->orWhere('document', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function ufOptions(): \Illuminate\Support\Collection
    {
        return Customer::query()
            ->join('customer_addresses', 'customers.id', '=', 'customer_addresses.customer_id')
            ->where('customer_addresses.is_primary', true)
            ->distinct()
            ->pluck('customer_addresses.state')
            ->sort()
            ->values();
    }

    public function orderCount(Customer $customer): int
    {
        return $customer->orders()->count();
    }

    public function lastPurchaseDate(?Customer $customer): ?string
    {
        if (! $customer) {
            return null;
        }

        $lastOrder = $customer->orders()->latest('created_at')->first();

        return $lastOrder?->created_at?->format('d/m/Y');
    }
}
?>

<div>
    {{-- Bloco: Filtros --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                <select wire:model.live="tipoPessoa" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todos</option>
                    <option value="pf">Pessoa Física</option>
                    <option value="pj">Pessoa Jurídica</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                <select wire:model.live="uf" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todas</option>
                    @foreach ($this->ufOptions as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Período de cadastro</label>
                <select wire:model.live="periodo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todo o período</option>
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d">Últimos 30 dias</option>
                    <option value="90d">Últimos 90 dias</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Com certificado vencendo</label>
                <select wire:model.live="certificadoVencendo" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
                    <option value="">Todos</option>
                    <option value="sim">Sim</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Buscar</label>
                <input wire:model.live.debounce.300ms="busca" type="text" placeholder="Nome, documento ou e-mail" class="w-full rounded-lg border border-border-light px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Tabela de clientes --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Filtros e lista</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Nome ou razão social</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">E-mail</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedidos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Última compra</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->customers as $customer)
                        <tr class="hover:bg-surface-alt">
                            <td class="border border-border px-3 py-2.5 text-ink"><a href="{{ route('painel.clientes.show', $customer->id) }}" class="font-semibold text-brand">{{ $customer->legal_name }}</a></td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->document }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->holderType?->name ?? '—' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->email }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $this->orderCount($customer) }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $this->lastPurchaseDate($customer) ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum cliente encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 font-sans text-xs text-muted-light">
            Mostrando {{ $this->customers->firstItem() ?? 0 }} a {{ $this->customers->lastItem() ?? 0 }} de {{ $this->customers->total() }}
        </div>

        <div class="mt-4">
            {{ $this->customers->links() }}
        </div>
    </section>
</div>
