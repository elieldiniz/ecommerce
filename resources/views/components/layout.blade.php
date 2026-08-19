<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title }}</title>
    @isset($meta_description)
        <meta name="description" content="{{ $meta_description }}">
    @endisset

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased" x-data="{ mobileMenuOpen: false }">
@php
    $customerLoggedIn = Auth::guard('customer')->check();
@endphp
    <header class="relative border-b border-border bg-white" @keydown.escape.window="mobileMenuOpen = false">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-4">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="md:hidden"
                    @click="mobileMenuOpen = ! mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen.toString()"
                    aria-controls="mobile-menu"
                    :aria-label="mobileMenuOpen ? 'Fechar menu de navegação' : 'Abrir menu de navegação'"
                >
                    <flux:icon.bars-3 x-show="! mobileMenuOpen" class="size-6 text-ink" />
                    <flux:icon.x-mark x-show="mobileMenuOpen" x-cloak class="size-6 text-ink" />
                </button>

                <a href="/" class="flex items-center">
                    <img src="{{ asset('digitallock-logo.png') }}" alt="Digital Lock" class="h-7 w-auto md:h-8">
                </a>
            </div>

            <nav class="hidden items-center gap-6 font-sans text-sm font-medium text-ink/80 md:flex">
                <a href="/certificado-digital/" class="hover:text-ink">Certificados</a>
                <a href="/certificado-digital/e-cpf/" class="hover:text-ink">e-CPF</a>
                <a href="/certificado-digital/e-cnpj/" class="hover:text-ink">e-CNPJ</a>
                <a href="/certificado-digital-para-mei/" class="hover:text-ink">MEI</a>
                <a href="/renovacao-certificado-digital/" class="hover:text-ink">Renovação</a>
                <a href="/como-emitir-certificado-digital/" class="hover:text-ink">Como emitir</a>
                <a href="/suporte/" class="hover:text-ink">Suporte</a>
            </nav>

            <div class="flex items-center gap-4">
                @if ($customerLoggedIn)
                    <a href="/minha-conta/pedidos/" class="hidden items-center gap-1.5 font-sans text-sm font-medium text-ink/80 hover:text-ink md:flex">
                        <flux:icon.user class="size-4" />
                        Minha conta
                    </a>
                @else
                    <a href="/cliente/login/" class="hidden items-center gap-1.5 font-sans text-sm font-medium text-ink/80 hover:text-ink md:flex">
                        <flux:icon.user class="size-4" />
                        Entrar
                    </a>
                @endif
                <a href="/certificado-digital/" class="rounded-lg bg-brand px-4.5 py-2.5 font-heading text-sm font-semibold text-white">Comprar</a>
            </div>
        </div>

        <nav
            id="mobile-menu"
            x-show="mobileMenuOpen"
            x-cloak
            class="absolute inset-x-0 top-full z-20 flex flex-col gap-1 border-t border-b border-border bg-white px-6 py-4 font-sans text-sm font-medium text-ink/80 shadow-lg md:hidden"
        >
            <a href="/certificado-digital/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">Certificados Digitais</a>
            <a href="/certificado-digital/e-cpf/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">e-CPF</a>
            <a href="/certificado-digital/e-cnpj/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">e-CNPJ</a>
            <a href="/certificado-digital-para-mei/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">MEI</a>
            <a href="/renovacao-certificado-digital/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">Renovação</a>
            <a href="/como-emitir-certificado-digital/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">Como emitir</a>
            <a href="/suporte/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">Suporte</a>
            <a href="/quem-somos/" class="rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">Quem Somos</a>
            @if ($customerLoggedIn)
                <a href="/minha-conta/pedidos/" class="flex items-center gap-1.5 rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">
                    <flux:icon.user class="size-4" />
                    Minha conta
                </a>
            @else
                <a href="/cliente/login/" class="flex items-center gap-1.5 rounded-md px-2 py-2.5 hover:bg-surface-alt hover:text-ink">
                    <flux:icon.user class="size-4" />
                    Entrar
                </a>
            @endif
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-ink px-7 pt-10 pb-6 text-white">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-7 pb-5.5 font-sans text-[13px] leading-[1.8] text-white/70 md:grid-cols-3">
            <div>
                <div class="mb-1.5 font-heading text-sm font-bold text-white">digital<span class="text-brand">lock</span></div>
                Razão social · CNPJ<br>
                Endereço completo<br>
                Telefone · WhatsApp · E-mail
            </div>
            <div>
                <div class="mb-1.5 font-sans text-xs font-semibold tracking-wide text-white uppercase">Navegação</div>
                <a href="/certificado-digital/" class="hover:text-white">Certificados</a> · <a href="/certificado-digital/e-cpf/" class="hover:text-white">e-CPF</a> · <a href="/certificado-digital/e-cnpj/" class="hover:text-white">e-CNPJ</a><br>
                <a href="/certificado-digital-para-mei/" class="hover:text-white">MEI</a> · <a href="/renovacao-certificado-digital/" class="hover:text-white">Renovação</a> · <a href="/como-emitir-certificado-digital/" class="hover:text-white">Como emitir</a><br>
                FAQ · <a href="/suporte/" class="hover:text-white">Suporte</a> · <a href="/quem-somos/" class="hover:text-white">Quem somos</a>
            </div>
            <div>
                <div class="mb-1.5 font-sans text-xs font-semibold tracking-wide text-white uppercase">Confiança e legal</div>
                Selo ICP-Brasil com link para o ITI<br>
                Declaração de Práticas de Negócio (PDF)<br>
                Repositório da AC Digital Múltipla<br>
                Privacidade · Termos · Trocas e devoluções
            </div>
        </div>
        <div class="mx-auto flex max-w-6xl items-center justify-between border-t border-white/10 pt-4 font-sans text-xs text-white/60">
            <span>Autoridade de Registro credenciada no ICP-Brasil</span>
            <a href="#top" class="text-white/70 hover:text-white">↑ Topo</a>
        </div>
    </footer>

    @fluxScripts
</body>
</html>
