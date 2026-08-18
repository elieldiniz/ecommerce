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

    public ?int $selectedCustomerId = null;

    public function selectCustomer(int $customerId): void
    {
        $this->selectedCustomerId = $this->selectedCustomerId === $customerId ? null : $customerId;
    }

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

    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        if ($this->selectedCustomerId === null) {
            return null;
        }

        return Customer::with(['holderType', 'addresses' => fn ($q) => $q->where('is_primary', true)])
            ->find($this->selectedCustomerId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order>
     */
    #[Computed]
    public function selectedCustomerOrders(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->selectedCustomerId === null) {
            return collect();
        }

        return \App\Models\Order::with(['items', 'status', 'fulfillmentStatus', 'items.gfsis'])
            ->where('customer_id', $this->selectedCustomerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\IssuanceData>
     */
    #[Computed]
    public function selectedCustomerHolders(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->selectedCustomerId === null) {
            return collect();
        }

        return \App\Models\IssuanceData::with(['holderType', 'orderItem.order', 'orderItem.gfsis'])
            ->whereHas('orderItem.order', fn ($q) => $q->where('customer_id', $this->selectedCustomerId))
            ->orderBy('created_at', 'desc')
            ->get();
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
                        <tr
                            wire:click="selectCustomer({{ $customer->id }})"
                            class="cursor-pointer {{ $selectedCustomerId === $customer->id ? 'bg-brand/5' : 'hover:bg-surface-alt' }}"
                        >
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->legal_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->document }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $customer->holderType->name }}</td>
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

    {{-- Bloco: Ficha do cliente · dados --}}
    @if ($selectedCustomerId && $this->selectedCustomer)
        <section class="mt-6 rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Ficha do cliente · dados</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social / Nome</label>
                    <input type="text" value="{{ $this->selectedCustomer->legal_name }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                    <input type="text" value="{{ $this->selectedCustomer->holderType->name }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Documento</label>
                    <input type="text" value="{{ $this->selectedCustomer->document }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                    <input type="text" value="{{ $this->selectedCustomer->email }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone</label>
                    <input type="text" value="{{ $this->selectedCustomer->phone }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->postal_code ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->street ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->number ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Complemento</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->complement ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->neighborhood ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->city ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
                <div>
                    <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                    <input type="text" value="{{ $this->selectedCustomer->addresses->first()?->state ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
                </div>
            </div>
        </section>
    @endif

    {{-- Bloco: Histórico de pedidos --}}
    @if ($selectedCustomerId)
        <section class="mt-6 rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Histórico de pedidos</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                    <thead>
                        <tr>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pedido</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Produto</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Pagamento</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Emissão</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Validade até</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->selectedCustomerOrders as $order)
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="border border-border px-3 py-2.5">
                                        <a href="{{ route('painel.vendas.show', $order->id) }}" class="font-semibold text-brand">#{{ $order->number }}</a>
                                    </td>
                                    <td class="border border-border px-3 py-2.5 text-ink">{{ $item->name_snapshot }}</td>
                                    <td class="border border-border px-3 py-2.5 text-ink">R$ {{ number_format($order->total, 2, ',', '.') }}</td>
                                    <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$order->status->slug === 'paid' ? 'emitido' : ($order->status->slug === 'cancelled' ? 'erro' : 'aguardando')">{{ $order->status->name }}</x-badge-status></td>
                                    <td class="border border-border px-3 py-2.5"><x-badge-status :variant="$order->fulfillmentStatus->slug === 'sent_to_gfsis' ? 'emitido' : ($order->fulfillmentStatus->slug === 'send_failed' ? 'erro' : ($order->fulfillmentStatus->slug === 'data_complete' ? 'agendado' : 'aguardando'))">{{ $order->fulfillmentStatus->name }}</x-badge-status></td>
                                    <td class="border border-border px-3 py-2.5 text-ink">{{ $item->gfsis?->certificate_expires_at?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum pedido encontrado</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Bloco: Titulares vinculados --}}
    @if ($selectedCustomerId)
        <section class="mt-6 rounded-xl border border-border bg-white p-5">
            <h2 class="mb-4 font-heading text-lg font-bold text-ink">Titulares vinculados</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] border-collapse font-sans text-[13px]">
                    <thead>
                        <tr>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Titular</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Documento</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Responsável</th>
                            <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Certificado até</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->selectedCustomerHolders as $holder)
                            <tr>
                                <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->holder_name }}</td>
                                <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->document }}</td>
                                <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->holderType->name }}</td>
                                <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->responsible_name ?? '—' }}</td>
                                <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->orderItem->gfsis?->certificate_expires_at?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum titular encontrado</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
