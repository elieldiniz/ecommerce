<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #14110f;">
    <p>Olá, {{ $order->customer->legal_name }}!</p>

    <p>
        Falta pouco para finalizar a emissão do seu Certificado Digital do pedido {{ $order->number }}.
        Clique no link abaixo para preencher os dados necessários:
    </p>

    <p>
        <a href="{{ $url }}">{{ $url }}</a>
    </p>

    <p>Digital Lock</p>
</body>
</html>
