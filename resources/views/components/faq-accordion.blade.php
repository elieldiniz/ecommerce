@props([
    'items' => [],
])

<div {{ $attributes }}>
    @foreach ($items as $item)
        <div x-data="{ open: false }" id="{{ $item['ancora'] }}" class="border-b border-border">
            <button
                type="button"
                @click="open = ! open"
                :aria-expanded="open.toString()"
                class="flex w-full items-center justify-between gap-4 py-4 text-left"
            >
                <span class="font-sans text-sm font-semibold text-ink">{{ $item['pergunta'] }}</span>
                <span
                    :class="open ? 'rotate-45' : ''"
                    class="flex size-5.5 flex-none items-center justify-center rounded-full border border-border-light font-sans text-sm text-muted transition-transform"
                >+</span>
            </button>
            <div x-show="open" x-cloak class="pb-4 font-sans text-sm leading-relaxed text-muted">
                {{ $item['resposta'] }}
            </div>
        </div>
    @endforeach
</div>
