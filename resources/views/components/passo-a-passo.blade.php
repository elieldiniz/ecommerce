@props([
    'card' => 'surface-alt',
    'steps' => [
        ['title' => 'Escolha e pague', 'description' => 'No Pix a confirmação é imediata'],
        ['title' => 'Agende', 'description' => 'Link chega por e-mail'],
        ['title' => 'Valide ao vivo', 'description' => 'Poucos minutos'],
        ['title' => 'Baixe e instale', 'description' => 'Com suporte incluído'],
    ],
])

@php
    $cardClass = $card === 'white' ? 'bg-white' : 'bg-surface-alt';
@endphp

<div {{ $attributes->class(['grid grid-cols-2 gap-3.5 md:grid-cols-4']) }}>
    @foreach ($steps as $index => $step)
        <div class="{{ $cardClass }} rounded-xl p-4.5">
            <span class="mb-2.5 inline-flex size-6.5 items-center justify-center rounded-full bg-brand font-heading text-xs font-bold text-white">{{ $index + 1 }}</span>
            <div class="mb-1 font-sans text-sm font-semibold text-ink">{{ $step['title'] }}</div>
            <div class="font-sans text-xs text-muted-light">{{ $step['description'] }}</div>
        </div>
    @endforeach
</div>
