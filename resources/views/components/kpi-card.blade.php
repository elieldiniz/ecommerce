@props([
    'label',
    'value',
    'support' => null,
])

<div {{ $attributes->class(['rounded-xl border border-border bg-white p-4.5']) }}>
    <div class="font-sans text-xs text-muted">{{ $label }}</div>
    <div class="mt-1 font-heading text-2xl font-bold text-ink">{{ $value }}</div>
    @if ($support)
        <div class="mt-1 font-sans text-xs text-muted-light">{{ $support }}</div>
    @endif
</div>
