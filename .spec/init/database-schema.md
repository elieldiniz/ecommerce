# Digital Lock E-commerce — Database Schema

<!-- inputs: project-description.md@sha256:cb6009f4181e user-stories.md@sha256:5fb32d8adb92 -->

## Overview

O modelo de dados gira em torno de três eixos: **catálogo** (`certificate_types`, `certificate_formats`,
`certificate_skus` — as 4 combinações e-CPF/e-CNPJ × A1/A3), **transação** (`orders`, `payments`, `invoices`,
`certificate_holders` — o titular pode ser diferente de quem compra) e **ciclo de vida do certificado**
(`certificates`, `appointments`, `revocation_requests`, `withdrawal_requests`). Uma renovação é apenas uma nova
`order` que referencia o `certificate` anterior via `renews_certificate_id`, conforme pedido explicitamente no
documento de referência ("deixar previsto na modelagem agora, mesmo sem uso imediato"). O conteúdo gerenciável
pelo painel administrativo (Feature Area 7 das user stories) vive em `faq_questions`, `use_cases` e
`support_issues`. Mudanças de status em `orders`, `payments` e `certificates` — todos registro financeiro/legal
— ficam auditadas em `status_histories`.

Convenções em vigor: stack detectada é **Laravel 13 / Eloquent** — tabelas no plural snake_case, chave primária
`id bigint` auto-incremento, chaves estrangeiras `<singular>_id`, `created_at`/`updated_at` em toda tabela de
domínio. Todo campo categórico (status, tipo, formato, categoria, papel) vira tabela de lookup própria, nunca
coluna enum — cada estado de cada máquina de estado (pedido, pagamento, certificado, agendamento, revogação,
arrependimento) tem sua própria tabela de status, para não misturar valores de contextos diferentes. Soft
delete (`deleted_at`) é usado **só em `users`**, coerente com a feature de exclusão de conta já existente no
starter kit (Fortify); pedidos, pagamentos e certificados são registro financeiro/legal e nunca são apagados,
só mudam de status.

## Schema (DBML)

```dbml
// ============================================================
// Lookup tables
// ============================================================

Table roles {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table certificate_types {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table certificate_formats {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  requires_hardware boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp
}

Table payment_methods {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table order_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table payment_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table certificate_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table appointment_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table revocation_request_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table withdrawal_request_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table faq_categories {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  position smallint [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

// ============================================================
// Domain tables
// ============================================================

Table users {
  id bigint [pk, increment]
  name varchar [not null]
  email varchar [unique, not null]
  email_verified_at timestamp [null]
  password varchar [not null]
  role_id bigint [ref: > roles.id, not null]
  two_factor_secret text [null]
  two_factor_recovery_codes text [null]
  two_factor_confirmed_at timestamp [null]
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [null]
}

Table certificate_skus {
  id bigint [pk, increment]
  certificate_type_id bigint [ref: > certificate_types.id, not null]
  certificate_format_id bigint [ref: > certificate_formats.id, not null]
  sku_code varchar [unique, not null]
  name varchar [not null]
  price decimal(10,2) [not null, note: 'CHECK (price >= 0)']
  price_pix decimal(10,2) [null, note: 'CHECK (price_pix >= 0)']
  validity_months smallint [not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (certificate_type_id, certificate_format_id) [unique]
  }

  Note: 'validity_months é fixo por SKU (ex.: A3 = 36). Cliente não escolhe prazo no checkout — decisão confirmada no init:database-schema. UNIQUE(certificate_type_id, certificate_format_id) porque o catálogo é fechado (4 combinações) e duplicata é sempre erro.'
}

Table orders {
  id bigint [pk, increment]
  user_id bigint [ref: > users.id, not null]
  certificate_sku_id bigint [ref: > certificate_skus.id, not null]
  status_id bigint [ref: > order_statuses.id, not null]
  renews_certificate_id bigint [ref: > certificates.id, null]
  total_price decimal(10,2) [not null, note: 'CHECK (total_price >= 0)']
  paid_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
    (user_id, status_id)
  }

  Note: 'renews_certificate_id aponta para o certificado sendo renovado. Preço/SKU de renovação hoje são os mesmos da primeira emissão (sem SKU distinto), conforme documento de referência. ORDEM DE MIGRATION: orders e certificates têm referência cruzada (orders.renews_certificate_id -> certificates.id, certificates.order_id -> orders.id) — criar a coluna renews_certificate_id SEM a constraint FK na migration que cria orders (unsignedBigInteger nullable), criar certificates na sequência, e só então adicionar a constraint FK de renews_certificate_id numa migration posterior (Schema::table(\'orders\', fn ($table) => $table->foreign(\'renews_certificate_id\')->references(\'id\')->on(\'certificates\'))). Migration do zero na ordem padrão falha sem esse passo extra.'
}

Table certificate_holders {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, unique, not null]
  full_name varchar [not null]
  document_number varchar [not null]
  birth_date date [null]
  company_name varchar [null]
  legal_representative_name varchar [null]
  phone varchar [not null]
  email varchar [not null]
  created_at timestamp
  updated_at timestamp

  Note: 'Titular do certificado, capturado no checkout. Pode ser diferente de quem paga (orders.user_id) — ex.: contador comprando para o cliente. document_number guarda CPF (e-CPF) ou CNPJ (e-CNPJ); company_name e legal_representative_name só se aplicam a e-CNPJ.'
}

Table payments {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, not null]
  payment_method_id bigint [ref: > payment_methods.id, not null]
  status_id bigint [ref: > payment_statuses.id, not null]
  amount decimal(10,2) [not null, note: 'CHECK (amount >= 0)']
  gateway_reference varchar [null]
  paid_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
  }

  Note: 'Um pedido pode ter mais de uma tentativa de pagamento (ex.: Pix expirado e gerado de novo). Gateway ainda não decidido — gateway_reference fica genérico até a integração ser definida.'
}

Table invoices {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, unique, not null]
  invoice_number varchar [unique, not null]
  file_path varchar [null]
  issued_at timestamp [not null]
  created_at timestamp
  updated_at timestamp

  Note: 'A linha só é criada no momento da emissão real da nota fiscal — nunca como placeholder antes disso. invoice_number segue regra legal de numeração sequencial no Brasil, então não pode existir sem issued_at preenchido junto. Até a NF ser emitida, o pedido simplesmente não tem linha em invoices.'
}

Table certificates {
  id bigint [pk, increment]
  order_id bigint [ref: > orders.id, unique, not null]
  status_id bigint [ref: > certificate_statuses.id, not null]
  validation_deadline_at date [null]
  approved_at timestamp [null]
  issued_at timestamp [null]
  expires_at date [null]
  revoked_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    status_id
  }

  Note: 'validation_deadline_at = paid_at + 180 dias (prazo para concluir validação e emissão). expires_at é calculado no momento da emissão a partir de certificate_skus.validity_months.'
}

Table appointments {
  id bigint [pk, increment]
  certificate_id bigint [ref: > certificates.id, not null]
  status_id bigint [ref: > appointment_statuses.id, not null]
  scheduled_at timestamp [null]
  scheduling_token varchar [unique, not null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    certificate_id
    status_id
  }

  Note: 'Relação 1:N com certificates, não 1:1 — cada reagendamento ou nova tentativa após reprovação cria uma NOVA linha em vez de sobrescrever a anterior (a linha antiga muda de status, ex.: para reagendado/reprovado, e permanece como histórico). A linha vigente é a de maior scheduled_at/created_at. scheduling_token é o identificador único do link de agendamento enviado por e-mail após confirmação do pagamento.'
}

Table revocation_requests {
  id bigint [pk, increment]
  certificate_id bigint [ref: > certificates.id, not null]
  requested_by_user_id bigint [ref: > users.id, not null]
  processed_by_user_id bigint [ref: > users.id, null]
  status_id bigint [ref: > revocation_request_statuses.id, not null]
  reason text [not null]
  requested_at timestamp [not null]
  processed_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    certificate_id
    status_id
  }

  Note: 'processed_by_user_id identifica qual administrador/atendente concluiu a revogação — trilha de auditoria exigida por ser registro legal. Nulo enquanto status_id = solicitada.'
}

Table withdrawal_requests {
  id bigint [pk, increment]
  certificate_id bigint [ref: > certificates.id, not null]
  processed_by_user_id bigint [ref: > users.id, null]
  status_id bigint [ref: > withdrawal_request_statuses.id, not null]
  requested_at timestamp [not null]
  refunded_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    certificate_id
    status_id
  }

  Note: 'Direito de arrependimento: requested_at deve estar dentro de 7 dias corridos da aprovação do certificado (certificates.approved_at). Devolução de valores exige revogação do certificado (revocation_requests). Sem order_id próprio — o pedido é alcançado via certificate_id -> certificates.order_id, evitando duas colunas que podiam divergir silenciosamente. processed_by_user_id é a trilha de auditoria de quem aprovou o reembolso.'
}

Table status_histories {
  id bigint [pk, increment]
  auditable_type varchar [not null]
  auditable_id bigint [not null]
  from_status varchar [null]
  to_status varchar [not null]
  changed_by_user_id bigint [ref: > users.id, null]
  changed_at timestamp [not null]
  created_at timestamp

  Indexes {
    (auditable_type, auditable_id)
  }

  Note: 'Trilha de auditoria de mudança de status para orders, payments e certificates (registro financeiro/legal). auditable_type + auditable_id referenciam polimorficamente a linha de origem (padrão Eloquent morphTo) — por isso não há Ref explícito nesta tabela. from_status/to_status guardam o slug do status como texto (snapshot histórico imutável, não uma FK viva), porque cada entidade auditada tem sua própria tabela de status (order_statuses, payment_statuses, certificate_statuses) e uma coluna polimórfica não pode apontar para tabelas de lookup diferentes ao mesmo tempo — não é uma exceção à regra de "sem enum", é um log de valores passados.'
}

Table faq_questions {
  id bigint [pk, increment]
  faq_category_id bigint [ref: > faq_categories.id, not null]
  question varchar [not null]
  answer text [not null]
  anchor varchar [unique, not null]
  position smallint [not null, default: 0]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Note: 'Qual categoria aparece em qual página é regra fixa da aplicação (não persistida) — decisão confirmada no init:database-schema.'
}

Table use_cases {
  id bigint [pk, increment]
  situation varchar [not null]
  recommended_certificate varchar [not null]
  position smallint [not null, default: 0]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp

  Note: 'recommended_certificate é texto livre (ex.: "e-CPF A1 ou A3"), não FK para certificate_skus — algumas situações do documento de referência recomendam mais de um formato.'
}

Table support_issues {
  id bigint [pk, increment]
  situation varchar [not null]
  response text [not null]
  anchor varchar [unique, not null]
  position smallint [not null, default: 0]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}
```

## Relationships

- Um **role** tem muitos **users** (cliente ou administrador de conteúdo).
- Um **certificate_type** e um **certificate_format** combinados formam um **certificate_sku** (ex.: e-CNPJ +
  A3).
- Um **user** faz muitos **orders**.
- Um **order** pertence a um **certificate_sku**, a um **order_status**, e opcionalmente renova um
  **certificate** anterior (`renews_certificate_id`).
- Um **order** tem um **certificate_holder** (1:1) — o titular do certificado, que pode não ser o comprador.
- Um **order** tem muitos **payments** (tentativas de pagamento), cada um com um **payment_method** e um
  **payment_status**.
- Um **order** tem uma **invoice** (1:1) — a nota fiscal da compra.
- Um **order** gera um **certificate** (1:1), com um **certificate_status** próprio.
- Um **certificate** tem muitos **appointments** ao longo do tempo (histórico de agendamentos e
  reagendamentos), cada um com seu próprio **appointment_status**; o mais recente é o vigente.
- Um **certificate** pode ter muitos **revocation_requests**, cada um solicitado por um **user**, opcionalmente
  processado por outro **user** (`processed_by_user_id`), e com um **revocation_request_status**.
- Um **certificate** pode ter um **withdrawal_request** (direito de arrependimento) — o pedido correspondente
  é alcançado indiretamente via `certificate.order_id`, sem coluna própria em `withdrawal_requests`.
- Uma **faq_category** tem muitas **faq_questions**.
- **use_cases** e **support_issues** são tabelas de conteúdo independentes, sem relacionamento com outras
  tabelas (gerenciadas diretamente pelo painel administrativo).
- **status_histories** referencia polimorficamente `orders`, `payments` ou `certificates` (`auditable_type` +
  `auditable_id`), opcionalmente associada a um **user** que fez a mudança (`changed_by_user_id`).

## Lookup Table Seeds

- **roles**: cliente, administrador
- **certificate_types**: e-CPF, e-CNPJ
- **certificate_formats**: A1 (`requires_hardware`: false), A3 (`requires_hardware`: true)
- **payment_methods**: Pix (`is_active`: true) — boleto/cartão ficam como candidatos futuros, sem seed
  enquanto o gateway não for decidido (ver Open Questions)
- **order_statuses**: aguardando_pagamento, pago, cancelado, reembolsado
- **payment_statuses**: pendente, confirmado, falhou, estornado
- **certificate_statuses**: aguardando_validacao, aprovado, emitido, revogado, vencido
- **appointment_statuses**: agendado, reagendado, concluido, reprovado
- **revocation_request_statuses**: solicitada, concluida
- **withdrawal_request_statuses**: solicitado, aprovado, reembolsado, negado
- **faq_categories** (9 linhas, na ordem do banco de perguntas): antes_de_comprar, compra_e_pagamento,
  videoconferencia_e_validacao, emissao_e_instalacao, uso_do_certificado, renovacao_e_vencimento, mei,
  revogacao_garantia_e_devolucao, suporte_e_atendimento

## Notes & Conventions

- **Ordem de migration para `orders` ↔ `certificates`**: as duas tabelas se referenciam mutuamente
  (`orders.renews_certificate_id -> certificates.id` e `certificates.order_id -> orders.id`). Criar a coluna
  `renews_certificate_id` sem a constraint FK na migration de `orders`, criar `certificates` na sequência, e só
  então adicionar a constraint FK de `renews_certificate_id` numa migration posterior. Sem esse passo, uma
  migration do zero falha na ordem padrão (repetido na Note da tabela `orders` no DBML).
- **`invoices` só existe após emissão real da nota fiscal**: a linha não é criada preemptivamente na compra —
  `invoice_number` e `issued_at` são preenchidos juntos, no momento da emissão. Numeração fiscal segue regra
  legal de sequência no Brasil e não admite placeholder.
- **Valores monetários** (`certificate_skus.price`, `price_pix`, `orders.total_price`, `payments.amount`) usam
  `decimal(10,2)` explícito (nunca `decimal` sem escala) e têm `CHECK (coluna >= 0)` documentado via `note` na
  coluna DBML — a aplicar como constraint real na migration.
- **Índices**: toda coluna `status_id` (orders, payments, certificates, appointments, revocation_requests,
  withdrawal_requests) tem índice próprio, mais o composto `(user_id, status_id)` em `orders` — o painel
  administrativo filtra por status o tempo todo. `certificate_skus` tem `UNIQUE(certificate_type_id,
  certificate_format_id)`, já que o catálogo é fechado (4 combinações) e duplicata é sempre erro de dado.
- **Trilha de auditoria**: `revocation_requests` e `withdrawal_requests` têm `processed_by_user_id` (quem
  concluiu a solicitação); mudanças de status em `orders`, `payments` e `certificates` — todos registro
  financeiro/legal — ficam em `status_histories` (auditoria polimórfica: `auditable_type` + `auditable_id`).
- **`ON DELETE RESTRICT` em toda foreign key**: nenhuma FK deste schema deve depender do comportamento padrão
  implícito do driver/Eloquent. Declarar `->restrictOnDelete()` (ou equivalente) explicitamente em cada
  `foreign()` nas migrations, como rede de segurança contra exclusão acidental de uma linha ainda referenciada.
- **Soft delete** só em `users` (decisão confirmada no init:database-schema) — coerente com a feature de
  exclusão de conta já presente no starter kit (`resources/views/pages/settings/⚡delete-user-form.blade.php`).
  Nenhuma outra tabela usa `deleted_at`: pedidos, pagamentos e certificados são registro financeiro/legal e só
  mudam de status.
- **Sem carrinho multi-item**: cada `order` referencia um único `certificate_sku_id`. Nenhuma página de produto
  ou user story descreve compra de mais de um certificado por pedido; se isso mudar, `orders` vira
  `order_items` com pivot.
- **`certificates` é criado quando o pedido é confirmado** (`orders.status_id` = pago), não na criação do
  pedido — é uma decisão de aplicação, não de schema, mas afeta a ordem de escrita nas migrations/seeders.
- **Preço duplicado em `orders.total_price`** apesar de existir em `certificate_skus.price`: denormalização
  intencional para preservar o valor pago mesmo se o preço do SKU mudar depois.
- **`certificate_holders.document_number`** guarda CPF (e-CPF) ou CNPJ (e-CNPJ) sem formatação fixa de
  máscara — validação de formato fica na camada de aplicação (Form Request), não no schema.
- **Sem tabela para mapeamento FAQ×página**: a distribuição de categorias por página (Home = 1 e 3, Suporte =
  4, 8 e 9, etc.) é regra fixa da aplicação, não editável pelo painel administrativo nesta versão — decisão
  confirmada no init:database-schema, pois nenhuma user story pede essa edição.
- **MEI não é um atributo persistido**: é um segmento de marketing/conteúdo para o mesmo SKU de e-CNPJ, sem
  campo próprio no schema (nenhuma user story exige relatório ou filtro por MEI).
- **Sem tabela de garantia legal (90 dias)**: nenhuma user story descreve um fluxo de abertura de reclamação de
  garantia distinto de suporte/revogação; o prazo em si é conteúdo estático da página Trocas e Devoluções
  (fora de escopo da Fase 1). Se um fluxo de reclamação formal for definido depois, vira tabela própria.
- **Pendência registrada no documento de referência, não modelada**: a renovação por solicitação eletrônica
  sem nova validação (prevista na Declaração de Práticas de Negócio, só para pessoa física sem biometria
  cadastrada) está marcada no próprio documento como "não incluir até a confirmação" — não há coluna ou tabela
  para isso ainda.

## Open Questions

- **Gateway de pagamento**: ainda não decidido (ver project-description.md). `payment_methods` está seedado só
  com Pix; `payments.gateway_reference` é genérico até a integração ser escolhida.
- **Provedor de e-mail**: ainda não decidido. Não afeta o schema diretamente, mas o disparo do e-mail de
  agendamento (que gera o `appointments.scheduling_token`) depende dessa escolha.
- **Ferramenta do painel administrativo** (Filament vs. Livewire próprio): não afeta o schema — qualquer uma
  das duas opera sobre as mesmas tabelas (`faq_questions`, `use_cases`, `support_issues`).
- **Duração do A3 (1, 2 ou 3 anos)**: modelada como valor fixo em `certificate_skus.validity_months` por
  decisão confirmada nesta sessão. Se o negócio decidir permitir o cliente escolher a duração no checkout (com
  preço variável), isso exige uma tabela `certificate_validity_options` e uma coluna em `orders` referenciando
  a opção escolhida — reavaliar quando a regra de preço por prazo for definida.
