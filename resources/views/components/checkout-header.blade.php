@props([
    'context',
])

<header {{ $attributes->class(['flex items-center justify-between border-b border-border bg-white px-7 py-4']) }}>
    <div class="flex items-center gap-2">
        <span class="inline-block size-3.5 rounded-full border-[2.5px] border-brand"></span>
        <span class="font-heading text-lg font-bold text-ink">digital<span class="text-brand">lock</span></span>
    </div>
    <div class="flex items-center gap-4 font-sans text-sm text-muted">
        <span>{{ $context }}</span>
        <a href="{{ route('suporte') }}" class="font-semibold text-brand">Ajuda</a>
    </div>
</header>
