# Produtos

[← Voltar ao índice de módulos](README.md)

## Finalidade

Gerencia o catálogo de Certificados Digitais vendidos (hoje: e-CPF e
e-CNPJ) e suas variantes (formato A1 ou A3), com preço, promoção e
configuração de integração com a GFSIS. Também é a fonte dos dados
exibidos nas páginas públicas de produto.

- **Rotas (painel)**: `painel/produtos/` (`painel.produtos`),
  `painel/produtos/novo/` (`painel.produtos.create`),
  `painel/produtos/{id}/` (`painel.produtos.show`)
- **Rotas (públicas)**: `certificado-digital/`, `certificado-digital/e-cpf/`,
  `certificado-digital/e-cnpj/`, `certificado-digital-para-mei/`, `/` (home)
- **Acesso ao painel**: `auth` + `verified` (time interno)

## Funcionalidades

### Painel administrativo

- **Listagem** de produtos com preço "a partir de" (considera promoção
  ativa) e toggle de ativo/inativo.
- **Cadastro de produto**: nome, slug, tipo de titular (PF/PJ), descrição
  curta, posição de exibição.
- **Edição de produto + gestão de variantes**: cada produto tem no máximo
  uma variante por formato de certificado (A1/A3) — SKU, ID do certificado
  na GFSIS, validade em meses, preço, preço/vigência promocional, variante
  padrão (`is_default`).

### Páginas públicas

- Home e páginas de produto (e-CPF, e-CNPJ, MEI) exibem preço e permitem
  "Comprar" uma variante específica, que vai direto para
  [Carrinho e Checkout](carrinho-e-checkout.md).

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Product` | Produto (e-CPF, e-CNPJ), com `holder_type_id` (PF/PJ) fixo. |
| `ProductVariant` | Combinação produto × formato do certificado — preço, promoção, `gfsis_certificado_id`. |
| `HolderType` | Lookup PF/PJ. |
| `CertificateFormat` | Lookup A1/A3. |

## Principais fluxos

- Toggle de status (`toggleProductStatus`) apenas alterna `is_active` — um
  produto inativo continua existindo (pedidos antigos que o referenciam não
  são afetados, já que `order_items` guarda um snapshot).
- "Definir como padrão" (`setDefaultVariant`) garante que só 1 variante do
  produto tenha `is_default = true` por vez (desmarca as demais antes).
- Preço vigente = `promotional_price` se preenchido e a data atual estiver
  entre `promotion_starts_at`/`promotion_ends_at`; senão, `price`. Essa
  mesma regra é reaplicada de forma independente em pelo menos 3 lugares do
  código (listagem do painel, checkout, `CreateOrderFromCart`).

## Como o usuário interage

- **Time interno**: cadastra/edita produtos e variantes no painel.
- **Cliente**: navega pelas páginas públicas de produto e adiciona uma
  variante ao carrinho.

## Regras de negócio importantes

- **Busca de variante nas páginas públicas é por `certificate_format_id`,
  nunca por SKU**. As páginas de produto (`certificado-digital.blade.php`
  e as demais) buscam a variante A1/A3 assim:
  ```php
  $ecpf?->variants()->whereHas('certificateFormat', fn ($q) => $q->where('slug', 'a1'))->first();
  ```
  Isso é proposital: o campo `sku` no formulário de variante do painel é
  texto livre, sem nenhuma indicação de que precisa bater com um valor
  específico — já houve um bug em produção em que uma variante cadastrada
  com um SKU qualquer nunca era encontrada pelas páginas públicas (caía no
  fallback de preço `R$ [PREÇO]`, visível, nunca em `R$ 0,00` silencioso).
  `certificate_format_id` é um `<select>`, por isso é seguro usá-lo como
  chave de busca — SKU é só um código de referência interno. Ver
  [`.ai/rules/produto-variant-lookup.md`](../../.ai/rules/produto-variant-lookup.md).
- **Produto público ainda é identificado por slug fixo**: `Product::where('slug',
  'e-cpf')`/`'e-cnpj'` está hardcoded nas 4 páginas de produto + home,
  porque hoje só existem esses 2 produtos. Cadastrar um 3º produto no
  painel com outro slug não gera automaticamente uma página pública para
  ele — o link "Ver X" na home resultaria em 404.
- **Unicidade de variante por formato**: `product_variants` tem uma
  constraint única em `(product_id, certificate_format_id)` — o formulário
  do painel trata a violação dessa constraint como erro de validação
  amigável ("Este produto já possui uma variante com este formato"), não
  como erro 500.

## Relação com outros módulos

- **[Carrinho e Checkout](carrinho-e-checkout.md)**: toda compra parte de
  uma `ProductVariant` cadastrada aqui.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: `gfsis_certificado_id`,
  configurado por variante, é pré-condição para o registro na GFSIS
  funcionar — sem ele, o registro é bloqueado com erro explícito.
- **[Vendas](vendas.md)** e **[Relatórios](relatorios.md)**: o filtro
  "Produto" das duas telas usa `product_id`.
- **[Formas de Pagamento](formas-de-pagamento.md)**: cupons podem ser
  restritos a uma `ProductVariant` específica cadastrada aqui.
