<?php

use App\Models\Customer;
use App\Models\IssuanceData;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'clientes', 'title' => 'Clientes'])] #[Title('Detalhe do cliente')] class extends Component
{
    public int $id;

    public function mount(int $id): void
    {
        if (Customer::query()->find($id) === null) {
            abort(404);
        }

        $this->id = $id;
    }

    #[Computed]
    public function customer(): ?Customer
    {
        return Customer::with(['holderType', 'addresses' => fn ($q) => $q->where('is_primary', true)])
            ->find($this->id);
    }

    /**
     * @return Collection<int, Order>
     */
    #[Computed]
    public function customerOrders(): Collection
    {
        return Order::with(['items', 'status', 'fulfillmentStatus', 'items.gfsis'])
            ->where('customer_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @return Collection<int, IssuanceData>
     */
    #[Computed]
    public function customerHolders(): Collection
    {
        return IssuanceData::with(['holderType', 'orderItem.order', 'orderItem.gfsis'])
            ->whereHas('orderItem.order', fn ($q) => $q->where('customer_id', $this->id))
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
?>

<div>
    <div class="flex items-center justify-between">
        <h1 class="font-heading text-xl font-bold text-ink">{{ $this->customer->legal_name }}</h1>
        <a href="{{ route('painel.clientes') }}" class="rounded-lg border border-border-light px-4 py-2.5 font-sans text-sm font-semibold text-ink">Voltar</a>
    </div>

    {{-- Bloco: Ficha do cliente · dados --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Ficha do cliente · dados</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Razão social / Nome</label>
                <input type="text" value="{{ $this->customer->legal_name }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Tipo de pessoa</label>
                <input type="text" value="{{ $this->customer->holderType?->name ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Documento</label>
                <input type="text" value="{{ $this->customer->document }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">E-mail</label>
                <input type="text" value="{{ $this->customer->email }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Telefone</label>
                <input type="text" value="{{ $this->customer->phone }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">CEP</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->postal_code ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Logradouro</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->street ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Número</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->number ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Complemento</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->complement ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Bairro</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->neighborhood ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">Município</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->city ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
            <div>
                <label class="mb-1 block font-sans text-xs font-semibold text-muted">UF</label>
                <input type="text" value="{{ $this->customer->addresses->first()?->state ?? '—' }}" readonly class="w-full rounded-lg border border-border-light bg-surface-alt px-3 py-2.5 font-sans text-sm text-ink">
            </div>
        </div>
    </section>

    {{-- Bloco: Histórico de pedidos --}}
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
                    @forelse ($this->customerOrders as $order)
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

    {{-- Bloco: Titulares vinculados --}}
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
                    @forelse ($this->customerHolders as $holder)
                        <tr>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->holder_name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->document }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $holder->holderType?->name ?? '—' }}</td>
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
</div>
