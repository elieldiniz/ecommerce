@props([
    'activeItem',
    'title',
])

@php
    $itens = [
        'visao-geral' => ['label' => 'Visão geral', 'route' => 'painel.visao-geral', 'icon' => 'squares-2x2'],
        'vendas' => ['label' => 'Vendas', 'route' => 'painel.vendas.index', 'icon' => 'shopping-cart'],
        'recuperacao' => ['label' => 'Fila de recuperação', 'route' => 'painel.recuperacao', 'icon' => 'arrow-path'],
        'produtos' => ['label' => 'Produtos', 'route' => 'painel.produtos', 'icon' => 'archive-box'],
        'formas-pagamento' => ['label' => 'Formas de pagamento', 'route' => 'painel.formas-pagamento', 'icon' => 'credit-card'],
        'clientes' => ['label' => 'Clientes', 'route' => 'painel.clientes', 'icon' => 'user-group'],
        'relatorios' => ['label' => 'Relatórios', 'route' => 'painel.relatorios', 'icon' => 'chart-bar'],
    ];
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Painel · Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <div x-data="{ menuAberto: false }" @keydown.escape.window="menuAberto = false">
        {{-- Barra mobile: só o hambúrguer, sem nenhum resquício de sidebar quando fechado --}}
        <div class="flex items-center gap-3 bg-ink px-5 py-4 text-white md:hidden">
            <button
                type="button"
                @click="menuAberto = ! menuAberto"
                :aria-expanded="menuAberto.toString()"
                aria-controls="painel-menu-mobile"
                :aria-label="menuAberto ? 'Fechar menu' : 'Abrir menu'"
            >
                <flux:icon.bars-3 x-show="! menuAberto" class="size-6" />
                <flux:icon.x-mark x-show="menuAberto" x-cloak class="size-6" />
            </button>
            <img src="{{ asset('digitallock-logo.png') }}" alt="Digital Lock" class="h-7 w-auto">
        </div>

        <div class="flex min-h-screen">
            <aside class="hidden flex-none flex-col justify-between overflow-y-auto bg-ink px-5 py-6 text-white md:sticky md:top-0 md:flex md:h-screen md:w-60">
                <div>
                    <div class="mb-8 font-heading text-lg font-bold"><img src="{{ asset('digitallock-logo.png') }}" alt="Digital Lock" class="h-7 w-auto"></div>
                    <nav class="flex flex-col gap-1">
                        @foreach ($itens as $chave => $item)
                            <a
                                href="{{ route($item['route']) }}"
                                data-item-nav="{{ $chave }}"
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2.5 font-sans text-sm',
                                    'border-l-[3px] border-brand bg-brand/15 font-semibold text-white' => $chave === $activeItem,
                                    'text-white/70 hover:text-white' => $chave !== $activeItem,
                                ])
                            >
                                <x-dynamic-component :component="'flux::icon.'.$item['icon']" class="size-5 flex-none" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
                <div class="dark border-t border-white/10 pt-4">
                    <x-desktop-user-menu :mock="$activeItem !== 'configuracoes'" />
                </div>
            </aside>

            {{-- Fundo escurecido atrás do menu aberto em mobile --}}
            <div
                x-show="menuAberto"
                x-cloak
                @click="menuAberto = false"
                class="fixed inset-0 z-20 bg-ink/60 md:hidden"
            ></div>

            {{-- Sidebar completa, abrindo por cima do conteúdo em mobile --}}
            <aside
                id="painel-menu-mobile"
                x-show="menuAberto"
                x-cloak
                class="fixed inset-y-0 left-0 z-30 flex w-60 flex-none flex-col justify-between overflow-y-auto bg-ink px-5 py-6 text-white md:hidden"
            >
                <div>
                    <div class="mb-8 flex items-center justify-between">
                        <img src="{{ asset('digitallock-logo.png') }}" alt="Digital Lock" class="h-7 w-auto">
                        <button
                            type="button"
                            @click="menuAberto = false"
                            aria-label="Fechar menu"
                        >
                            <flux:icon.x-mark class="size-6" />
                        </button>
                    </div>
                    <nav class="flex flex-col gap-1">
                        @foreach ($itens as $chave => $item)
                            <a
                                href="{{ route($item['route']) }}"
                                data-item-nav="{{ $chave }}"
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2.5 font-sans text-sm',
                                    'border-l-[3px] border-brand bg-brand/15 font-semibold text-white' => $chave === $activeItem,
                                    'text-white/70 hover:text-white' => $chave !== $activeItem,
                                ])
                            >
                                <x-dynamic-component :component="'flux::icon.'.$item['icon']" class="size-5 flex-none" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
                <div class="dark border-t border-white/10 pt-4">
                    <x-desktop-user-menu :mock="$activeItem !== 'configuracoes'" />
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="flex items-center justify-between border-b border-border bg-white px-7 py-4">
                    <h1 class="font-heading text-lg font-bold text-ink">{{ $title }}</h1>
                    <span class="font-sans text-xs text-muted">Últimos 30 dias ▾</span>
                </header>

                <main class="p-7">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
