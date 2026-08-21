# Pagamentos e Estornos

[← Voltar ao índice de módulos](README.md)

## Finalidade

Cobra o pedido na Safe2Pay (gateway de pagamento), interpreta as
confirmações assíncronas (webhook) e reconciliação periódica, e processa
estornos de Pix e Cartão. É o módulo que decide quando um pedido passa a
`paid` e quando ele deve ser reembolsado.

- **Integração externa**: [Safe2Pay](https://safe2pay.com.br/) — detalhes
  de payload e endpoints em [`docs/safe2pay.md`](../safe2pay.md).
- **Rotas**: `checkout/tokenizar-cartao`, `webhooks/safe2pay` (sem CSRF),
  chamadas internamente pelo [Checkout](carrinho-e-checkout.md).
- **Comando agendado**: `payments:reconcile` (a cada 5 min).

## Funcionalidades

- **Tokenização de cartão**: troca os dados brutos do cartão por um
  `Token` do Cofre de Chaves da Safe2Pay antes de qualquer cobrança —
  nunca loga nem persiste número de cartão/CVV.
- **Cobrança por forma de pagamento**: uma Action por forma —
  `ChargePixPayment`, `ChargeCardPayment`, `ChargeBoletoPayment`
  (`app/Actions/Payments/`).
- **Consulta de parcelamento**: `FetchInstallmentValues` consulta juros por
  parcela na Safe2Pay, cacheada por valor (nunca cacheia falha).
- **Processamento de webhook**: grava o payload bruto, aplica a transição
  de status e dispara efeitos colaterais (autorização, estorno).
- **Reconciliação automática** (`payments:reconcile`): fecha a lacuna de
  webhooks perdidos.
- **Estorno de Pix/Cartão** (painel): abre um estorno ativo contra a
  Safe2Pay.
- **Alteração manual de status** (`UpdatePaymentStatusManually`): action
  disponível para uma futura tela de painel, com auditoria — hoje sem tela
  associada.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Payment` | Uma linha por tentativa de cobrança (um pedido pode ter várias). |
| `PaymentEvent` | Log bruto de cada webhook recebido da Safe2Pay (idempotência por `event_hash`). |
| `PaymentStatus` | Lookup com `weight` — a transição só avança se o peso do novo status for maior. |
| `PaymentGateway` | Hoje só `safe2pay`. |
| `Refund` | Um estorno solicitado/confirmado, ligado a `Payment`. |
| `RefundReason` | Motivo do estorno (arrependimento, garantia, duplicidade, chargeback, outro). |
| `AuditLog` | Toda mudança manual de status e todo estorno gera uma linha aqui. |
| `Setting` | `pix_expiration_seconds`, `reconciliation_pending_threshold_minutes`. |

## Principais fluxos

### 1. Cobrança (no checkout)

`ChargePixPayment` / `ChargeCardPayment` / `ChargeBoletoPayment` recalculam
o total, bloqueiam em caso de divergência (`PaymentTotalMismatchException`),
montam o payload via `PaymentPayloadBuilder` e chamam a Safe2Pay. Em caso
de sucesso, criam a linha em `payments` já com o `gateway_transaction_id`.
Cartão exige `Token` do cofre de chaves + `VisitorID` (antifraude) e rejeita
parcelas acima de `payment_methods.max_installments`.

### 2. Confirmação assíncrona (webhook)

`POST webhooks/safe2pay` (`Safe2PayWebhookController`) grava o payload bruto
em `payment_events` **incondicionalmente**, mesmo que o `IdTransaction` não
corresponda a nenhum `Payment` conhecido — idempotência por
`event_hash` (sha256 do corpo) garante que reenvios nunca disparem
processamento duplicado. Responde `200` imediatamente e delega o
processamento a `ProcessSafe2PayWebhookJob` (fila `database`), que chama
`ApplyPaymentStatusTransition`.

### 3. `ApplyPaymentStatusTransition` — coração do módulo

Único ponto que interpreta os 13 códigos de status da Safe2Pay
(`App\Support\Safe2Pay\TransactionStatus`) e aplica a transição — usado
tanto pelo webhook quanto pela reconciliação periódica, para nunca
duplicar a regra:

- Só aplica a transição se `PaymentStatus.weight` do novo status for
  **estritamente maior** que o atual (nunca regride um status).
- Ao autorizar (`authorized`) pela primeira vez no pedido: marca
  `orders.status = paid` + `orders.paid_at`, enfileira `send_to_gfsis` em
  `integration_queue`, dispara `RegisterOrderItemWithGfsisJob`, gera o
  token de emissão (`GenerateIssuanceAccessToken`) e envia o e-mail com o
  link de emissão.
- Se o pedido **já estava `paid`** e chega uma nova autorização (ex.:
  segunda tentativa de cobrança confirmada por engano), o pedido nunca
  muda de status de novo — em vez disso, abre um estorno automático de
  duplicata (`reason = duplicate`) atribuído a um usuário de sistema.
- Ao estornar (`reversed`): cria uma linha em `refunds` se ainda não
  existir uma (usa `chargeback` como motivo quando o código bruto for `13`,
  `other` para `6`) e notifica o time financeiro por e-mail
  (`PaymentRefundedNotification`, enviada a todo usuário com
  `roles.slug = 'finance'`).
- Código `5` (Em disputa) sempre notifica o financeiro, independente de
  mudar o peso do status.

### 4. Reconciliação (`payments:reconcile`, a cada 5 min)

Consulta a Safe2Pay para pagamentos pendentes que passaram do prazo:
Pix com `expires_at` vencido (expira automaticamente para `expired` se a
consulta não retornar algo de peso igual/maior), e Cartão/Boleto sem
`expires_at` mas parados há mais que
`reconciliation_pending_threshold_minutes` (`settings`). Reaproveita
`ApplyPaymentStatusTransition` — mesma regra do webhook.

### 5. Estorno manual (painel)

`RequestPixRefund` / `RequestCardRefund` exigem
`payment->status->slug === 'authorized'`, criam a linha em `refunds`
**antes** da chamada síncrona à Safe2Pay (`CreateRefund`, que também grava
`audit_logs`), e a confirmação definitiva só acontece depois, via webhook
de status 6/13. Se a chamada síncrona falhar, o refund não fica órfão: uma
linha extra em `audit_logs` registra a falha, sem lançar exceção. Não existe
estorno de Boleto.

## Como o usuário interage

- **Cliente**: indiretamente, ao finalizar a compra no checkout e
  acompanhar o status na tela `pedido/{id}/pagamento/`.
- **Time interno**: solicita estornos a partir do detalhe do pedido em
  [Vendas](vendas.md) (`painel/vendas/{id}/`); recebe e-mail automático
  quando um pagamento é estornado (se tiver papel `finance`).

## Regras de negócio importantes

- `refunds.user_id`/`audit_logs.user_id` são `NOT NULL` — não existe
  reembolso "anônimo". Reembolsos automáticos (duplicata, chargeback) usam
  uma conta de sistema fixa (`sistema@digitallock.com.br`, `is_active =
  false`, criada sob demanda) em vez de usuário nulo.
- `payments.gateway_status_code` sempre grava o código bruto recebido, sem
  tradução — preservado para auditoria mesmo quando o código mapeia para um
  slug interno com rótulo diferente (ex.: `7`/Baixado → `expired`).
- Cada tentativa de pagamento gera uma **nova linha** em `payments`; nada é
  sobrescrito, e uma nova tentativa de Pix nunca reaproveita o QR code de
  uma tentativa anterior.

## Relação com outros módulos

- **[Carrinho e Checkout](carrinho-e-checkout.md)**: origem de toda
  cobrança — o pedido e a forma de pagamento já existem antes deste módulo
  agir.
- **[Vendas](vendas.md)**: o status de pagamento mais recente aparece no
  detalhe do pedido; o botão de estorno também vive lá.
- **[Emissão (GFSIS)](emissao-gfsis.md)**: é disparada automaticamente pela
  autorização do pagamento (`applyAuthorizedSideEffects`).
- **[Filas e Recuperação](filas-e-recuperacao.md)**: a reconciliação de
  pagamentos (`payments:reconcile`) e a fila de "reembolsos pendentes" na
  [Visão geral](visao-geral.md) leem diretamente `payments`/`refunds`.
- **[Formas de Pagamento](formas-de-pagamento.md)**: `discount_percentage`
  e `max_installments` de `payment_methods` são usados nos cálculos de
  cobrança.
