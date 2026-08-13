@props([
    'activeStep' => 1,
])

@php
    $passos = [
        1 => '1. Carrinho',
        2 => '2. Dados e pagamento',
        3 => '3. Dados de emissão',
    ];
@endphp

<div {{ $attributes->class(['flex border-b border-border font-sans text-xs']) }}>
    @foreach ($passos as $numero => $rotulo)
        <span
            @class([
                'flex-1 border-r border-border px-4.5 py-3 last:border-r-0',
                'bg-highlight font-semibold text-ink' => $numero === (int) $activeStep,
                'text-muted-light' => $numero !== (int) $activeStep,
            ])
            @if ($numero === (int) $activeStep)
                data-step="ativo"
            @elseif ($numero < (int) $activeStep)
                data-step="concluido"
            @else
                data-step="futuro"
            @endif
        >{{ $rotulo }}</span>
    @endforeach
</div>
