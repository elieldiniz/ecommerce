@props([
    'context',
])

<header {{ $attributes->class(['flex items-center justify-between border-b border-border bg-white px-7 py-4']) }}>
    <div class="flex items-center gap-2">
        <img src="{{ asset('digitallock-logo.png') }}" alt="Digital Lock" class="h-7 w-auto">
    </div>
    <div class="flex items-center gap-4 font-sans text-sm text-muted">
        <span>{{ $context }}</span>
        <a href="{{ route('suporte') }}" class="font-semibold text-brand">Ajuda</a>
    </div>
</header>
