@props([
    'stages',
])

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    @foreach ($stages as $stage)
        <div class="flex items-center justify-between border-b border-border py-2.5 font-sans text-sm">
            <span class="text-ink">{{ $stage['name'] }}</span>
            <span class="flex items-center gap-3">
                <b class="text-ink">{{ $stage['quantity'] }}</b>
                @isset($stage['percentage'])
                    <span class="text-xs text-muted-light">{{ $stage['percentage'] }}</span>
                @endisset
            </span>
        </div>
    @endforeach
</div>
