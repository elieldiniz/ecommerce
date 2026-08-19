<?php

use App\Models\Coupon;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.admin-layout', ['activeItem' => 'formas-pagamento', 'title' => 'Formas de pagamento'])] #[Title('Formas de pagamento')] class extends Component
{
    /**
     * @return Collection<int, PaymentMethod>
     */
    #[Computed]
    public function paymentMethods(): Collection
    {
        return PaymentMethod::orderBy('position')->get();
    }

    /**
     * @return Collection<int, Coupon>
     */
    #[Computed]
    public function coupons(): Collection
    {
        return Coupon::with(['type', 'restrictedVariant'])->orderBy('code')->get();
    }

    public function togglePaymentMethodStatus(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $method = PaymentMethod::findOrFail($id);
            $method->update(['is_active' => ! $method->is_active]);
        });
    }

    public function toggleCouponStatus(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $coupon = Coupon::findOrFail($id);
            $coupon->update(['is_active' => ! $coupon->is_active]);
        });
    }
}
?>

<div>
    {{-- Bloco: Formas de pagamento --}}
    <section class="rounded-xl border border-border bg-white p-5">
        <h2 class="mb-4 font-heading text-lg font-bold text-ink">Formas de pagamento</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Código</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Nome exibido</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Desconto</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Máx. parcelas</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ordem</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->paymentMethods as $method)
                        <tr data-payment-method-row="{{ $method->id }}">
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->slug }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ number_format($method->discount_percentage, 2, ',', '.') }}%</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->max_installments }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->position }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $method->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <a href="{{ route('painel.formas-pagamento.show', $method->id) }}" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Editar</a>
                                <button type="button" wire:click.stop="togglePaymentMethodStatus({{ $method->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $method->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bloco: Cupons --}}
    <section class="mt-6 rounded-xl border border-border bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-heading text-lg font-bold text-ink">Cupons</h2>
            <a href="{{ route('painel.formas-pagamento.cupons.create') }}" class="rounded-lg bg-brand px-4 py-2.5 font-heading text-xs font-semibold text-white">Novo cupom</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] border-collapse font-sans text-[13px]">
                <thead>
                    <tr>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Código</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Tipo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Valor</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Usos</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Limite</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Vigência</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Restrição</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ativo</th>
                        <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->coupons as $coupon)
                        <tr data-coupon-row="{{ $coupon->id }}">
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->code }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->type->name }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->type->slug === 'percentage' ? number_format($coupon->value, 2, ',', '.').'%' : Number::currency($coupon->value, in: 'BRL', locale: 'pt_BR') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->uses_count }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->usage_limit ?? 'Sem limite' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->starts_at->format('d/m/Y') }} a {{ $coupon->ends_at->format('d/m/Y') }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->restrictedVariant?->sku ?? 'Todas as variantes' }}</td>
                            <td class="border border-border px-3 py-2.5 text-ink">{{ $coupon->is_active ? 'Sim' : 'Não' }}</td>
                            <td class="border border-border px-3 py-2.5">
                                <a href="{{ route('painel.formas-pagamento.cupons.show', $coupon->id) }}" class="rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">Editar</a>
                                <button type="button" wire:click.stop="toggleCouponStatus({{ $coupon->id }})" class="ml-2 rounded-lg border border-border-light px-3 py-1.5 font-sans text-xs font-semibold text-ink">{{ $coupon->is_active ? 'Desativar' : 'Ativar' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-border px-3 py-8 text-center font-sans text-sm text-muted">Nenhum cupom encontrado</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
