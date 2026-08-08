@props([
    'card' => 'surface-alt',
])

@php
    $passos = [
        ['titulo' => 'Escolha e pague', 'descricao' => 'No Pix a confirmação é imediata'],
        ['titulo' => 'Agende', 'descricao' => 'Link chega por e-mail'],
        ['titulo' => 'Valide ao vivo', 'descricao' => 'Poucos minutos'],
        ['titulo' => 'Baixe e instale', 'descricao' => 'Com suporte incluído'],
    ];

    $cardClass = $card === 'white' ? 'bg-white' : 'bg-surface-alt';
@endphp

<div {{ $attributes->class(['grid grid-cols-2 gap-3.5 md:grid-cols-4']) }}>
    @foreach ($passos as $index => $passo)
        <div class="{{ $cardClass }} rounded-xl p-4.5">
            <span class="mb-2.5 inline-flex size-6.5 items-center justify-center rounded-full bg-brand font-heading text-xs font-bold text-white">{{ $index + 1 }}</span>
            <div class="mb-1 font-sans text-sm font-semibold text-ink">{{ $passo['titulo'] }}</div>
            <div class="font-sans text-xs text-muted-light">{{ $passo['descricao'] }}</div>
        </div>
    @endforeach
</div>
