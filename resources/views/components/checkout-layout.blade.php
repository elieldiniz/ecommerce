@props([
    'title' => 'Checkout',
    'activeStep' => 2,
])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Digital Lock</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-alt font-sans text-ink antialiased">
    <x-checkout-header context="Compra segura" />
    <x-checkout-steps :active-step="$activeStep" />

    {{ $slot }}
</body>
</html>
