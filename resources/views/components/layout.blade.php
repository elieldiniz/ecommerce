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
<body class="bg-white font-sans text-ink antialiased">
    <header class="border-b border-border bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-4">
            <a href="/" class="flex items-center gap-2">
                <span class="inline-block h-3.5 w-3.5 rounded-full border-[2.5px] border-brand"></span>
                <span class="font-heading text-lg font-bold text-ink">digital<span class="text-brand">lock</span></span>
            </a>

            <nav class="hidden items-center gap-6 font-sans text-sm font-medium text-ink/80 md:flex">
                <a href="/certificado-digital/" class="hover:text-ink">Certificados</a>
                <a href="/certificado-digital-para-mei/" class="hover:text-ink">MEI</a>
                <a href="/renovacao-certificado-digital/" class="hover:text-ink">Renovação</a>
                <a href="/como-emitir-certificado-digital/" class="hover:text-ink">Como emitir</a>
                <a href="/suporte/" class="hover:text-ink">Suporte</a>
            </nav>

            <a href="/certificado-digital/" class="rounded-lg bg-brand px-4.5 py-2.5 font-heading text-sm font-semibold text-white">Comprar</a>
        </div>
    </header>

    <main>
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
                Certificados · e-CPF · e-CNPJ<br>
                MEI · Renovação · Como emitir<br>
                FAQ · Suporte · Quem somos
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
