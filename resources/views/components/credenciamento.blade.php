@props([
    'itiHref' => '#',
])

<div {{ $attributes->class(['flex items-start gap-3.5']) }}>
    <div class="flex size-8.5 flex-none items-center justify-center rounded-full bg-highlight font-sans text-base text-brand">✓</div>
    <div class="font-sans text-sm leading-relaxed text-muted">
        <strong class="font-sans text-sm font-semibold text-ink">Autoridade de Registro credenciada</strong><br>
        Credenciada no ICP-Brasil e vinculada à AC Digital Múltipla.
        <a href="{{ $itiHref }}" class="font-semibold text-brand">Ver listagem oficial do ITI →</a>
    </div>
</div>
