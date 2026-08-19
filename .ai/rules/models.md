---
paths:
  - app/Models/OrderAttribution.php
---

# Models

## order_attribution existe mas nada grava nele ainda
Model/tabela `order_attribution` (utm_source/medium/campaign/term/content, gclid, landing_page, referrer, device_type_id, sessions_before_purchase) já existe no schema, mas nenhum código do checkout captura esses dados hoje — a tabela fica sempre vazia. Por isso: (1) o bloco "Origem da venda" em `painel/vendas/{id}/` continua mockado (decisão deliberada da feature `painel-vendas-dados-reais`); (2) o filtro "Origem" em `painel/vendas/` (lista) ficou fora de escopo da feature `painel-vendas-filtros-reais` pelo mesmo motivo. Para implementar de verdade no futuro: capturar UTM/gclid no checkout (`⚡checkout.blade.php`) e persistir em `order_attribution` na criação do pedido, só então os dois blocos acima fazem sentido virar reais.
