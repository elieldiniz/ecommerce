# Digital Lock E-commerce — Database Schema

<!-- inputs: project-description.md@sha256:cb6009f4181e user-stories.md@sha256:5fb32d8adb92 -->

## Overview

Este schema é a tradução 1:1 do documento de referência do negócio (`Digital Lock | Estrutura de Banco de
Dados, v1.0`, 05/08/2026, anexado nesta sessão) para DBML — a fonte de verdade é exclusivamente esse documento,
não `project-description.md`/`user-stories.md`. Ele decide formalmente o **gateway de pagamento (Safe2Pay)** e
nomeia o **sistema de emissão (GFSIS)**. O modelo gira em torno de dois eixos: **catálogo e venda**
(`products`, `product_variants`, `customers`, `orders`, `order_items`, `payments`, `refunds`, `coupons`) e
**emissão do certificado via GFSIS** (`issuance_data`, `order_item_gfsis`, `gfsis_events`). O princípio central
do documento é que **um pedido tem dois ciclos de vida independentes**: o ciclo financeiro vive em
`orders.status_id`, o ciclo operacional (emissão) vive em `order_item_gfsis.status_id`, com um resumo
denormalizado em `orders.fulfillment_status_id` só para filtro rápido no painel — os dois nunca são unificados
num único campo.

Convenções em vigor: stack detectada é **Laravel 13 / Eloquent** — tabelas no plural snake_case, chave primária
`id bigint` auto-incremento (exceto `order_attribution`, extensão 1:1 de `orders` com chave primária própria,
conforme o documento de referência), chaves estrangeiras `<singular>_id`, `created_at`/`updated_at` em toda
tabela de domínio (tabelas de log só têm `created_at`, conforme convenção do documento de referência). Todo
campo categórico do documento de referência (status, tipo, papel, motivo, gateway, dispositivo) foi convertido
de `varchar` solto para **tabela de lookup com FK própria** — nenhuma tabela usa coluna enum ou string solta
para representar um conjunto fechado de valores. Identificadores de tabela e coluna estão em **inglês**, por
decisão tomada nesta sessão (ver Notes & Conventions); cada tabela cuja origem é o documento de referência traz
o nome original em português na sua `Note`, para rastreabilidade completa. **Nenhuma tabela usa `deleted_at`**:
o documento de referência declara que exclusão de registro histórico não é permitida — pedido, pagamento e
emissão são inativados por status, nunca apagados, e essa convenção foi estendida a todo o schema, inclusive
`users` (não há soft delete implementado no model `User` atual do projeto).

## Schema (DBML)

```dbml
// ============================================================
// Lookup tables
// ============================================================

Table holder_types {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: tipo_titular / tipo_pessoa (PF ou PJ). Compartilhada entre products, customers e issuance_data — é o mesmo conceito nos três contextos, não uma máquina de estado própria por tabela.'
}

Table certificate_formats {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  requires_hardware boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: tipo_certificado (A1 ou A3), em product_variantes.'
}

Table payment_methods {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  discount_percentage decimal(5,2) [not null, default: 0, note: 'CHECK (discount_percentage >= 0)']
  max_installments smallint [not null, default: 1]
  is_active boolean [not null, default: true]
  position smallint [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: formas_pagamento (pix, cartao, boleto). Já é, por natureza, uma tabela de lookup — reutilizada como FK em orders (forma escolhida) e payments (método da tentativa).'
}

Table payment_gateways {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: campo gateway em pagamentos ("Nome do gateway. Valor atual: safe2pay"). Modelado como lookup porque o campo existe para múltiplos gateways no futuro, mesmo com um único valor hoje.'
}

Table order_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: pedidos.status — ciclo financeiro do pedido.'
}

Table order_fulfillment_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: pedidos.status_emissao — resumo denormalizado do ciclo operacional, recalculado a partir de order_item_gfsis.status_id conforme a regra do capítulo 9.3 do documento de referência.'
}

Table gfsis_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: order_item_gfsis.status_gfsis — "o status real da emissão". Estrutura de lookup criada por regra da casa (nenhuma coluna categórica solta), mas os valores não são enumerados no documento de referência (vêm do sistema externo GFSIS) — ver Open Questions.'
}

Table payment_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  weight smallint [not null, note: 'Substitui payments.peso_status e payment_events.peso_status do documento de referência: a ordem lógica do status vive uma única vez aqui, não duplicada em cada linha.']
  created_at timestamp
  updated_at timestamp

  Note: 'docx: payments.status. Regra de aplicação (documento de referência, capítulo 6): uma transição só é gravada se o weight do status recebido for maior que o weight atual do pagamento — nunca regredir de autorizado para pendente.'
}

Table refund_reasons {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: reembolsos.motivo (arrependimento, garantia, duplicidade, chargeback, outro).'
}

Table coupon_types {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: cupons.tipo (percentual ou valor).'
}

Table device_types {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: pedido_atribuicao.dispositivo (desktop, mobile, tablet).'
}

Table ads_conversion_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: conversoes_ads.status (pendente, enviado, falha).'
}

Table queue_job_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: integracao_fila.status (pendente, processando, concluido, falha).'
}

Table roles {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: usuarios.papel (admin, operacao, financeiro, suporte) — papéis do painel administrativo, não de clientes.'
}

// ============================================================
// Catalog
// ============================================================

Table products {
  id bigint [pk, increment]
  slug varchar(120) [unique, not null]
  name varchar(120) [not null]
  holder_type_id bigint [ref: > holder_types.id, not null]
  short_description varchar(255) [null]
  is_active boolean [not null, default: true]
  position smallint [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  Indexes {
    holder_type_id
    is_active
  }

  Note: 'docx: produtos. Agrupador comercial (e-CPF, e-CNPJ), não o item vendável.'
}

Table product_variants {
  id bigint [pk, increment]
  product_id bigint [ref: > products.id, not null]
  certificate_format_id bigint [ref: > certificate_formats.id, not null]
  sku varchar(40) [unique, not null]
  validity_months smallint [not null]
  price decimal(10,2) [not null, note: 'CHECK (price >= 0)']
  promotional_price decimal(10,2) [null, note: 'CHECK (promotional_price >= 0)']
  promotion_starts_at datetime [null]
  promotion_ends_at datetime [null]
  is_active boolean [not null, default: true]
  is_default boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp

  Indexes {
    product_id
    (product_id, certificate_format_id) [unique]
  }

  Note: 'docx: produto_variantes. Preço vigente é promotional_price quando não nulo e a data atual está dentro de [promotion_starts_at, promotion_ends_at]; caso contrário é price (regra de aplicação, documento de referência capítulo 2). is_default sinaliza a variante pré-selecionada (ex.: A1 na página MEI). Identificador numérico exigido pelo GFSIS na criação de pedido não está modelado aqui — ver Open Questions (Pendência 1 do documento de referência).'
}

// ============================================================
// Customers
// ============================================================

Table customers {
  id bigint [pk, increment]
  holder_type_id bigint [ref: > holder_types.id, not null]
  legal_name varchar(180) [not null]
  document varchar(14) [unique, not null]
  email varchar(180) [unique, not null]
  phone varchar(20) [not null]
  password_hash varchar(255) [null]
  email_verified_at timestamp [null]
  terms_accepted_at timestamp [not null]
  marketing_opt_in boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: clientes. Quem compra e é faturado — não necessariamente o titular do certificado. Tabela distinta de users: users (Fortify, já existente no projeto) é a equipe do painel administrativo; customers são os compradores da loja, com login opcional (password_hash aceita nulo porque o checkout da primeira fase não exige criação de senha, conforme documento de referência).'
}

Table customer_addresses {
  id bigint [pk, increment]
  customer_id bigint [ref: > customers.id, not null]
  postal_code varchar(8) [not null]
  street varchar(180) [not null]
  number varchar(20) [not null]
  complement varchar(120) [null]
  neighborhood varchar(120) [not null]
  city varchar(120) [not null]
  state char(2) [not null]
  ibge_code varchar(7) [null]
  is_primary boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Indexes {
    customer_id
  }

  Note: 'docx: cliente_enderecos. postal_code (CEP) só dígitos, 8 caracteres; ibge_code é alternativa aceita pelo GFSIS no lugar de city+state, conforme documento de referência.'
}

// ============================================================
// Orders
// ============================================================

Table orders {
  id bigint [pk, increment]
  number varchar(20) [unique, not null]
  customer_id bigint [ref: > customers.id, not null]
  status_id bigint [ref: > order_statuses.id, not null]
  fulfillment_status_id bigint [ref: > order_fulfillment_statuses.id, not null]
  payment_method_id bigint [ref: > payment_methods.id, not null]
  coupon_id bigint [ref: > coupons.id, null]
  subtotal decimal(10,2) [not null, note: 'CHECK (subtotal >= 0)']
  coupon_discount decimal(10,2) [not null, default: 0, note: 'CHECK (coupon_discount >= 0)']
  payment_method_discount decimal(10,2) [not null, default: 0, note: 'CHECK (payment_method_discount >= 0)']
  total decimal(10,2) [not null, note: 'CHECK (total >= 0)']
  ip_address varchar(45) [not null]
  user_agent varchar(255) [not null]
  paid_at timestamp [null]
  cancelled_at timestamp [null]
  internal_notes text [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
    fulfillment_status_id
    paid_at
    (customer_id, status_id)
  }

  Note: 'docx: pedidos. status_id e fulfillment_status_id são os dois ciclos de vida independentes descritos no princípio central do documento de referência — nunca unificados. Sem carrinho multi-item: cada order agrega vários order_items (o "carrinho" em si é o status inicial "cart" em order_statuses, não uma tabela separada).'
}

Table order_items {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, not null]
  product_variant_id bigint [ref: > product_variants.id, not null]
  sku_snapshot varchar(40) [not null]
  name_snapshot varchar(180) [not null]
  list_price_snapshot decimal(10,2) [not null, note: 'CHECK (list_price_snapshot >= 0)']
  unit_price decimal(10,2) [not null, note: 'CHECK (unit_price >= 0)']
  quantity smallint [not null, default: 1]
  total decimal(10,2) [not null, note: 'CHECK (total >= 0)']
  created_at timestamp
  updated_at timestamp

  Indexes {
    order_id
    product_variant_id
  }

  Note: 'docx: pedido_itens. Os campos _snapshot existem para que alteração futura de preço ou nome do produto não reescreva o histórico (razão explícita do documento de referência). total = unit_price × quantity.'
}

Table order_attribution {
  order_id bigint [pk, ref: > orders.id]
  device_type_id bigint [ref: > device_types.id, not null]
  gclid varchar(255) [null]
  utm_source varchar(180) [null]
  utm_medium varchar(180) [null]
  utm_campaign varchar(180) [null]
  utm_term varchar(180) [null]
  utm_content varchar(180) [null]
  landing_page varchar(255) [not null]
  referrer varchar(255) [null]
  first_touch_at timestamp [not null]
  sessions_before_purchase smallint [not null, default: 1]
  created_at timestamp
  updated_at timestamp

  Indexes {
    gclid
  }

  Note: 'docx: pedido_atribuicao. Extensão 1:1 de orders — order_id é a própria chave primária, conforme o documento de referência (não uma tabela com id próprio). Origem da venda, para medir campanha sem depender do relatório da plataforma de anúncios.'
}

// ============================================================
// Issuance & GFSIS integration
// ============================================================

Table issuance_data {
  id bigint [pk, increment]
  order_item_id bigint [ref: > order_items.id, unique, not null]
  holder_type_id bigint [ref: > holder_types.id, not null]
  holder_name varchar(180) [not null]
  document varchar(14) [not null]
  birth_date date [null]
  email varchar(180) [not null]
  phone varchar(20) [not null]
  postal_code varchar(8) [not null]
  street varchar(180) [not null]
  number varchar(20) [not null]
  complement varchar(120) [null]
  neighborhood varchar(120) [not null]
  city varchar(120) [not null]
  state char(2) [not null]
  ibge_code varchar(7) [null]
  responsible_name varchar(180) [null]
  responsible_document varchar(11) [null]
  responsible_birth_date date [null]
  responsible_email varchar(180) [null]
  responsible_phone varchar(20) [null]
  access_token char(40) [unique, not null]
  access_token_expires_at timestamp [not null]
  filled_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    document
    filled_at
  }

  Note: 'docx: dados_emissao. Dados do titular, coletados na segunda fase do checkout, depois da confirmação do pagamento — uma linha por order_item. Campos responsible_* são obrigatórios apenas quando holder_type é PJ; obrigatoriedade condicional validada na aplicação (Form Request), não no banco, conforme o documento de referência. access_token permite abrir o formulário por link direto sem login (fila de recuperação, capítulo 9.4). Nenhum documento de identidade, imagem ou gravação de videoconferência é armazenado aqui — fica sob guarda da Autoridade Certificadora.'
}

Table order_item_gfsis {
  id bigint [pk, increment]
  order_item_id bigint [ref: > order_items.id, unique, not null]
  status_id bigint [ref: > gfsis_statuses.id, null]
  gfsis_order_id bigint [unique, not null]
  gfsis_code varchar(30) [null]
  status_synced_at timestamp [null]
  appointment_id int [null]
  appointment_date date [null]
  appointment_time time [null]
  certificate_expires_at date [null]
  sent_at timestamp [null]
  attempts smallint [not null, default: 0]
  last_error text [null]
  request_payload json [null]
  response_payload json [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
    appointment_date
    certificate_expires_at
  }

  Note: 'docx: pedido_item_gfsis. Espelho leve da integração — o status real da emissão vive aqui (status_id), não em orders. gfsis_order_id é a chave de idempotência: reenvio não duplica o pedido no GFSIS. certificate_expires_at alimenta o relatório de base de renovação (documento de referência, capítulo 5). Cache de número/URL de nota fiscal não modelado — Pendência 2 do documento de referência, ver Open Questions.'
}

Table gfsis_events {
  id bigint [pk, increment]
  gfsis_order_id bigint [not null]
  event_hash char(64) [unique, not null]
  received_status varchar(40) [not null]
  payload json [not null]
  received_at timestamp [not null]
  processed_at timestamp [null]
  error text [null]
  created_at timestamp

  Indexes {
    gfsis_order_id
  }

  Note: 'docx: gfsis_eventos. Log do webhook de status do GFSIS, sem updated_at (tabela de log, convenção do documento de referência). received_status guarda o texto bruto recebido no evento — não é FK para gfsis_statuses porque é um snapshot histórico imutável do que chegou, não um valor vivo a filtrar (mesmo raciocínio de log aplicado a payment_events.received_status). event_hash garante idempotência e impede processamento duplicado.'
}

// ============================================================
// Payments
// ============================================================

Table payments {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, not null]
  payment_gateway_id bigint [ref: > payment_gateways.id, not null]
  payment_method_id bigint [ref: > payment_methods.id, not null]
  status_id bigint [ref: > payment_statuses.id, not null]
  gateway_transaction_id varchar(80) [not null]
  gateway_status_code varchar(20) [null]
  gross_amount decimal(10,2) [not null, note: 'CHECK (gross_amount >= 0)']
  gateway_fee decimal(10,2) [null]
  net_amount decimal(10,2) [null]
  pix_id varchar(64) [null]
  end_to_end_id varchar(64) [null]
  qr_code_payload text [null]
  boleto_digitable_line varchar(80) [null]
  receipt_url varchar(255) [null]
  installments smallint [null]
  card_brand varchar(20) [null]
  card_last_digits char(4) [null]
  authorization_nsu varchar(40) [null]
  expires_at timestamp [null]
  paid_at timestamp [null]
  expected_settlement_date date [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    order_id
    status_id
    gateway_transaction_id
    end_to_end_id
    paid_at
  }

  Note: 'docx: pagamentos. Um pedido pode ter mais de um pagamento (nova tentativa ou troca de forma). gateway_status_code guarda o código bruto do gateway "sem tradução" (documento de referência) — não é FK, é auditoria/depuração, mesmo raciocínio de gfsis_events.received_status. peso_status do documento de referência não é replicado aqui: a ordem lógica do status vive uma única vez em payment_statuses.weight. Nenhum dado completo de cartão é armazenado — tokenização fica no cofre do gateway; guarda no máximo bandeira, 4 últimos dígitos e NSU.'
}

Table payment_events {
  id bigint [pk, increment]
  payment_id bigint [ref: > payments.id, null]
  gateway_transaction_id varchar(80) [not null]
  event_hash char(64) [unique, not null]
  received_status varchar(20) [not null]
  payload json [not null]
  received_at timestamp [not null]
  processed_at timestamp [null]
  error text [null]
  created_at timestamp

  Indexes {
    payment_id
    gateway_transaction_id
  }

  Note: 'docx: pagamento_eventos. Log do webhook do gateway, sem updated_at. payment_id aceita nulo porque o evento pode chegar antes do vínculo (documento de referência). O gateway reenvia até 6 vezes com 5h de intervalo e pode entregar fora de ordem — a rotina de conferência periódica (não modelada como tabela) cobre a lacuna.'
}

Table refunds {
  id bigint [pk, increment]
  payment_id bigint [ref: > payments.id, not null]
  reason_id bigint [ref: > refund_reasons.id, not null]
  user_id bigint [ref: > users.id, not null]
  amount decimal(10,2) [not null, note: 'CHECK (amount >= 0)']
  requires_revocation boolean [not null, default: false]
  revocation_confirmed_at timestamp [null]
  requested_at timestamp [not null]
  completed_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    payment_id
  }

  Note: 'docx: reembolsos. user_id é o usuário do painel que registrou a solicitação. requires_revocation verdadeiro nos casos de arrependimento (devolução exige revogação do certificado, conforme documento de referência).'
}

// ============================================================
// Commercial
// ============================================================

Table coupons {
  id bigint [pk, increment]
  code varchar(40) [unique, not null]
  type_id bigint [ref: > coupon_types.id, not null]
  restricted_variant_id bigint [ref: > product_variants.id, null]
  value decimal(10,2) [not null, note: 'CHECK (value >= 0)']
  usage_limit int [null]
  uses_count int [not null, default: 0]
  per_customer_limit int [null]
  starts_at datetime [not null]
  ends_at datetime [not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Indexes {
    is_active
  }

  Note: 'docx: cupons. usage_limit e per_customer_limit nulos significam ilimitado. restricted_variant_id restringe o cupom a uma variante específica, quando preenchido.'
}

Table coupon_uses {
  id bigint [pk, increment]
  coupon_id bigint [ref: > coupons.id, not null]
  order_id bigint [ref: > orders.id, not null]
  customer_id bigint [ref: > customers.id, not null]
  discount_applied decimal(10,2) [not null, note: 'CHECK (discount_applied >= 0)']
  created_at timestamp

  Indexes {
    coupon_id
    customer_id
  }

  Note: 'docx: cupom_usos. Sem updated_at, igual ao documento de referência (registro de uso único, não editável). Sustenta o controle de limite por cliente (per_customer_limit).'
}

// ============================================================
// Integrations & system
// ============================================================

Table ads_conversions {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, unique, not null]
  status_id bigint [ref: > ads_conversion_statuses.id, not null]
  transaction_id varchar(60) [unique, not null]
  gclid varchar(255) [null]
  amount decimal(10,2) [not null, note: 'CHECK (amount >= 0)']
  currency char(3) [not null, default: 'BRL']
  attempts smallint [not null, default: 0]
  sent_at timestamp [null]
  response text [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
  }

  Note: 'docx: conversoes_ads. transaction_id é usado para deduplicação entre evento de navegador e evento de servidor. currency fica como código ISO solto (não lookup): é um padrão externo fixo, não uma categoria de negócio administrável.'
}

Table integration_queue {
  id bigint [pk, increment]
  status_id bigint [ref: > queue_job_statuses.id, not null]
  job varchar(60) [not null]
  reference_type varchar(60) [not null]
  reference_id bigint [not null]
  payload json [null]
  attempts smallint [not null, default: 0]
  run_at timestamp [not null]
  error text [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    job
    reference_id
    status_id
    run_at
  }

  Note: 'docx: integracao_fila. Fila genérica: envio ao GFSIS, envio de conversão, disparo de e-mail e reconsulta de status. reference_type + reference_id apontam para a entidade relacionada (ex.: order_item) de forma genérica — sem Ref explícito no DBML, pois pode apontar para tabelas diferentes conforme o job (mesmo padrão de audit_logs.entity/entity_id).'
}

Table users {
  id bigint [pk, increment]
  role_id bigint [ref: > roles.id, not null]
  name varchar [not null]
  email varchar [unique, not null]
  email_verified_at timestamp [null]
  password varchar [not null]
  two_factor_secret text [null]
  two_factor_recovery_codes text [null]
  two_factor_confirmed_at timestamp [null]
  remember_token varchar [null]
  is_active boolean [not null, default: true]
  last_login_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Note: 'docx: usuarios (usuários do painel administrativo). Tabela users já existe no projeto (migration 0001_01_01_000000, starter kit Fortify) com id, name, email, email_verified_at, password, remember_token, two_factor_* e timestamps — este schema apenas acrescenta role_id, is_active e last_login_at do documento de referência. Distinta de customers (compradores da loja, sem papel/permissão de painel).'
}

Table audit_logs {
  id bigint [pk, increment]
  user_id bigint [ref: > users.id, not null]
  action varchar(60) [not null]
  entity varchar(60) [not null]
  entity_id bigint [not null]
  data_before json [null]
  data_after json [null]
  ip_address varchar(45) [not null]
  created_at timestamp

  Indexes {
    (entity, entity_id)
    user_id
  }

  Note: 'docx: log_auditoria. Sem updated_at (tabela de log). Toda alteração manual de status de pedido, pagamento ou integração é obrigatoriamente registrada aqui, conforme o documento de referência.'
}

Table settings {
  id bigint [pk, increment]
  key varchar(120) [unique, not null]
  value text [not null]
  group varchar(60) [not null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    group
  }

  Note: 'docx: configuracoes. Parâmetros do sistema editáveis pelo painel administrativo.'
}

```

## Relationships

- Um **holder_type** (PF/PJ) é referenciado por muitos **products**, **customers** e **issuance_data**.
- Um **product** tem muitos **product_variants**; cada variante combina um **certificate_format** (A1/A3) com
  preço e validade próprios.
- Um **customer** tem muitos **customer_addresses** e muitos **orders**.
- Um **order** pertence a um **customer**, tem um **order_status** (ciclo financeiro), um
  **order_fulfillment_status** (resumo do ciclo de emissão), um **payment_method** e, opcionalmente, um
  **coupon**.
- Um **order** tem muitos **order_items**; cada item referencia um **product_variant**.
- Um **order** tem uma **order_attribution** (1:1, chave primária compartilhada) com a origem da venda.
- Um **order_item** tem um conjunto de **issuance_data** (1:1) — dados do titular coletados após o pagamento.
- Um **order_item** tem um **order_item_gfsis** (1:1) — espelho da integração, com seu próprio **gfsis_status**.
- Um **gfsis_order_id** em **order_item_gfsis** tem muitos **gfsis_events** (log de webhooks).
- Um **order** tem muitos **payments** (tentativas), cada um com um **payment_gateway**, um **payment_method** e
  um **payment_status**.
- Um **payment** tem muitos **payment_events** (log de webhooks) e muitos **refunds**.
- Um **refund** tem um **refund_reason** e é registrado por um **user** (painel).
- Um **coupon** tem um **coupon_type** e muitos **coupon_uses**; cada uso referencia um **order** e um
  **customer**.
- Um **order** tem uma **ads_conversion** (1:1) com um **ads_conversion_status**.
- **integration_queue** referencia genericamente qualquer entidade via `reference_type`/`reference_id`, com um
  **queue_job_status**.
- Um **role** tem muitos **users** (painel administrativo); um **user** pode registrar muitos **refunds** e
  muitos **audit_logs**.

## Lookup Table Seeds

- **holder_types**: PF (Pessoa Física), PJ (Pessoa Jurídica)
- **certificate_formats**: A1 (`requires_hardware`: false), A3 (`requires_hardware`: true)
- **payment_methods**: Pix (seed inicial, `is_active`: true) — cartão e boleto ficam cadastrados como
  candidatos futuros conforme o negócio decidir habilitá-los; valores de `discount_percentage` e
  `max_installments` por forma são pendência de dado do negócio (Pendência 3 do documento de referência, ver
  Open Questions)
- **payment_gateways**: Safe2Pay (decidido no documento de referência)
- **order_statuses**: cart, awaiting_payment, paid, cancelled, refunded, expired
- **order_fulfillment_statuses**: awaiting_data, data_complete, sent_to_gfsis, send_failed
- **gfsis_statuses**: estrutura criada, sem seed — vocabulário de status vem da API do GFSIS, não documentado
  nas fontes atuais (ver Open Questions)
- **payment_statuses**: pending, authorized, expired, reversed, denied, under_review (com `weight` crescente
  nesta mesma ordem lógica, salvo `under_review`, que a aplicação posiciona conforme a regra de negócio)
- **refund_reasons**: withdrawal_right (arrependimento), warranty, duplicate, chargeback, other
- **coupon_types**: percentage, fixed_amount
- **device_types**: desktop, mobile, tablet
- **ads_conversion_statuses**: pending, sent, failed
- **queue_job_statuses**: pending, processing, completed, failed
- **roles**: admin, operations, finance, support

## Notes & Conventions

- **Fonte de verdade transacional**: as tabelas de catálogo, cliente, pedido, pagamento e integração GFSIS
  seguem estruturalmente o documento `Digital Lock | Estrutura de Banco de Dados, v1.0` (anexado nesta
  execução), campo a campo, com identificadores traduzidos para inglês (decisão tomada nesta sessão — ver
  pergunta respondida no início desta execução) e categóricas convertidas em lookup table (regra da casa,
  universal). Cada tabela de origem no documento traz o nome português original em sua `Note` DBML.
- **Dois ciclos de vida independentes**: `orders.status_id` (financeiro) e `order_item_gfsis.status_id`
  (operacional/emissão) nunca são unificados, conforme o princípio central do documento de referência.
  `orders.fulfillment_status_id` é o resumo denormalizado usado só para filtro rápido no painel, recalculado
  pela mesma rotina que atualiza os itens (regra de cálculo no capítulo 9.3 do documento: se qualquer item está
  em `send_failed`, o pedido fica em `send_failed`; senão, se qualquer item está em `awaiting_data`, o pedido
  fica em `awaiting_data`; senão, assume o menor estágio entre os itens).
- **`weight` centralizado em `payment_statuses`**: o documento de referência duplicava `peso_status` em
  `pagamentos` e `pagamento_eventos` para permitir comparação e impedir regressão de status fora de ordem. Aqui
  o peso vive uma única vez em `payment_statuses.weight`; a aplicação compara os pesos dos `status_id` das duas
  linhas em vez de comparar uma coluna redundante.
- **Campos de log mantidos como snapshot de texto, não FK**: `gfsis_events.received_status`,
  `payment_events.received_status` e `payments.gateway_status_code` guardam o valor bruto recebido de sistemas
  externos, exatamente como o documento de referência os descreve ("sem tradução"). Não violam a regra de "sem
  enum" — são log imutável do que chegou, não uma coluna categórica viva a filtrar.
- **Sem tabela de nota fiscal**: a emissão fiscal é responsabilidade do GFSIS; não há tabela `invoices` neste
  modelo, seguindo o documento de referência. Cache local de número/URL da nota (`nf_numero`, `nf_url`,
  `nf_consultada_em` em `order_item_gfsis`) é uma pendência explícita do documento — ver Open Questions.
- **`customers` é distinta de `users`**: `users` (já existente no projeto, Fortify) é a equipe do painel
  administrativo; `customers` é quem compra na loja. As duas nunca se misturam — um mesmo e-mail pode existir
  em ambas as tabelas sem conflito, representando papéis diferentes.
- **Nenhuma tabela usa `deleted_at`**: pedido, pagamento e emissão são inativados por status, nunca apagados
  (convenção explícita do documento de referência); extensão aplicada também a `users`, já que o model `User`
  atual do projeto não implementa `SoftDeletes`.
- **CPF/CNPJ, CEP e UF**: `document` (customers, issuance_data) guarda apenas dígitos, sem máscara; `postal_code`
  guarda 8 dígitos sem traço; `state` (UF) e `currency` (ISO) ficam como código solto, não lookup — são padrões
  externos fixos (IBGE/ISO), não categorias de negócio administráveis pelo painel.
- **Valores monetários** usam `decimal(10,2)` explícito com `CHECK (coluna >= 0)` documentado via `note` na
  coluna DBML, a aplicar como constraint real na migration — convenção herdada do documento de referência
  ("valores monetários usam decimal com dez dígitos e duas casas").
- **`ON DELETE RESTRICT` em toda foreign key**: nenhuma FK deste schema deve depender do comportamento padrão
  implícito do driver/Eloquent. Declarar `->restrictOnDelete()` (ou equivalente) explicitamente em cada
  `foreign()` nas migrations.
- **Tabelas de log sem `updated_at`**: `gfsis_events`, `payment_events`, `audit_logs`, `coupon_uses` têm apenas
  `created_at`, seguindo a convenção geral do documento de referência para tabelas de log/registro de uso
  único.

## Open Questions

- **Identificador numérico do certificado exigido pelo GFSIS** (Pendência 1 do documento de referência): o
  endpoint de criação de pedido do GFSIS exige um identificador numérico. Se `product_variants.sku` não for
  esse número, a tabela precisa de um campo adicional para armazená-lo — decisão pendente do time.
- **Cache de nota fiscal** (Pendência 2 do documento de referência): exibir a nota na conta do cliente sem
  consultar a API do GFSIS a cada acesso exigiria três campos em `order_item_gfsis` (`nf_numero`, `nf_url`,
  `nf_consultada_em`) — decisão pendente, não modelada.
- **Vocabulário de `gfsis_statuses`**: a estrutura de lookup foi criada (regra da casa), mas os valores
  possíveis de status não estão documentados nas fontes atuais — vêm da API do GFSIS. Seed pendente de
  confirmação junto à integração.
- **Preços e percentual de desconto por forma de pagamento** (Pendência 3 do documento de referência): valores
  concretos a cadastrar em `product_variants` e `payment_methods` são pendência de dado do negócio, não de
  schema.
- **Matriz de permissões do painel** (Pendência 4 do documento de referência): os quatro papéis em `roles`
  (admin, operations, finance, support) precisam de uma matriz de permissão por tela — o documento de referência
  registra a pendência sem detalhar a granularidade; fica registrado, não modelado.
