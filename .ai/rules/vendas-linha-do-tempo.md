---
paths:
  - resources/views/pages/painel/vendas/⚡show.blade.php
---

# Detalhe do pedido · Linha do tempo

## Composição da linha do tempo (`timeline()`)
A linha do tempo é montada juntando datas já gravadas em várias tabelas —
não existe uma tabela de "eventos do pedido" dedicada:
- `orders.created_at` → "Pedido criado"
- `orders.paid_at` → "Pagamento autorizado"
- `issuance_data.filled_at` (por item) → "Dados de emissão preenchidos"
- `order_item_gfsis.sent_at` (por item) → "Enviado ao GFSIS"
- `gfsis_events` (por item, via `order_item_gfsis.gfsis_order_id`) → eventos
  subsequentes reais recebidos por webhook do GFSIS.

## Por que `CRIADO`/`ENVIADO` do GFSIS são ignorados
`ApplyGfsisStatusTransition::STATUS_SLUG_MAP` mapeia os status brutos do
GFSIS (`payload['status']`) pros slugs de `gfsis_statuses`. `CRIADO` e
`ENVIADO` mapeiam pro mesmo slug `enviado_gfsis`, que já é representado na
linha do tempo por `order_item_gfsis.sent_at` (o momento em que *nós*
enviamos). Incluir esses dois eventos do webhook duplicaria a mesma
informação com um rótulo confuso. Só `APROVADO`/`EMITIDO`/`RECUSADO`/
`CANCELADO` do log de webhooks (`gfsis_events`) entram na linha do tempo —
qualquer outro status desconhecido é silenciosamente ignorado (`default => null`
no `match`), não vira um evento com texto genérico tipo "Atualização
recebida".

## "Videoconferência realizada" não existe como status separado
Não há nenhum status distinto no GFSIS pra "vídeo realizado" — o mais
próximo é `APROVADO`, cujo texto na linha do tempo já foi ajustado pra
"Aprovado pelo GFSIS (videoconferência validada)" pra manter o sentido do
mock original sem inventar um evento que não existe de verdade.

## "Origem da venda" continua mockado — causa diferente, não confundir
Ao lado da linha do tempo, no mesmo arquivo, o bloco "Origem da venda"
continua com array fixo. Não é o mesmo problema: `order_attribution` e
`ads_conversions` nunca são escritos por nenhum código do checkout hoje —
não tem dado real pra buscar, diferente da linha do tempo, que já tinha o
dado espalhado no banco. Ver `.ai/rules/models.md`.
