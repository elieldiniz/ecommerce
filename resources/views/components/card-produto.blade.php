@props([
    'titulo',
    'descricao',
    'preco',
    'ctaTexto',
    'ctaHref',
    'featured' => false,
    'ctaColor' => null,
])

<div {{ $attributes->class([
    'rounded-xl bg-white p-5.5',
    'border-2 border-brand' => $featured,
    'border border-border' => ! $featured,
]) }}>
    <div class="mb-2 font-heading text-[15px] font-bold text-ink">{{ $titulo }}</div>
    <p class="mb-3.5 font-sans text-[13px] leading-relaxed text-muted">{{ $descricao }}</p>
    <div class="mb-3 font-heading text-xl font-bold text-ink">{{ $preco }}</div>
    <a
        href="{{ $ctaHref }}"
        @class([
            'block rounded-lg px-2.5 py-2.5 text-center font-heading text-[13px] font-semibold',
            'bg-cta-secondary text-white' => $ctaColor === 'secondary',
            'bg-brand text-white' => $ctaColor !== 'secondary' && $featured,
            'border border-border-light bg-white text-ink' => $ctaColor !== 'secondary' && ! $featured,
        ])
    >{{ $ctaTexto }}</a>
</div>
