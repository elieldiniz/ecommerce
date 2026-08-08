@props(['items' => []])

<nav aria-label="Breadcrumb" class="border-b border-border bg-white px-6 py-2.5 md:px-10">
    <ol class="mx-auto flex max-w-6xl items-center gap-1 font-sans text-xs text-muted-light">
        <li><a href="/" class="hover:text-ink">Início</a></li>
        @foreach ($items as $item)
            <li aria-hidden="true" class="mx-1">›</li>
            <li>
                @if (! $loop->last && ($item['href'] ?? null))
                    <a href="{{ $item['href'] }}" class="hover:text-ink">{{ $item['label'] }}</a>
                @else
                    <span class="text-muted">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
