@props([
    'events',
])

<ol {{ $attributes->class(['flex flex-col gap-3.5']) }}>
    @foreach ($events as $event)
        <li class="flex gap-3.5 font-sans text-sm">
            <span class="w-36 flex-none text-xs text-muted-light">{{ $event['date'] }}</span>
            <div>
                <div class="text-ink">{{ $event['description'] }}</div>
                <div class="text-xs text-muted-light">{{ $event['origin'] }}</div>
            </div>
        </li>
    @endforeach
</ol>
