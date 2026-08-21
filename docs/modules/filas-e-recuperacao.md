# Filas e Recuperação

[← Voltar ao índice de módulos](README.md)

## Finalidade

Concentra os pedidos "presos" em algum ponto do processo pós-pagamento e dá
ao time interno as ferramentas para desbloqueá-los: pedidos pagos que o
cliente ainda não preencheu os dados de emissão, e itens que falharam ao
serem enviados à GFSIS. Também roda, em segundo plano, as rotinas
agendadas que tentam se auto-corrigir antes de qualquer ação manual.

- **Rota**: `painel/recuperacao/` (`painel.recuperacao`)
- **Componente**: `resources/views/pages/painel/⚡recuperacao.blade.php`
- **Acesso**: `auth` + `verified` (time interno)
- **Comandos agendados**: `recuperacao:reforco-24h` (a cada hora),
  `gfsis:reconcile-stuck` (a cada hora), `payments:reconcile` (a cada 5
  min — ver [Pagamentos e Estornos](pagamentos-e-estornos.md))

## Funcionalidades

- **Fila de recuperação**: pedidos `paid` + `fulfillment_status =
  awaiting_data`, do mais antigo para o mais novo, com botão "Reenviar
  link" de emissão por pedido.
- **KPI "Recuperados em 7 dias"**: % de pedidos pagos nos últimos 7 dias
  que já saíram de `awaiting_data`.
- **Falhas de integração GFSIS**: itens com `order_item_gfsis.status =
  falha_envio`, com botão "Corrigir e reenviar" por item.
- **Reforço automático por e-mail (24h)**: comando agendado que reenvia o
  link de emissão para pedidos parados há mais de
  `recovery_reinforcement_email_threshold_hours` (`settings`), com
  idempotência — nunca reenvia duas vezes pelo mesmo motivo ao mesmo
  pedido.
- **Sinalização de pedidos travados na GFSIS**: comando agendado que
  loga (sem chamar a GFSIS) itens presos em `enviado_gfsis` além de
  `gfsis_stuck_threshold_hours` (`settings`) — hoje é só observabilidade
  (log estruturado + tabela no terminal), não há ação automática nem tela
  dedicada para esses itens.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Order` | Fila principal (pagos aguardando dados). |
| `OrderItemGfsis` | Falhas de integração e itens travados. |
| `IntegrationQueueJob` (`integration_queue`) | Usada como marca de idempotência do reforço de e-mail (`job = 'recovery_email_24h'`), não como fila de processamento em si. |
| `Setting` | 3 limiares configuráveis: `recovery_reinforcement_email_threshold_hours`, `gfsis_stuck_threshold_hours`, `reconciliation_pending_threshold_minutes`. |

## Principais fluxos

### Reenvio manual (painel)

- "Reenviar link" → `ResendIssuanceAccessLink::execute()`: regenera o
  token de acesso (mesmo TTL de 30 dias do envio original) e reenvia o
  e-mail; se o pedido nunca tiver tido `IssuanceData` gerada (dado
  legado/inconsistente), cria antes de regenerar. O token é sempre
  regenerado mesmo que o envio do e-mail falhe — só o envio é reportado
  como falho.
- "Corrigir e reenviar" → chama `RegisterOrderItemWithGfsis::execute()`
  diretamente (síncrono), a mesma Action usada pelo disparo automático —
  ver [Emissão (GFSIS)](emissao-gfsis.md).

### Reforço automático (24h)

`RecuperacaoEnviarReforcoEmail24h` roda a cada hora: busca pedidos
`paid` + `awaiting_data` com `paid_at` além do limiar, pula os que já têm
a marca de idempotência (`integration_queue.job = 'recovery_email_24h'`
para esse `order_id`), tenta reenviar via `ResendIssuanceAccessLink` e só
marca como enviado em caso de sucesso. Uma falha em um pedido não
interrompe o processamento dos demais na mesma execução.

### Sinalização de travados na GFSIS (hourly)

`GfsisReconcileStuckOrders` identifica `order_item_gfsis` com
`status = enviado_gfsis` cuja `sent_at` ultrapassa o limiar configurado,
registra um log estruturado por item e imprime uma tabela — não existe
endpoint de consulta de pedido documentado na GFSIS, então nenhuma
chamada HTTP é feita.

## Como o usuário interage

Time interno (operação) revisita esta tela recorrentemente para agir sobre
pedidos parados; as rotinas agendadas tentam resolver o caso mais comum
(cliente esqueceu de preencher os dados) automaticamente antes que alguém
precise intervir.

## Regras de negócio importantes

- Um item marcado `send_failed` **continua retentável indefinidamente** —
  não existe um estado "preso até reset manual".
- A idempotência do reforço de e-mail é **por pedido**, reaproveitando a
  tabela `integration_queue` como registro de "já foi feito", não como
  fila de jobs de fato.
- Os 3 limiares de tempo usados neste módulo (e no de Pagamentos) são
  configuráveis via tabela `settings` (grupo `pagamento`/`gfsis`/
  `recuperacao`), não hardcoded — mas hoje só são editáveis diretamente no
  banco, não há tela de configuração para eles (ver
  [Autenticação e Configurações](autenticacao-e-configuracoes.md), que
  cobre apenas dados da conta do usuário, não esses parâmetros de negócio).

## Relação com outros módulos

- **[Emissão (GFSIS)](emissao-gfsis.md)**: este módulo é inteiramente
  reativo aos estados gerados por ele (`awaiting_data`, `send_failed`).
- **[Vendas](vendas.md)**: a ação em massa "Disparar recuperação" da
  listagem de vendas usa a mesma Action desta tela.
- **[Visão geral](visao-geral.md)**: as filas "Pagos sem dados de emissão"
  e "Falha de envio ao GFSIS" do dashboard levam direto para esta tela — as
  únicas 2 das 5 linhas de "Exige ação" com link.
- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: compartilha o
  mesmo padrão de rotina agendada + `Setting` (`payments:reconcile`) e o
  mesmo módulo de configuração de limiares.
