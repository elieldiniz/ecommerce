@props([
    'showSelector' => true,
    'precoA1' => 'R$ [PREÇO]',
    'precoA1Pix' => 'R$ [PREÇO PIX]',
    'precoA3' => 'R$ [PREÇO]',
    'precoA3Pix' => 'R$ [PREÇO PIX]',
    'variantIdA1' => null,
    'variantIdA3' => null,
    'ctaTexto' => 'Comprar agora',
])

<div
    x-data="{
        variant: 'a1',
        precos: {
            a1: { preco: @js($precoA1), pix: @js($precoA1Pix), id: @js($variantIdA1) },
            a3: { preco: @js($precoA3), pix: @js($precoA3Pix), id: @js($variantIdA3) },
        },
    }"
    {{ $attributes->class(['rounded-xl border border-border bg-surface-alt p-4.5']) }}
>
    @if ($showSelector)
        <div class="mb-2.5 font-sans text-[11px] font-semibold tracking-wide text-muted-light uppercase">Seletor de variação</div>
        <div class="mb-4 flex gap-2">
            <button
                type="button"
                @click="variant = 'a1'"
                :class="variant === 'a1' ? 'border-2 border-brand text-ink' : 'border border-border-light bg-white text-muted'"
                class="flex-1 rounded-[7px] px-2 py-2.5 text-center font-sans text-xs font-semibold"
            >A1 · arquivo</button>
            <button
                type="button"
                @click="variant = 'a3'"
                :class="variant === 'a3' ? 'border-2 border-brand text-ink' : 'border border-border-light bg-white text-muted'"
                class="flex-1 rounded-[7px] px-2 py-2.5 text-center font-sans text-xs font-semibold"
            >A3 · token</button>
        </div>
    @endif

    <div class="font-heading text-2xl leading-tight font-bold text-ink" x-text="precos[variant].preco">{{ $precoA1 }}</div>
    <div class="mb-3.5 font-sans text-xs text-muted-light" x-text="'ou ' + precos[variant].pix + ' no Pix'">ou {{ $precoA1Pix }} no Pix</div>
    <a :href="precos[variant].id ? '{{ url('/carrinho/adicionar') }}/' + precos[variant].id : '#'" class="block rounded-lg bg-brand px-3 py-3 text-center font-heading text-sm font-semibold text-white">{{ $ctaTexto }}</a>
</div>
