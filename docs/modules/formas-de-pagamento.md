# Formas de Pagamento

[← Voltar ao índice de módulos](README.md)

## Finalidade

Configura as formas de pagamento aceitas no checkout (Pix, Cartão de
crédito, Boleto) — nome exibido, desconto e limite de parcelas — e
gerencia os cupons de desconto.

- **Rotas**: `painel/formas-pagamento/` (`painel.formas-pagamento`),
  `painel/formas-pagamento/{id}/` (`.show`),
  `painel/formas-pagamento/cupons/novo/` (`.cupons.create`),
  `painel/formas-pagamento/cupons/{id}/` (`.cupons.show`)
- **Acesso**: `auth` + `verified` (time interno)

## Funcionalidades

- **Listagem de formas de pagamento** com toggle de ativo/inativo.
- **Edição de forma de pagamento**: nome exibido, desconto percentual,
  máximo de parcelas, posição de exibição.
- **Listagem de cupons** com toggle de ativo/inativo.
- **Cadastro/edição de cupom**: código, tipo (percentual ou valor fixo),
  valor, limite de uso total e por cliente, restrição a uma variante de
  produto, vigência (início/fim).

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `PaymentMethod` | Pix/Cartão/Boleto — `slug`, `discount_percentage`, `max_installments`, `is_active`, `position`. |
| `Coupon` | Cupom de desconto. |
| `CouponType` | Lookup: `percentage` / `fixed_amount`. |
| `CouponUse` | Uma linha por uso efetivo de um cupom em um pedido. |
| `ProductVariant` | Alvo opcional de restrição de um cupom (`restricted_variant_id`). |

## Principais fluxos

Ambas as listagens (formas de pagamento e cupons) são simples CRUD com
toggle de status — não há fluxo de aprovação ou efeito colateral além da
gravação direta.

## Como o usuário interage

Time interno configura aqui o que aparece como opção no checkout e os
cupons que podem ser digitados pelo cliente — ver
[Carrinho e Checkout](carrinho-e-checkout.md).

## Regras de negócio importantes

### O `slug` da forma de pagamento é um código interno fixo, não um dado de administração

`App\Support\Safe2Pay\PaymentMethodCode` e o checkout comparam
`payment_methods.slug` diretamente contra os literais `'pix'`/`'cartao'`/
`'boleto'` para decidir o código numérico enviado à Safe2Pay e o
comportamento do formulário. Isso só é seguro **hoje** porque:

- o formulário de edição (`⚡show.blade.php`) **não tem campo `slug`** — só
  `name`, `discount_percentage`, `max_installments`, `position`;
- **não existe tela de "criar forma de pagamento"** — só as 3 linhas do
  `PaymentMethodSeeder` existem.

Se algum dia for construída uma tela de cadastro de forma de pagamento, o
campo `slug` **nunca** pode ser texto livre — precisa ser travado num
`<select>` com os valores que `PaymentMethodCode::fromSlug()` já sabe
tratar, ou a criação de um slug desconhecido precisa ser recusada. Sem
essa trava, o checkout quebraria com `InvalidArgumentException` na hora de
cobrar — uma falha pior que o bug de SKU de produto (que só mostrava um
placeholder, sem impedir a compra). Ver
[`.ai/rules/payment-method-slug.md`](../../.ai/rules/payment-method-slug.md).

### Validação de cupom

`App\Actions\Checkout\ValidateCoupon` checa, nesta ordem: ativo → dentro da
vigência (`starts_at`/`ends_at`) → limite de uso total
(`usage_limit`/`uses_count`) → restrição de variante
(`restricted_variant_id`) → limite por cliente (`per_customer_limit`,
contado em `coupon_uses`). É a mesma Action usada tanto no feedback em
tempo real do checkout quanto na gravação atômica do pedido — a regra
nunca está duplicada.

## Relação com outros módulos

- **[Carrinho e Checkout](carrinho-e-checkout.md)**: consome diretamente
  `payment_methods.is_active`/`discount_percentage`/`max_installments` e
  valida cupons digitados pelo cliente.
- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: `slug` da forma de
  pagamento decide o código numérico enviado à Safe2Pay
  (`PaymentMethodCode::fromSlug()`).
- **[Produtos](produtos.md)**: cupons podem restringir-se a uma
  `ProductVariant` específica.
- **[Relatórios](relatorios.md)**: o relatório "Cupons" e o filtro "Forma
  de pagamento" usam essas mesmas entidades.
