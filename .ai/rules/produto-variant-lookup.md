---
paths:
  - resources/views/pages/certificado-digital.blade.php
  - resources/views/pages/certificado-digital/e-cpf.blade.php
  - resources/views/pages/certificado-digital/e-cnpj.blade.php
  - resources/views/pages/certificado-digital-para-mei.blade.php
  - resources/views/pages/home.blade.php
---

# Páginas de produto (e-CPF / e-CNPJ / MEI / home)

## Buscar variante por certificate_format, nunca por SKU fixo
Essas páginas buscam a variante A1/A3 de um produto assim:
```php
$ecpf?->variants()->whereHas('certificateFormat', fn ($q) => $q->where('slug', 'a1'))->first();
```
**Nunca** volte a comparar por `sku` (`where('sku', 'ECPF-A1-12')` etc.) — já foi assim antes e quebrou em produção: o formulário de variante no painel (`painel/produtos/{id}/`) deixa o campo SKU como texto livre, sem nenhuma indicação de que precisa bater com um valor específico. Um admin cadastrando a variante com um SKU qualquer (ex: `PD-233424`) fazia a variante existir no banco corretamente, mas nunca ser encontrada pelas páginas públicas, caindo no fallback `R$ [PREÇO]`. `certificate_format_id` é um `<select>` no formulário (não texto livre), então é o campo seguro pra identificar "a variante A1" vs "a variante A3" — SKU é só um código de referência interno, não uma chave de busca.

## Produto ainda é identificado por slug fixo — risco conhecido, fora de escopo
`Product::where('slug', 'e-cpf')`/`'e-cnpj'` continua sendo comparação por slug fixo — essas 4 páginas + a home só existem porque a Digital Lock vende exatamente esses 2 produtos hoje (rotas `certificado-digital/e-cpf/` e `certificado-digital/e-cnpj/` são estáticas em `routes/web.php`, sem rota genérica). Se um 3º produto for cadastrado no painel com qualquer outro slug, o link "Ver X" na home vai 404 (não existe view/rota pra ele). Corrigir isso exigiria uma página de produto genérica — decisão de escopo pendente com o usuário, não implementada.

## Fallback de preço "não encontrado" deve ser visível, nunca silencioso
Quando a variante não é encontrada, sempre mostrar um placeholder visível (`'R$ [PREÇO]'`), nunca deixar cair em `0`/`null` sem tratamento — `'A partir de R$ 0,00'` parece um preço real e engana o cliente. `home.blade.php` já teve esse bug (`?? 0` no cálculo do preço do card MEI).
