---
paths:
  - resources/views/pages/painel/⚡visao-geral.blade.php
---

# Painel · Visão geral

## Semântica das contagens do funil e das filas
- "Pagos" e o `sum(total)`/`avg(total)` de faturamento usam `orders.paid_at IS NOT NULL`, não `status.slug = 'paid'` — um pedido pago e depois reembolsado/cancelado continua contando como "chegou a esse estágio" no funil (semântica histórica, não status atual).
- "Enviados ao GFSIS" e "Emitidos" no funil vêm do status do **item** (`OrderItem::gfsis()->status->slug` em `['enviado_gfsis','aprovado','emitido']` / `'emitido'`), não do `orders.fulfillment_status` do pedido — o `fulfillment_status` do pedido não tem estágio "emitido", só chega até `sent_to_gfsis`.
- "Dados completos" inclui `fulfillment_status.slug` em `['data_complete','sent_to_gfsis','send_failed']` — `send_failed` conta porque o envio ao GFSIS só é tentado depois que os dados já estavam completos.
- Percentuais do funil são conversão passo-a-passo (estágio atual / estágio anterior), não cumulativos desde "Pedidos criados".
- O botão "Abrir" da tabela "Exige ação" só tem link nas 2 primeiras filas (Pagos sem dados de emissão, Falha de envio ao GFSIS) — ambas apontam para `painel.recuperacao`, a única tela de destino que já existe. As outras 3 filas (Conversões não enviadas, Reembolsos pendentes, Certificados vencendo em 30 dias) não têm tela dedicada ainda; o botão fica sem link até essas telas serem construídas.
- O bloco "Vendas por dia" continua estático (placeholder) — fica de fora até uma lib de gráfico ser escolhida e instalada no projeto.
