# Visão geral (dashboard do painel)

[← Voltar ao índice de módulos](README.md)

## Finalidade

Página inicial do painel administrativo (`painel/`). Dá ao time interno um
retrato rápido do funil de vendas — do pedido criado até o certificado
emitido — e destaca, em uma única tabela, quais filas exigem ação humana
agora.

- **Rota**: `GET painel/` → `painel.visao-geral`
- **Componente**: `resources/views/pages/painel/⚡visao-geral.blade.php`
- **Acesso**: `auth` + `verified` (guard `web`, time interno — ver
  [Autenticação e Configurações](autenticacao-e-configuracoes.md))

## Funcionalidades

- **KPIs**: pedidos criados, pedidos pagos, faturamento, ticket médio e taxa
  de conversão (pagos ÷ criados).
- **Funil operacional** (5 estágios, com conversão passo a passo):
  Pedidos criados → Pagos → Dados completos → Enviados ao GFSIS → Emitidos.
- **Tabela "Exige ação"**: 5 filas com quantidade e "há quanto tempo está
  parado":
  1. Pagos sem dados de emissão
  2. Falha de envio ao GFSIS
  3. Conversões não enviadas (Google Ads)
  4. Reembolsos pendentes
  5. Certificados vencendo em 30 dias
- **"Vendas por dia"**: bloco estático/placeholder — ainda não há lib de
  gráfico instalada no projeto.

## Entidades envolvidas

| Model | Uso nesta tela |
| --- | --- |
| `Order` | Contagens de criados/pagos, faturamento, funil, fila "pagos sem dados". |
| `OrderItemGfsis` | Fila "falha de envio ao GFSIS", fila "certificados vencendo", estágios "enviados"/"emitidos" do funil. |
| `AdsConversion` | Fila "conversões não enviadas" (`status.slug` em `pending`/`failed`). |
| `Refund` | Fila "reembolsos pendentes" (`completed_at IS NULL`). |

## Principais fluxos

Todas as métricas são **computadas a cada carregamento da página**
(propriedades `#[Computed]` do Livewire), direto do banco — não há
cache nem tabela de agregação. Não existe nenhuma escrita nesta tela: é
100% leitura.

Somente 2 das 5 linhas da tabela "Exige ação" têm botão "Abrir" com link —
ambas apontam para [Filas e Recuperação](filas-e-recuperacao.md)
(`painel.recuperacao`), a única tela de destino que já existe hoje. As
outras 3 (conversões, reembolsos, certificados vencendo) ainda não têm tela
dedicada.

## Como o usuário interage

Somente leitura: o operador abre `painel/` para ter uma visão geral e, a
partir daí, navega para a fila de recuperação quando algo precisa de ação.

## Regras de negócio importantes

Estas regras vêm de `.ai/rules/painel-visao-geral.md` e são fáceis de
interpretar errado ao olhar só o nome dos campos:

- **"Pagos" e faturamento usam `orders.paid_at IS NOT NULL`**, não
  `status.slug = 'paid'`. Um pedido pago e depois reembolsado/cancelado
  continua contando como "chegou a esse estágio" — é uma semântica
  histórica do funil, não o status atual do pedido.
- **"Enviados ao GFSIS" e "Emitidos" vêm do status do item**
  (`OrderItem::gfsis()->status->slug`), não do `orders.fulfillment_status` —
  o fulfillment status do pedido não tem estágio "emitido", só chega até
  `sent_to_gfsis`.
- **"Dados completos" inclui `send_failed`** — o envio ao GFSIS só é
  tentado depois que os dados já estavam completos, então uma falha de
  envio não significa "dados incompletos".
- Os percentuais do funil são **conversão passo a passo** (estágio atual ÷
  estágio anterior), não cumulativos desde "Pedidos criados".

## Relação com outros módulos

- **[Vendas](vendas.md)**: toda métrica de pedido/faturamento vem da mesma
  tabela `orders` gerenciada pelo módulo de Vendas.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: os estágios "Enviados ao
  GFSIS"/"Emitidos" e a fila de certificados vencendo dependem
  diretamente de `order_item_gfsis`, escrita por esse módulo.
- **[Filas e Recuperação](filas-e-recuperacao.md)**: é o destino de 2 das 5
  filas de "Exige ação", e reutiliza exatamente a mesma consulta de "pagos
  sem dados de emissão".
- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: a fila de
  "reembolsos pendentes" lê `refunds`, escrita por esse módulo.
- **[Relatórios](relatorios.md)**: o relatório "Funil operacional" (ver
  [relatorios.md](relatorios.md)) reproduz deliberadamente a mesma lógica
  desta tela, só que filtrável por período/produto/forma de pagamento/origem.
