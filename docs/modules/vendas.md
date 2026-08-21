# Vendas

[← Voltar ao índice de módulos](README.md)

## Finalidade

Tela do painel administrativo para o time interno acompanhar e gerenciar
todos os pedidos: listar, filtrar, exportar e ver o detalhe completo de um
pedido — status de pagamento, status de emissão e uma linha do tempo real
dos eventos.

- **Rotas**: `painel/vendas/` (`painel.vendas.index`),
  `painel/vendas/{id}/` (`painel.vendas.show`)
- **Componentes**: `resources/views/pages/painel/⚡vendas.blade.php`,
  `resources/views/pages/painel/vendas/⚡show.blade.php`
- **Acesso**: `auth` + `verified` (time interno)

## Funcionalidades

- **Listagem paginada** (25 por página) com filtros: status de pagamento,
  status de emissão, forma de pagamento, produto, período (7/30/90 dias) e
  busca por número do pedido/nome/documento do cliente.
- **Exportação CSV** da listagem filtrada.
- **Ações em massa** sobre o resultado filtrado:
  - "Reenviar ao GFSIS" — dispara `RegisterOrderItemWithGfsisJob` para
    cada pedido do filtro.
  - "Disparar recuperação" — reenvia o link de emissão
    (`ResendIssuanceAccessLink`) para pedidos pagos aguardando dados.
- **Detalhe do pedido** (`painel/vendas/{id}/`): dados do cliente, itens,
  pagamento mais recente, e uma **linha do tempo** com os eventos reais do
  pedido.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Order` | Pedido em si — status, valores, cliente. |
| `OrderItem` | Item(ns) do pedido (snapshot do produto no momento da compra). |
| `Payment` | Pagamento mais recente exibido no detalhe. |
| `IssuanceData` / `OrderItemGfsis` / `GfsisEvent` | Fontes da linha do tempo — ver [Emissão (GFSIS)](emissao-gfsis.md). |

## Principais fluxos

### Listagem e filtros

O filtro "status de pagamento" olha o **pagamento mais recente** de cada
pedido (subquery `max(id)` por `order_id`), não qualquer pagamento — um
pedido com 2 tentativas de cobrança é filtrado pela última.

### Linha do tempo do detalhe do pedido

Não existe uma tabela dedicada de "eventos do pedido" — a linha do tempo é
montada juntando datas já gravadas em várias tabelas, na ordem cronológica:

1. `orders.created_at` → "Pedido criado"
2. `orders.paid_at` → "Pagamento autorizado"
3. `issuance_data.filled_at` (por item) → "Dados de emissão preenchidos"
4. `order_item_gfsis.sent_at` (por item) → "Enviado ao GFSIS"
5. `gfsis_events` (por item) → eventos subsequentes reais do webhook GFSIS
   (`APROVADO` → "Aprovado pelo GFSIS (videoconferência validada)",
   `EMITIDO` → "Certificado emitido", `RECUSADO`, `CANCELADO`)

Ver as regras completas de composição em [Emissão (GFSIS)](emissao-gfsis.md).

### "Origem da venda" — mockado, não implementado

Ao lado da linha do tempo, o bloco "Origem da venda" no detalhe do pedido
usa um array fixo — **não é dado real**. A tabela `order_attribution`
(UTM/gclid/landing page) existe no schema, mas nenhum código do checkout
grava nela hoje; fica sempre vazia. Implementar de verdade exigiria
capturar UTM/gclid no checkout e persistir na criação do pedido. Pelo
mesmo motivo, o filtro "Origem" **não existe** na listagem de vendas — foi
deliberadamente deixado fora de escopo até essa captura existir.

## Como o usuário interage

Time interno (operação/financeiro) usa esta tela no dia a dia para
localizar um pedido específico, entender em que ponto do processo ele está
e agir (reenviar e-mail, reenviar ao GFSIS, abrir estorno — ver
[Pagamentos e Estornos](pagamentos-e-estornos.md)).

## Regras de negócio importantes

- Ver a nota acima sobre **"status de pagamento" filtrar pela última
  tentativa**, não por qualquer pagamento do pedido.
- Ver [`.ai/rules/vendas-linha-do-tempo.md`](../../.ai/rules/vendas-linha-do-tempo.md)
  para o detalhamento completo de por que `CRIADO`/`ENVIADO` do GFSIS são
  ignorados na linha do tempo.

## Relação com outros módulos

- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: o pagamento
  exibido, e as ações de estorno disponíveis no detalhe do pedido, vivem
  nesse módulo.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: fonte de quase toda a linha do
  tempo e da ação "Reenviar ao GFSIS".
- **[Filas e Recuperação](filas-e-recuperacao.md)**: a ação "Disparar
  recuperação" desta tela usa a mesma Action
  (`ResendIssuanceAccessLink`) que a fila de recuperação.
- **[Visão geral](visao-geral.md)** e **[Relatórios](relatorios.md)**:
  consomem os mesmos dados de `orders`/`payments` agregados de formas
  diferentes.
- **[Produtos](produtos.md)**: o filtro "Produto" da listagem usa
  `product_id` via `items.productVariant`.
