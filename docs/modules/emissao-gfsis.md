# Emissão (GFSIS)

[← Voltar ao índice de módulos](README.md)

## Finalidade

Depois que o pagamento é confirmado, este módulo coleta os dados do titular
do certificado (PF ou PJ) e registra o pedido junto à GFSIS — o sistema
externo responsável pela emissão do Certificado Digital em si (validação
por videoconferência, agendamento, emissão).

- **Integração externa**: GFSIS — detalhes de payload/endpoints em
  [`docs/gfsis.md`](../gfsis.md).
- **Rotas públicas**: `pedido/{id}/emissao/` (GET/POST), protegidas por
  token de acesso — não exigem login.
- **Webhook**: `webhooks/gfsis` (sem CSRF).

## Funcionalidades

- **Link de acesso à emissão**: gerado automaticamente por e-mail assim
  que o pedido é pago; pode ser reenviado manualmente
  ([Filas e Recuperação](filas-e-recuperacao.md)).
- **Formulário de dados de emissão**: campos de titular (PF) ou
  titular + responsável legal (PJ), endereço.
- **Registro na GFSIS**: envio automático assim que os dados estiverem
  completos e o pedido pago; reenvio manual em caso de falha
  ("Corrigir e reenviar").
- **Recebimento de status da GFSIS via webhook**: aprovado, emitido,
  recusado, cancelado — atualiza o andamento em tempo real.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `IssuanceData` | Uma linha por `OrderItem` — dados de titular/endereço + token de acesso de 40 caracteres. |
| `OrderItemGfsis` | Uma linha por `OrderItem` — status, `gfsis_order_id`, tentativas, payload de request/response, agendamento e data de vencimento do certificado. |
| `GfsisEvent` | Log bruto de cada webhook recebido da GFSIS (idempotência por `event_hash`). |
| `GfsisStatus` | Lookup: `enviado_gfsis`, `aprovado`, `emitido`, `falha_envio`, `cancelado`. |
| `ProductVariant.gfsis_certificado_id` | ID do produto de certificado configurado na GFSIS — sem ele, o registro é bloqueado. |

## Principais fluxos

### 1. Geração do token de acesso (ao pagar)

`GenerateIssuanceAccessToken::execute()` roda dentro de
`ApplyPaymentStatusTransition::applyAuthorizedSideEffects()` (ver
[Pagamentos e Estornos](pagamentos-e-estornos.md)): cria (idempotente,
`firstOrCreate`) a `IssuanceData` de cada item, pré-preenchendo os campos
obrigatórios a partir do `Customer`/`CustomerAddress` primário e do
`holder_type_id` **do produto comprado** (não do cadastro do cliente — o
produto define se a emissão é PF ou PJ). `filled_at` fica `null` até o
cliente efetivamente enviar o formulário. Um e-mail
(`IssuanceAccessLinkMail`) com o link `pedido/{id}/emissao/?token=...` é
enviado ao cliente.

### 2. Preenchimento pelo cliente

`EnsureIssuanceAccessTokenIsValid` (middleware) resolve a `IssuanceData`
pelo `?token=` + `order_id` da rota, e recusa (403) token ausente,
inválido ou expirado — fecha o IDOR da rota (qualquer um com o link não
consegue ver dados de outro pedido). `StoreIssuanceDataRequest` valida os
campos condicionalmente por `?tipo=pj`. Ao salvar,
`MarkIssuanceDataComplete::execute()` verifica se todos os campos
obrigatórios (PF; +PJ se aplicável) estão preenchidos — se sim, grava
`filled_at`, move `orders.fulfillment_status = data_complete` e, **só se o
pedido já estiver `paid`**, despacha `RegisterOrderItemWithGfsisJob`.

### 3. Registro na GFSIS

`RegisterOrderItemWithGfsis::execute()` é o núcleo, reutilizado tanto pelo
disparo automático (job assíncrono) quanto pelo reenvio manual síncrono no
painel. Elegível quando `orders.fulfillment_status` é `data_complete` OU
`send_failed` (uma falha anterior continua retentável, sem travar até
reset manual). Bloqueia se a variante não tiver `gfsis_certificado_id`
configurado (`GfsisRegistrationBlockedException`). Em sucesso, marca
`order_item_gfsis.status = enviado_gfsis` e
`orders.fulfillment_status = sent_to_gfsis`; em falha, marca
`send_failed` e grava `last_error`.

### 4. Atualizações via webhook

`POST webhooks/gfsis` grava o payload bruto em `gfsis_events`
incondicionalmente (mesmo sem `order_item_gfsis` correspondente),
idempotente por `event_hash`, e delega a `ProcessGfsisWebhookJob` →
`ApplyGfsisStatusTransition`, que só aplica a transição se
`dataAtualizacao` do payload for mais recente que a já gravada (nunca
regride por data).

## Como o usuário interage

- **Cliente**: recebe o e-mail, abre o link (sem precisar de login),
  preenche os dados do titular (e do responsável, se PJ). Acompanha o
  andamento em ["Minha conta" → "Meus pedidos"](clientes.md).
- **Time interno**: acompanha e corrige falhas em
  [Filas e Recuperação](filas-e-recuperacao.md) (reenviar link de emissão,
  "Corrigir e reenviar" ao GFSIS) e vê o histórico completo no detalhe do
  pedido em [Vendas](vendas.md).

## Regras de negócio importantes

- **Mapeamento de status do webhook**: `CRIADO` e `ENVIADO` mapeiam para o
  mesmo slug interno `enviado_gfsis` — a linha do tempo do pedido
  (ver [Vendas](vendas.md)) ignora esses dois eventos porque já são
  representados por `order_item_gfsis.sent_at` (o momento em que *nós*
  enviamos); só `APROVADO`/`EMITIDO`/`RECUSADO`/`CANCELADO` viram eventos
  visíveis, qualquer status desconhecido é silenciosamente ignorado.
- **Não existe status "Videoconferência realizada"** — o mais próximo é
  `APROVADO`.
- **Sucesso duplicado é tratado como sucesso**: se a GFSIS responder um
  código de erro que signifique "este pedido já foi criado" (visto em
  `App\Support\Gfsis\GfsisErrorCode::isDuplicateSuccess()`), o registro é
  considerado bem-sucedido sem incrementar `attempts`.
- **Autenticação da API cacheada**: `GfsisClient::auth()` cacheia o token
  respeitando a expiração informada pela própria GFSIS e nunca autentica
  de novo a cada chamada; em `401`, invalida o cache e tenta uma única vez
  com um token novo.

## Relação com outros módulos

- **[Pagamentos e Estornos](pagamentos-e-estornos.md)**: é disparado pela
  primeira autorização de pagamento do pedido.
- **[Vendas](vendas.md)**: a linha do tempo do detalhe do pedido é montada
  juntando datas de `issuance_data`, `order_item_gfsis` e `gfsis_events`.
- **[Filas e Recuperação](filas-e-recuperacao.md)**: consome exatamente as
  filas geradas por este módulo (`awaiting_data` parado, `send_failed`).
- **[Clientes](clientes.md)**: "Meus pedidos" na área do cliente reflete o
  estado de `order_item_gfsis`/`issuance_data` de cada item comprado.
- **[Produtos](produtos.md)**: `gfsis_certificado_id`, configurado por
  variante no cadastro de produto, é obrigatório para o registro funcionar.
