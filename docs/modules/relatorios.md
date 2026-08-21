# Relatórios

[← Voltar ao índice de módulos](README.md)

## Finalidade

Central de relatórios operacionais e financeiros do painel — mesmos dados
de [Vendas](vendas.md)/[Visão geral](visao-geral.md), recortados de formas
diferentes conforme a pergunta que o time interno precisa responder.

- **Rota**: `painel/relatorios/` (`painel.relatorios`)
- **Componente**: `resources/views/pages/painel/⚡relatorios.blade.php`
- **Acesso**: `auth` + `verified` (time interno)

## Funcionalidades

9 relatórios, selecionáveis por abas, todos filtráveis por **período**
(7/30/90 dias), **produto**, **forma de pagamento** e **origem** (quando
aplicável), com **exportação CSV** individual por relatório:

| Relatório | O que mostra |
| --- | --- |
| Vendas por período | Faturamento, pedidos, ticket médio e desconto por dia. |
| Vendas por produto | Comparativo de quantidade/faturamento/ticket médio entre e-CPF e e-CNPJ. |
| Funil operacional | Mesmos 5 estágios da [Visão geral](visao-geral.md#funcionalidades), mas filtrável. |
| Pagos sem dados | Pedidos pagos que ainda não preencheram os dados de emissão. |
| Base de renovação | Certificados com `certificate_expires_at` nos próximos 30 dias. |
| Atribuição | Pedidos pagos agrupados por origem (UTM). |
| Conciliação do gateway | Aba presente na navegação, mas sem implementação de dados própria hoje. |
| Estornos | Pedidos com reembolso solicitado no período, motivo e valor. |
| Cupons | Uso e desconto total por cupom. |

## Entidades envolvidas

`Order`, `OrderItem`, `OrderItemGfsis`, `OrderAttribution`, `Refund`,
`CouponUse`, `PaymentMethod`, `Product` — os mesmos Models usados por
[Vendas](vendas.md) e [Visão geral](visao-geral.md), aqui combinados com
mais filtros.

## Principais fluxos

- **Filtro "Origem"** lê valores distintos de `order_attribution.utm_source`
  já existentes no banco (`origemOptions()`), populando o `<select>`
  dinamicamente.
- **Funil operacional filtrado** reproduz deliberadamente a mesma
  semântica do funil da [Visão geral](visao-geral.md) — "Pagos" por
  `paid_at`, "Enviados"/"Emitidos" via `items.gfsis.status`, "Dados
  completos" incluindo `send_failed`, percentuais passo a passo — só que
  escopada pelos filtros ativos da tela.
- **Estornos e Cupons** não têm relação direta com `Order` (via
  `payment.order` e `order`, respectivamente) — os mesmos 5 filtros de
  período/produto/forma de pagamento/origem/busca são replicados via
  `whereHas` aninhado em vez de extraídos para uma função compartilhada
  (decisão deliberada de simplicidade, documentada no próprio código).
- **Exportar CSV** gera o arquivo do relatório atualmente ativo
  (`exportarCsv(string $reportKey)`), cada um com seu próprio cabeçalho de
  colunas.

## Como o usuário interage

Time interno seleciona um relatório, ajusta os filtros e opcionalmente
exporta em CSV para uso externo (planilhas, apresentações).

## Regras de negócio importantes

- **"Atribuição" está sempre praticamente vazia na prática**: como nenhum
  código do checkout grava em `order_attribution` hoje (ver
  [Vendas](vendas.md#origem-da-venda--mockado-não-implementado)), 100% dos
  pedidos caem no grupo "Direto/Não identificado" — **comportamento
  esperado**, não um bug do relatório em si.
- Mesma regra de composição do funil operacional da
  [Visão geral](visao-geral.md#regras-de-negócio-importantes) se aplica
  aqui, só que parametrizada pelos filtros.
- O relatório de **Cupons** não tem filtro de busca textual — é
  intencionalmente um relatório agregado por cupom.

## Relação com outros módulos

- **[Vendas](vendas.md)** e **[Visão geral](visao-geral.md)**: fonte dos
  mesmos dados de pedido, recortados de forma diferente.
- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: relatório
  "Estornos" lê `refunds`.
- **[Formas de Pagamento](formas-de-pagamento.md)**: relatório "Cupons" lê
  `coupon_uses`/`coupons`; filtro "Forma de pagamento" usa
  `payment_methods`.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: relatório "Base de renovação" lê
  `order_item_gfsis.certificate_expires_at`.
- **[Produtos](produtos.md)**: filtro/relatório "Produto" usa `products`.
