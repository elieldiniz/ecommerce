@props([
    'variant' => 'neutro',
])

@php
    $estilos = [
        'emitido' => 'bg-[#e4f0e8] text-[#1e5c34]',
        'agendado' => 'bg-highlight text-[#B8003A]',
        'aguardando' => 'bg-[#fbf0d8] text-[#7a5606]',
        'erro' => 'bg-[#fbe9e9] text-[#8f2020]',
        'neutro' => 'bg-[#eef0f3] text-[#5c626c]',
    ];
@endphp

<span {{ $attributes->class(['inline-block rounded-full px-2.75 py-1 font-sans text-xs font-medium whitespace-nowrap', $estilos[$variant] ?? $estilos['neutro']]) }}>{{ $slot }}</span>
