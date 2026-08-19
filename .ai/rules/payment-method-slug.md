---
paths:
  - resources/views/pages/painel/formas-pagamento/⚡show.blade.php
  - app/Support/Safe2Pay/PaymentMethodCode.php
---

# Forma de pagamento · slug é código interno fixo, não dado de admin

`app/Support/Safe2Pay/PaymentMethodCode.php` e `⚡checkout.blade.php` comparam
`payment_methods.slug` direto contra os literais `'pix'`/`'cartao'`/`'boleto'`
pra decidir o código numérico da Safe2Pay e o comportamento do checkout. Isso
é seguro **hoje** só porque não existe nenhum jeito de um admin criar ou
renomear esse slug: `⚡show.blade.php` (edição de forma de pagamento) não tem
campo `slug` no formulário (só `name`, `discount_percentage`,
`max_installments`, `position`), e não existe rota/tela de "criar forma de
pagamento" — só as 3 linhas do `PaymentMethodSeeder` existem.

**Se algum dia for construída uma tela de "cadastrar forma de pagamento"**:
nunca deixar `slug` como texto livre. Trave num `<select>` com os valores que
`PaymentMethodCode::fromSlug()` já sabe tratar (`pix`/`cartao`/`boleto`), ou
recuse a criação de formas de pagamento com slug desconhecido — sem gateway
de cobrança real por trás, checkout quebraria com
`InvalidArgumentException` na hora de cobrar (pior que o bug de SKU de
produto, que só mostrava um placeholder — aqui a compra falha de verdade).

Mesmo raciocínio de "campo texto livre sem constraint quebra lookup fixo em
outro lugar do código" que já causou o bug de SKU em `produto-variant-lookup.md`.
