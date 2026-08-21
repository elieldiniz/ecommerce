# Carrinho e Checkout

[← Voltar ao índice de módulos](README.md)

## Finalidade

Cobre a jornada pública de compra: adicionar um Certificado Digital ao
carrinho, revisar o carrinho e finalizar a compra escolhendo forma de
pagamento (Pix, Cartão de crédito ou Boleto).

- **Rotas**: `carrinho/`, `carrinho/adicionar/{productVariantId}` (`cart.add`),
  `checkout/`, `checkout/tokenizar-cartao`, `pedido/{id}/pagamento/`
- **Componentes**: `resources/views/pages/⚡carrinho.blade.php`,
  `resources/views/pages/⚡checkout.blade.php`,
  `resources/views/pages/pedido/⚡pagamento.blade.php`
- **Acesso**: público (visitante ou cliente autenticado via guard
  `customer` — ver [Clientes](clientes.md))

## Funcionalidades

- **Adicionar ao carrinho**: botões "Comprar" nas páginas de produto
  (ver [Produtos](produtos.md)) apontam para `cart.add/{productVariantId}`,
  que soma a quantidade se a variante já estiver no carrinho.
- **Página do carrinho**: lista itens, subtotal/total e permite remover
  item; some automaticamente quando o último item é removido (o `Cart`
  vazio é apagado).
- **Checkout**: formulário único (dados do titular PF/PJ, endereço,
  cupom, forma de pagamento) que cobra a compra na Safe2Pay — ver detalhes
  de cobrança em [Pagamentos e Estornos](pagamentos-e-estornos.md).
  - Seleção de forma de pagamento recalcula subtotal/desconto/total sem
    reload de página.
  - Cartão de crédito mostra simulação de parcelas em tempo real
    (consulta à Safe2Pay) e nunca fica pré-selecionado se essa consulta
    estiver falhando.
  - Cupom de desconto aplicável por código.
- **Tela de pagamento** (`pedido/{id}/pagamento/`): mostra o status do
  pagamento mais recente do pedido (QR Pix, linha digitável do Boleto,
  etc.) e permite "Tentar novamente" após expiração, criando uma nova
  tentativa de cobrança sem reaproveitar o QR/boleto anterior.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Cart` / `CartItem` | Carrinho persistido — por `customer_id` (cliente logado) ou `session_id` (visitante); único por cliente/sessão. |
| `ProductVariant` | O que é adicionado ao carrinho e, no checkout, o que é comprado. |
| `Customer` / `CustomerAddress` | Criados/atualizados (`updateOrCreate`) no momento de "Finalizar compra". |
| `Coupon` | Validado e aplicado ao pedido, se um código for informado. |
| `Order` / `OrderItem` | Criados por `CreateOrderFromCart` como snapshot imutável da compra. |
| `Payment` | Criado pela Action de cobrança correspondente à forma escolhida. |

## Principais fluxos

1. `carrinho/adicionar/{id}` → `App\Actions\Cart\AddToCart` cria (se
   necessário) o `Cart` do visitante/cliente e o `CartItem`, depois
   redireciona para `carrinho/`.
2. Na página do carrinho, "Continuar" redireciona para `checkout/` (exige
   login como cliente antes; visitante é mandado para o login).
3. Em `checkout/`, ao clicar "Finalizar compra"
   (`finalizarCompra()`): valida os dados do titular/endereço → grava/
   atualiza `Customer` e `CustomerAddress` (idempotente por `document`) →
   cria o `Order` via `App\Actions\Checkout\CreateOrderFromCart` (ou
   reaproveita um `Order` `awaiting_payment` já existente desta mesma
   sessão de checkout, se a forma de pagamento mudou) → dispara a Action
   de cobrança da forma escolhida (`ChargePixPayment` /
   `ChargeCardPayment` / `ChargeBoletoPayment`) → redireciona para
   `pedido/{id}/pagamento/`.
4. `pedido/{id}/pagamento/` faz polling do `Payment` mais recente do
   pedido até o status mudar (confirmação chega de forma assíncrona via
   webhook — ver [Pagamentos e Estornos](pagamentos-e-estornos.md)).

## Como o usuário interage

Fluxo 100% self-service do cliente final, sem nenhuma ação do painel
administrativo no caminho feliz. A única exceção é quando algo trava depois
do pagamento (ver [Filas e Recuperação](filas-e-recuperacao.md)).

## Regras de negócio importantes

- **Guarda de divergência de valor**: toda cobrança (Pix/Cartão/Boleto)
  recalcula o total no servidor via `RecalculateOrderTotals` e **recusa a
  cobrança antes de qualquer chamada HTTP à Safe2Pay** se o valor enviado
  pelo front (`confirmedTotal`) não bater com o recalculado — trava contra
  manipulação client-side do preço.
- **Cupom de desconto**: validado por `App\Actions\Checkout\ValidateCoupon`
  (ativo, dentro da vigência, limite de uso total/por cliente, restrição a
  uma variante específica) — a mesma regra roda tanto no feedback em tempo
  real do checkout quanto na gravação atômica do pedido.
- **Snapshot imutável do pedido**: `CreateOrderFromCart` grava `sku_snapshot`,
  `name_snapshot` e `list_price_snapshot` em `order_items` a partir da
  variante **antes** de qualquer chamada de rede — o pedido nunca muda
  retroativamente se o produto for editado depois.
- **Preço promocional**: se `product_variants.promotional_price` estiver
  preenchido e a data atual estiver dentro de
  `promotion_starts_at`/`promotion_ends_at`, esse é o preço cobrado; senão,
  `price`.

### Carrinho e Checkout não estão conectados

Achado relevante ao ler o código: **a tela de checkout não lê o `Cart`
persistido**. `⚡checkout.blade.php::mount()` recebe a variante via query
string `?variant=` (usada pelos botões "Comprar" quando apontam
diretamente para o checkout — hoje nenhum aponta) ou, na ausência dela, cai
no fallback "primeira variante ativa por `id`". O botão "Continuar" da
página do carrinho (`⚡carrinho.blade.php::continuar()`) faz
`redirectRoute('checkout')` **sem nenhum parâmetro**, e os botões "Comprar"
das páginas de produto (ver [Produtos](produtos.md)) sempre apontam para
`cart.add/{id}` (adicionar ao carrinho), nunca para o checkout diretamente.

Na prática, hoje o checkout sempre cobra **1 unidade** de uma variante —
seja ela a primeira variante ativa do catálogo (fallback), seja uma passada
manualmente por `?variant=`. O `Cart`/`CartItem` multi-item existe, é
populado e exibido corretamente na página `/carrinho`, mas nada no
checkout lê o que está nele. `App\Actions\Checkout\CreateOrderFromCart`
já aceita uma coleção de itens (preparado para múltiplos), mas o único
chamador (`⚡checkout.blade.php`) sempre passa uma coleção de 1 item.

## Relação com outros módulos

- **[Produtos](produtos.md)**: as variantes compradas vêm do catálogo;
  preço e promoção ativa são lidos direto de `product_variants`.
- **[Formas de Pagamento](formas-de-pagamento.md)**: a lista de formas
  disponíveis no checkout, seus descontos (`discount_percentage`) e limite
  de parcelas (`max_installments`) vêm de `payment_methods`, cadastradas
  nesse módulo.
- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: toda cobrança
  efetiva (Pix/Cartão/Boleto) é delegada às Actions desse módulo.
- **[Clientes](clientes.md)**: o checkout cria/atualiza o `Customer` e é o
  único ponto de cadastro; ao fazer login (`pages::auth.customer.login`), o
  carrinho de sessão é mesclado no carrinho do cliente via
  `Cart::getOrCreateForCustomer($customer)->mergeFromSession($sessionCartId)`,
  chamado diretamente no componente de login. `App\Actions\Cart\SyncCartOnLogin`
  encapsula essa mesma lógica como Action, mas não é chamada de nenhum lugar
  do código hoje — a lógica ficou duplicada inline no login em vez de
  reaproveitar a Action.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: começa a existir só depois que
  o pagamento é confirmado (fora do escopo direto deste módulo).
