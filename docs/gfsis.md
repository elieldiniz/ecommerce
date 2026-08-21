# Integração com o GFSIS

> Documento técnico baseado exclusivamente no código-fonte deste repositório
> (estado em que a integração está implementada hoje). Trechos referenciam
> arquivo e, quando útil, linha. Segue o mesmo padrão de
> [`docs/safe2pay.md`](safe2pay.md).
>
> **Sobre a fonte externa**: o GFSIS ("Gestão Fácil") é a plataforma de
> gestão usada por Autoridades de Registro de certificado digital no Brasil
> — [gfsis.com.br](https://gfsis.com.br) descreve o produto ("GFSIS
> Certificados: automatize a gestão da sua AR"). Diferente do que uma versão
> anterior deste documento afirmava, o GFSIS **tem, sim**, uma documentação
> pública de API para desenvolvedores, em
> [gfsis.readme.io](https://gfsis.readme.io/reference/getting-started-with-your-api)
> — ela é usada abaixo como referência 🔗 sempre que existe uma página
> correspondente ao que o código implementa. Ela também documenta vários
> endpoints que a API do GFSIS oferece mas que **este código-fonte não
> chama** (consulta de pedido, cancelamento, consulta por CPF/CNPJ,
> certificados a vencer) — esses estão listados na seção 14, marcados como
> não implementados, para não sugerir que existe algo que não existe neste
> repositório.

## 1. Visão geral

O GFSIS é responsável por emitir o certificado digital depois que o pedido é
pago — é o segundo (e último) sistema externo da aplicação, depois da
Safe2Pay confirmar o pagamento. Diferente da Safe2Pay, o GFSIS não participa
do checkout: ele entra em cena só depois que o cliente preenche o formulário
de emissão com os dados do titular.

Toda a saída HTTP para o GFSIS passa por uma única classe,
`App\Support\Gfsis\GfsisClient` (`app/Support/Gfsis/GfsisClient.php`), que
comenta no próprio código o padrão de rotas da API:
`{urlGFSIS}/gestaofacil/rest/*`. Nenhuma outra classe do projeto chama
`Http::` diretamente contra o GFSIS.

Dois conceitos de "token" não devem ser confundidos:

- **Token de autenticação da API GFSIS** — obtido por `GfsisClient::auth()`,
  usado no header `Authorization: Bearer` de toda chamada ao GFSIS (seção 4).
- **Token de acesso à emissão** (`issuance_data.access_token`) — gerado
  localmente pela aplicação, enviado por e-mail ao cliente, usado para
  autorizar o preenchimento do formulário `pedido/{id}/emissao/` (seção 6).
  Não é usado em nenhuma chamada ao GFSIS.

## 2. Certificados suportados

O GFSIS não expõe "formas de pagamento" como a Safe2Pay — o que ele emite é
um certificado por variante de produto vendida. Cada `product_variants` tem
uma coluna `gfsis_certificado_id`, o identificador do produto/plano
correspondente **do lado do GFSIS**:

```php
// app/Models/ProductVariant.php
'product_id', 'certificate_format_id', 'gfsis_certificado_id', 'sku', 'validity_months', 'price', ...
```

Uma variante sem `gfsis_certificado_id` configurado bloqueia o registro do
pedido antes de qualquer chamada HTTP —
`GfsisRegistrationBlockedException` (seção 5). Não existe cobrança nem
parcelamento no lado do GFSIS: o valor (`certificado.valor`) é informativo,
vindo do preço já cobrado pela Safe2Pay (`order_item.unit_price`), não de uma
tabela de preços do GFSIS.

## 3. Endpoints do GFSIS utilizados

Todos encapsulados em `GfsisClient` (`app/Support/Gfsis/GfsisClient.php`):

| Método do client | Verbo + endpoint | Finalidade | Referência oficial 🔗 |
| --- | --- | --- | --- |
| `auth()` / `refreshToken()` | `POST /gestaofacil/rest/auth` | Obtém o token de acesso (Basic Auth com login/senha), cacheado até `expirationDate` | [Autenticação](https://gfsis.readme.io/reference/autenticação-1) |
| `criarPedidoVenda()` | `POST /gestaofacil/rest/CriaPedidoVendaLTS` | Registra o pedido de emissão do certificado | [Registrar pedido](https://gfsis.readme.io/reference/registrar-pedido) |

Não há, no código-fonte deste projeto, nenhuma chamada a endpoint de
**consulta** de pedido nem de **cancelamento** do lado do GFSIS — só os dois
acima são usados. Isso **não** é porque o GFSIS não os ofereça: a doc oficial
documenta `GET /rest/ConsultaPedidoVendaLTS` ("Situação de um pedido") e
`DELETE /rest/CancelaPedidoVenda` ("Cancela o pedido"), entre outros (seção
14) — este código-fonte simplesmente nunca os chama. O comentário em
`GfsisReconcileStuckOrders` (seção 8) que diz "não há endpoint de consulta de
pedido documentado" reflete o que era conhecido no momento em que aquele
código foi escrito, não o estado real da API — a consulta existe e está
documentada publicamente, apenas não foi (ainda) implementada aqui.

## 4. Autenticação e configuração da API

> Doc oficial: [Autenticação](https://gfsis.readme.io/reference/autenticação-1)
> e [Token de acesso](https://gfsis.readme.io/reference/token-de-acesso) —
> confirmam Basic Auth em `POST {{urlGFSIS}}/gestaofacil/rest/auth`, o campo
> de resposta `acessToken` (um JWT) e `expirationDate`, exatamente como lido
> por `GfsisClient::refreshToken()`.

Credenciais e URL são lidas exclusivamente de `config('services.gfsis.*')`
(`config/services.php`), nunca hardcoded:

```php
'gfsis' => [
    'login' => env('GFSIS_LOGIN'),
    'senha' => env('GFSIS_SENHA'),
    'base_url' => env('GFSIS_BASE_URL'),
],
```

Fluxo de autenticação (`GfsisClient`):

1. `auth()` lê o token do cache (`Cache::get('gfsis.access_token')`). Se
   presente, reutiliza — **nunca** busca um token novo a cada chamada.
2. Sem token em cache, `refreshToken()` chama `POST /gestaofacil/rest/auth`
   com Basic Auth (`login`/`senha` da config) e grava o token retornado
   (`acessToken`) no cache com expiração igual ao `expirationDate`
   retornado pelo próprio GFSIS — o TTL do cache não é um valor fixo do
   código, vem da resposta da API.
3. `criarPedidoVenda()` envia o token via `withToken()` (header
   `Authorization: Bearer`). Se a resposta for `401`, invalida o cache
   (`Cache::forget`) e repete a chamada **uma única vez** com um token
   recém-obtido — sem retry indefinido.

Nenhuma credencial é logada ou exposta a assets de frontend
(`GfsisSecurityTest` cobre isso — seção 12).

## 5. Registro do pedido — `RegisterOrderItemWithGfsis`

`app/Actions/Gfsis/RegisterOrderItemWithGfsis.php`, núcleo de todo o
registro, reaproveitado tanto pelo disparo automático quanto — em teoria —
por um reenvio manual (ver nota abaixo).

Elegibilidade (`execute()`, antes de qualquer chamada HTTP):

- `order.status.slug === 'paid'`.
- `order.fulfillmentStatus.slug` em `data_complete` **ou** `send_failed` —
  um pedido já marcado como falha continua retentável, sem exigir reset
  manual.
- A variante do produto precisa ter `gfsis_certificado_id` configurado,
  senão lança `GfsisRegistrationBlockedException`
  (`app/Exceptions/Gfsis/GfsisRegistrationBlockedException.php`) e grava a
  mensagem em `order_item_gfsis.last_error` sem chamar o GFSIS.

Fluxo:

1. Garante (`firstOrCreate`) uma linha em `order_item_gfsis` para o
   `order_item`, gerando `gfsis_order_id` **localmente**
   (`random_int(100000000, 999999999)`, único) — o GFSIS nunca atribui esse
   ID, é a aplicação quem gera e envia.
2. Monta o payload via `GfsisPayloadBuilder::build()` (seção 7).
3. Chama `GfsisClient::criarPedidoVenda($payload)`.
4. Interpreta a resposta (`{ codigo, erro }`):
   - Sucesso (`erro === false` com HTTP 2xx) **ou** código `002`
     (pedido duplicado, tratado como sucesso por idempotência) →
     `applySuccess()`: grava `gfsis_code`, `sent_at`, incrementa
     `attempts` (exceto em duplicata — não conta como nova tentativa),
     persiste `request_payload`/`response_payload`, move
     `order_item_gfsis.status` para `enviado_gfsis` e
     `order.fulfillment_status` para `sent_to_gfsis`.
   - Qualquer outro código → `applyFailure()`: grava `last_error` (mensagem
     legível via `GfsisErrorCode`), incrementa `attempts`, persiste os
     payloads, move `order.fulfillment_status` para `send_failed`.
   - Exceção na chamada HTTP (rede) → mesmo caminho de `applyFailure()`,
     com a mensagem da exceção.

> ⚠️ **"Reenviar ao GFSIS" no painel não está implementado.** O docblock da
> classe descreve reaproveitamento tanto pelo job automático quanto por um
> "reenvio manual síncrono do painel" — mas o botão correspondente em
> `resources/views/pages/painel/vendas/⚡show.blade.php:218`
> (`<button type="button">Reenviar ao GFSIS</button>`) não tem `wire:click`
> nem qualquer handler associado. Na prática, hoje, só o disparo automático
> via `RegisterOrderItemWithGfsisJob` registra pedidos de fato.

## 6. Dados de emissão e o link enviado ao cliente

Dois Actions cuidam do ciclo "coletar dados do titular → habilitar o envio
ao GFSIS":

### `GenerateIssuanceAccessToken`

`app/Actions/Gfsis/GenerateIssuanceAccessToken.php`. Chamada quando o pedido
é pago pela primeira vez (`ApplyPaymentStatusTransition`, ver
[`docs/safe2pay.md`](safe2pay.md#8-webhooks)):

- Cria (idempotente, `firstOrCreate`) uma linha em `issuance_data` por
  `order_item`, pré-preenchida com os dados do `Customer`/`CustomerAddress`
  primário e o `holder_type_id` do **produto** (o produto define se a
  emissão é PF ou PJ, não o cadastro do cliente).
- Gera `access_token` único (`Str::random(40)`) com TTL de 30 dias
  (`TOKEN_TTL_DAYS`).
- `filled_at` fica sempre `null` neste momento — só é gravado quando o
  cliente de fato submete o formulário.
- `regenerate()` gera um novo token com TTL renovado para o mesmo
  `order_item` (usado no reenvio de link, abaixo).

O e-mail com o link (`pedido/{id}/emissao/?token=...`,
`App\Mail\IssuanceAccessLinkMail`) é disparado automaticamente pela mesma
transição de pagamento que gera o token — sem depender de clique manual.

### Formulário de emissão — `ShowEmissaoController` / `StoreEmissaoController`

Rotas `GET`/`POST pedido/{id}/emissao/` (`routes/web.php`), protegidas por
`App\Http\Middleware\EnsureIssuanceAccessTokenIsValid`
(`app/Http/Middleware/EnsureIssuanceAccessTokenIsValid.php`):

- Resolve `IssuanceData` por `access_token` (querystring `?token=`) **e**
  `order_item->order_id` batendo com o `{id}` da rota — fecha o IDOR que
  existiria se `{id}` sozinho fosse aceito (é um inteiro sequencial
  adivinhável).
- `403` se o token estiver ausente, não corresponder a nenhuma
  `IssuanceData`, ou estiver expirado (`access_token_expires_at`).
- Anexa a `IssuanceData` resolvida em `$request->attributes` para reuso
  pelos controllers, evitando buscar duas vezes.

`StoreEmissaoController` valida os campos via `StoreIssuanceDataRequest`
(`app/Http/Requests/StoreIssuanceDataRequest.php`) — regras condicionais por
`?tipo=`: PF (padrão) exige titular + endereço; PJ (`?tipo=pj`) soma os
campos do responsável (`responsible_*`). Em sucesso, delega a
`MarkIssuanceDataComplete`.

### `MarkIssuanceDataComplete`

`app/Actions/Gfsis/MarkIssuanceDataComplete.php`. Verifica se **todos** os
campos obrigatórios do `holder_type` (PF ou PJ, mesma lista condicional
acima) estão preenchidos:

- Se sim: grava `filled_at`, move `orders.fulfillment_status` para
  `data_complete` e — lendo `orders.status_id` **fresco do banco** (nunca do
  estado em memória de quem chamou) — despacha
  `RegisterOrderItemWithGfsisJob` apenas se o pedido já estiver `paid`.
- Se não: não faz nada (o formulário simplesmente não avança até estar
  completo).

### `ResendIssuanceAccessLink`

`app/Actions/Gfsis/ResendIssuanceAccessLink.php`. Regenera o token (criando
a `IssuanceData` primeiro, se ainda não existir — caso de dado
legado/inconsistente) e reenvia o e-mail. O token é sempre regenerado, mesmo
quando o envio do e-mail falha — só o envio é reportado como malsucedido
(`false`), sem lançar exceção. Usada tanto por um reenvio manual quanto pelo
comando de reforço 24h (seção 8).

## 7. Payload de registro — `GfsisPayloadBuilder`

> Doc oficial: [Registrar pedido](https://gfsis.readme.io/reference/registrar-pedido).
> O payload documentado aceita mais campos do que este builder envia — em
> `pedido`, também `formaPagamento`, `pago`, `taxaDelivery`, `observacao`,
> `ac`, `tipoEmissaoSerpro`, `enviarAtendimentoVideoconferenciaAc` e
> `codigoVoucher`; a resposta documentada também inclui `mensagem` e
> `urlPagamento`, que este código não lê. `GfsisPayloadBuilder` envia
> apenas o subconjunto de que esta integração precisa — os demais campos são
> opcionais na doc oficial, não um endpoint diferente.

`app/Support/Gfsis/GfsisPayloadBuilder.php`, monta o corpo de
`POST /gestaofacil/rest/CriaPedidoVendaLTS` a partir de
`OrderItemGfsis`/`IssuanceData`/`ProductVariant`:

```
{
  "pedido": { "id", "data", "pontoAtendimento", "tipoValidacao" },
  "cliente": { "nome", "cpf" | "cnpj", "email", "telefone", "logradouro", "numero",
               "complemento", "bairro", "municipio", "uf", "cep",
               "codigoIbge"?, "dataNascimento"? },
  "certificado": { "id", "valor" }
}
```

- `pedido.id` é sempre `order_item_gfsis.gfsis_order_id` **já gravado**
  (gerado localmente antes da primeira chamada — seção 5), nunca gerado
  dentro do builder.
- `pedido.pontoAtendimento` e `pedido.tipoValidacao` vêm de
  `settings` (`gfsis_ponto_atendimento`, `gfsis_tipo_validacao`) —
  configuráveis sem deploy.
- `cliente.cpf` ou `cliente.cnpj` é escolhido pelo tamanho de
  `issuance_data.document` (11 dígitos → `cpf`, senão `cnpj`) — nunca os
  dois juntos.
- `cliente.codigoIbge` só entra se `issuance_data.ibge_code` estiver
  preenchido.
- `cliente.dataNascimento` só entra se `issuance_data.birth_date` estiver
  preenchido — a ausência **nunca bloqueia** a montagem do payload.
- `certificado.id` é `product_variant.gfsis_certificado_id`;
  `certificado.valor` é `order_item.unit_price` formatado com 2 casas
  decimais (`number_format(..., 2, '.', '')`) — não vem de uma tabela de
  preços do GFSIS.

## 8. Reconciliação e reforço de e-mail (comandos agendados)

### `gfsis:reconcile-stuck` — `GfsisReconcileStuckOrders`

`app/Console/Commands/GfsisReconcileStuckOrders.php`. Identifica
`order_item_gfsis` com `status = enviado_gfsis` cuja `sent_at` ultrapassa
`settings.gfsis_stuck_threshold_hours` (padrão `48`, seed
`database/seeders/SettingSeeder.php`). Para cada linha presa: registra um
log estruturado (`gfsis.stuck_order_item`) e lista em uma tabela no
terminal. **Nenhuma chamada HTTP é feita** neste código — este comando é
puramente observacional; a correção de um pedido preso depende de ação
humana fora deste comando. Isso não é uma limitação do GFSIS (a API expõe
[`GET /rest/ConsultaPedidoVendaLTS`](https://gfsis.readme.io/reference/situação-de-um-pedido),
ver seção 3/14) — é uma decisão de escopo deste comando, que ainda não foi
estendido para consultar automaticamente.

### `recuperacao:reforco-24h` — `RecuperacaoEnviarReforcoEmail24h`

`app/Console/Commands/RecuperacaoEnviarReforcoEmail24h.php`. Não chama o
GFSIS diretamente, mas faz parte do mesmo funil de emissão: identifica
pedidos `paid` + `awaiting_data` cujo `paid_at` ultrapassa
`settings.recovery_reinforcement_email_threshold_hours`, e reenvia o link de
emissão via `ResendIssuanceAccessLink` — idempotente por pedido, marcado em
`integration_queue` (job `recovery_email_24h`) para nunca reenviar duas
vezes. Uma falha em um pedido (logada, `recuperacao.reforco_24h_falhou`)
não interrompe o processamento dos demais na mesma execução.

## 9. Webhooks

> Doc oficial: [Status do pedido alterado](https://gfsis.readme.io/reference/status-do-pedido-alterado) —
> confirma que o GFSIS envia `POST` a cada mudança de status e espera `200`
> de volta. O payload documentado é bem mais rico do que o que este código
> interpreta: além de `identificador`/`status`/`dataAtualizacao`/`dataValidade`
> (os únicos campos lidos por `ApplyGfsisStatusTransition`, abaixo), também
> traz `dataCriacao`, `nomeCliente`, `cpfCnpjCliente`, `nomeVendedor`,
> `emailVendedor`, `nomeProduto`, `protocolo`, `tipoEmissao`, `ac`, dados de
> endereço/contato/representante legal, `listaDocumentos` e informações
> financeiras. Nada disso é descartado — `GfsisWebhookController` grava o
> `payload` bruto inteiro em `gfsis_events.payload` (JSON) — só não é
> **interpretado** para atualizar `order_item_gfsis`.

### Recepção — `GfsisWebhookController`

`app/Http/Controllers/Webhooks/GfsisWebhookController.php`, rota
`POST /webhooks/gfsis` (`routes/web.php`). Mesmo padrão do webhook Safe2Pay
(ver [`docs/safe2pay.md`](safe2pay.md#8-webhooks)):

1. Decodifica o corpo bruto; JSON inválido → `422`.
2. Grava o payload bruto em `gfsis_events` **incondicionalmente** — mesmo
   quando `identificador` está ausente do payload ou não corresponde a
   nenhum `order_item_gfsis.gfsis_order_id` conhecido (a FK entre
   `gfsis_events.gfsis_order_id` e `order_item_gfsis.gfsis_order_id` foi
   removida e a coluna tornada nullable pela migration
   `2026_08_18_000002_drop_foreign_and_make_nullable_gfsis_order_id_on_gfsis_events_table.php`).
3. Idempotência por `event_hash` (SHA-256 do corpo bruto): `firstOrCreate`
   garante que reenvios do mesmo corpo nunca despacham um novo job.
4. Evento novo → despacha `App\Jobs\ProcessGfsisWebhookJob`.
5. Responde `200 { "received": true }` imediatamente.

### Processamento — `ProcessGfsisWebhookJob`

`app/Jobs/ProcessGfsisWebhookJob.php`, fila `database`. Localiza o
`order_item_gfsis` pelo `gfsis_order_id` do evento:

- `gfsis_order_id` nulo ou sem correspondência → grava
  `gfsis_events.error` e `processed_at`, sem lançar exceção (evento órfão).
- Correspondência encontrada → delega a
  `App\Actions\Gfsis\ApplyGfsisStatusTransition::execute()` dentro de um
  `try/catch` que **nunca deixa uma exceção travar a fila**: qualquer
  `\Throwable` é gravado em `gfsis_events.error`.
- `processed_at` é sempre gravado ao final, independentemente do resultado.

### Interpretação do status — `ApplyGfsisStatusTransition`

`app/Actions/Gfsis/ApplyGfsisStatusTransition.php`. Mapeia o campo `status`
bruto do payload do webhook para o slug interno de `gfsis_statuses`:

| Status bruto do GFSIS | Slug interno (`gfsis_statuses`) |
| --- | --- |
| `CRIADO` | `enviado_gfsis` |
| `ENVIADO` | `enviado_gfsis` |
| `APROVADO` | `aprovado` |
| `EMITIDO` | `emitido` |
| `RECUSADO` | `falha_envio` |
| `CANCELADO` | `cancelado` |

Regras da transição:

- Só aplica se `payload.dataAtualizacao` for **estritamente mais recente**
  que `order_item_gfsis.status_synced_at` já gravado — protege contra
  eventos que chegam fora de ordem (nunca regride o status).
- Formato de data primário: `d/m/Y` para `dataAtualizacao` (hora zerada,
  `startOfDay()`) e `d/m/Y H:i` para `dataValidade`. Quando o valor não
  bate exatamente com o formato esperado, cai para `Carbon::parse()` como
  fallback defensivo e registra um `Log::warning`.
- `dataValidade`, quando presente, é gravado em
  `order_item_gfsis.certificate_expires_at` sob a mesma condição de não
  regressão.
- Um `status` bruto fora da tabela acima simplesmente não atualiza
  `status_id` (mas `status_synced_at` ainda é atualizado, se a data for mais
  recente).

## 10. Classes, Actions, Jobs, Controllers e Models envolvidos

| Componente | Caminho | Responsabilidade |
| --- | --- | --- |
| `GfsisClient` | `app/Support/Gfsis/GfsisClient.php` | Único ponto de saída HTTP para o GFSIS (auth + registro de pedido) |
| `GfsisPayloadBuilder` | `app/Support/Gfsis/GfsisPayloadBuilder.php` | Monta o payload de `CriaPedidoVendaLTS` |
| `GfsisErrorCode` | `app/Support/Gfsis/GfsisErrorCode.php` | Código de erro do GFSIS → mensagem legível |
| `RegisterOrderItemWithGfsis` | `app/Actions/Gfsis/RegisterOrderItemWithGfsis.php` | Núcleo do registro do pedido no GFSIS |
| `GenerateIssuanceAccessToken` | `app/Actions/Gfsis/GenerateIssuanceAccessToken.php` | Cria `IssuanceData` e gera/regenera o token de emissão |
| `MarkIssuanceDataComplete` | `app/Actions/Gfsis/MarkIssuanceDataComplete.php` | Marca dados completos e dispara o registro quando pago |
| `ResendIssuanceAccessLink` | `app/Actions/Gfsis/ResendIssuanceAccessLink.php` | Regenera o token e reenvia o e-mail de emissão |
| `ApplyGfsisStatusTransition` | `app/Actions/Gfsis/ApplyGfsisStatusTransition.php` | Interpreta o status do webhook e aplica a transição |
| `ShowEmissaoController` / `StoreEmissaoController` | `app/Http/Controllers/Pedido/` | Exibem/recebem o formulário de emissão |
| `EnsureIssuanceAccessTokenIsValid` | `app/Http/Middleware/EnsureIssuanceAccessTokenIsValid.php` | Valida `?token=` e fecha o IDOR da rota de emissão |
| `StoreIssuanceDataRequest` | `app/Http/Requests/StoreIssuanceDataRequest.php` | Validação condicional PF/PJ do formulário |
| `GfsisWebhookController` | `app/Http/Controllers/Webhooks/GfsisWebhookController.php` | Recepção do webhook |
| `ProcessGfsisWebhookJob` | `app/Jobs/ProcessGfsisWebhookJob.php` | Processamento assíncrono do webhook |
| `RegisterOrderItemWithGfsisJob` | `app/Jobs/RegisterOrderItemWithGfsisJob.php` | Disparo assíncrono do registro |
| `GfsisReconcileStuckOrders` | `app/Console/Commands/GfsisReconcileStuckOrders.php` | Comando agendado — sinaliza pedidos presos (sem chamar o GFSIS) |
| `RecuperacaoEnviarReforcoEmail24h` | `app/Console/Commands/RecuperacaoEnviarReforcoEmail24h.php` | Comando agendado — reforço de e-mail de emissão |
| `IssuanceAccessLinkMail` | `app/Mail/IssuanceAccessLinkMail.php` | E-mail com o link de emissão |
| `OrderItemGfsis` | `app/Models/OrderItemGfsis.php` | Estado do registro do pedido no GFSIS por item |
| `IssuanceData` | `app/Models/IssuanceData.php` | Dados do titular/responsável coletados para a emissão |
| `GfsisEvent` | `app/Models/GfsisEvent.php` | Log bruto de cada webhook recebido |
| `GfsisStatus` | `app/Models/GfsisStatus.php` | Catálogo dos 5 status internos (`enviado_gfsis`, `aprovado`, `emitido`, `falha_envio`, `cancelado`) |
| `GfsisRegistrationBlockedException` | `app/Exceptions/Gfsis/GfsisRegistrationBlockedException.php` | Bloqueio local antes de qualquer chamada HTTP (hoje: `gfsis_certificado_id` ausente) |

## 11. Fluxo geral da integração

```mermaid
sequenceDiagram
    participant Safe2Pay as Webhook Safe2Pay (pago)
    participant App as ApplyPaymentStatusTransition
    participant Cliente
    participant Emissao as Show/StoreEmissaoController
    participant Mark as MarkIssuanceDataComplete
    participant Job as RegisterOrderItemWithGfsisJob
    participant GFSIS as GFSIS API
    participant Webhook as GfsisWebhookController

    Safe2Pay->>App: pedido autorizado pela primeira vez
    App->>App: GenerateIssuanceAccessToken cria issuance_data (filled_at=null)
    App->>Cliente: e-mail com link pedido/{id}/emissao/?token=...

    Cliente->>Emissao: GET (token validado por EnsureIssuanceAccessTokenIsValid)
    Cliente->>Emissao: POST formulário de emissão (PF/PJ)
    Emissao->>Mark: MarkIssuanceDataComplete::execute()
    alt todos campos obrigatórios preenchidos
        Mark->>Mark: filled_at = now(); fulfillment_status = data_complete
        Mark->>Job: dispatch (só se order.status === paid)
    end

    Job->>GFSIS: POST /gestaofacil/rest/CriaPedidoVendaLTS
    GFSIS-->>Job: { codigo, erro }
    Job->>Job: sucesso ou 002 (duplicado) → enviado_gfsis; senão → send_failed

    GFSIS->>Webhook: POST /webhooks/gfsis (status assíncrono)
    Webhook->>Webhook: grava gfsis_events (idempotente por event_hash)
    Webhook->>Webhook: dispatch ProcessGfsisWebhookJob (só em evento novo)
    Webhook->>Webhook: ApplyGfsisStatusTransition (CRIADO/ENVIADO, APROVADO, EMITIDO, RECUSADO, CANCELADO)
```

Rede de segurança: `gfsis:reconcile-stuck` sinaliza pedidos presos em
`enviado_gfsis` (sem chamar o GFSIS, seção 8); `recuperacao:reforco-24h`
reforça o e-mail de emissão para pedidos pagos que ainda não avançaram.

## 12. Tratamento de erros e respostas da API

> Doc oficial: [Erros](https://gfsis.readme.io/reference/códigos-de-erro) —
> documenta o formato de resposta de erro (`erro`, `codigo`, `mensagem`); não
> foi possível confirmar a partir dela, de forma automatizada, se a tabela
> completa de códigos bate 1:1 com os 6 mapeados em `GfsisErrorCode`
> (`app/Support/Gfsis/GfsisErrorCode.php`) — os 6 códigos abaixo (`001`,
> `002`, `003`, `005`, `006`, `999`) são os confirmados diretamente no
> código-fonte, que continua sendo a fonte da verdade desta tabela.

| Situação | Onde é tratada | Comportamento |
| --- | --- | --- |
| Variante sem `gfsis_certificado_id` | `RegisterOrderItemWithGfsis`, antes de qualquer chamada HTTP | `GfsisRegistrationBlockedException`, grava `last_error`, nunca chama o GFSIS |
| Código de erro `001`/`003`/`005`/`006`/`999` | `RegisterOrderItemWithGfsis` (via `GfsisErrorCode`) | Move `fulfillment_status` para `send_failed`, grava mensagem legível em `last_error`, incrementa `attempts` |
| Código `002` (pedido duplicado) | `RegisterOrderItemWithGfsis` (via `GfsisErrorCode::isDuplicateSuccess()`) | Tratado como **sucesso** — idempotência, sem incrementar `attempts` nem reenviar |
| Falha de rede/exceção na chamada de registro | `RegisterOrderItemWithGfsis` | Mesmo caminho de falha, mensagem da exceção em `last_error` |
| `401` em `criarPedidoVenda()` | `GfsisClient` | Invalida o token em cache e repete a chamada **uma única vez** com token novo |
| `status` bruto do webhook fora da tabela mapeada | `ApplyGfsisStatusTransition` | `status_id` não é alterado; `status_synced_at` ainda avança se a data for mais recente |
| `dataAtualizacao`/`dataValidade` fora do formato primário | `ApplyGfsisStatusTransition` | Fallback `Carbon::parse()` + `Log::warning` |
| Evento de webhook com `identificador` desconhecido/ausente | `ProcessGfsisWebhookJob` | Grava `gfsis_events.error` e `processed_at`, sem lançar exceção |
| Qualquer exceção dentro do processamento do webhook | `ProcessGfsisWebhookJob` | Capturada, gravada em `gfsis_events.error` — nunca propaga e trava a fila |
| Corpo do webhook não é JSON válido | `GfsisWebhookController` | Responde `422` |
| Falha ao enviar o e-mail de emissão (manual ou reforço 24h) | `ResendIssuanceAccessLink` | Token ainda é regenerado; envio reportado como `false`/logado, sem lançar exceção |

## 13. Testes relacionados à integração

| Arquivo | Cobre |
| --- | --- |
| `tests/Unit/Support/Gfsis/GfsisClientTest.php` | Reuso do token cacheado, renovação em `401` antes de repetir a chamada original, headers de Basic Auth (`auth`) vs Bearer (`criarPedidoVenda`) |
| `tests/Unit/Support/Gfsis/GfsisErrorCodeTest.php` | `002` é o único código de sucesso-duplicado, mensagem do `005`, `fromCode()` retorna `null` para código desconhecido/nulo, resolução dos códigos conhecidos |
| `tests/Unit/Support/Gfsis/GfsisPayloadBuilderTest.php` | Todos os campos do payload lidos das colunas esperadas, `cpf` vs `cnpj` por tamanho do documento, `dataNascimento` ausente não bloqueia, `certificado.valor` sempre com 2 casas decimais |
| `tests/Unit/Support/Gfsis/ServicesConfigTest.php` | Config reflete as variáveis de ambiente; nenhum asset de frontend expõe as credenciais |
| `tests/Feature/Jobs/RegisterOrderItemWithGfsisJobTest.php` | Pedido não pago nunca chama a API, pedido pago mas sem dados completos nunca chama, `gfsis_certificado_id` ausente bloqueia antes do HTTP, mesmo `pedido.id` em duas execuções, sucesso grava `gfsis_code`/status, código `005` grava erro e move para `send_failed`, um pedido `send_failed` é retentável, código `002` não altera `last_error`/`attempts` |
| `tests/Feature/Http/Webhooks/GfsisWebhookControllerTest.php` | Evento com `identificador` conhecido/desconhecido/ausente sempre é gravado, reenvio do mesmo corpo nunca duplica o job, resposta `200` imediata, JSON inválido responde `422` |
| `tests/Feature/Jobs/ProcessGfsisWebhookJobTest.php` | Transição aplicada com `dataAtualizacao` válida, bloqueio por não-regressão ainda grava `processed_at`, evento órfão (`gfsis_order_id` nulo ou desconhecido) não lança exceção, `dataAtualizacao` malformada mesmo com fallback ainda registra erro sem lançar |
| `tests/Unit/Actions/Gfsis/ApplyGfsisStatusTransitionTest.php` | Data igual/anterior não altera nada, data posterior aplica update incluindo validade do certificado, mapeamento de cada `status` bruto para o slug esperado, parsing no formato primário sem cair no fallback, fallback `Carbon::parse()` com log de aviso para formato inesperado |
| `tests/Unit/Actions/Gfsis/GenerateIssuanceAccessTokenTest.php` | Uma linha de `issuance_data` por item, idempotência ao chamar duas vezes, tokens únicos entre pedidos, `regenerate()` produz token diferente com TTL renovado |
| `tests/Unit/Actions/Gfsis/MarkIssuanceDataCompleteTest.php` | Dados completos em pedido pago despacha o job de registro, dados completos em pedido não pago marca completo mas não despacha, dados incompletos não marca nem despacha |
| `tests/Unit/Actions/Gfsis/ResendIssuanceAccessLinkTest.php` | Regenera token e envia e-mail, e-mail malformado não lança exceção mas ainda regenera o token e reporta falha |
| `tests/Feature/Console/GfsisReconcileStuckOrdersTest.php` | Item preso além do limiar aparece na saída e é logado, item dentro do limiar não aparece, item com status diferente não aparece mesmo antigo, **nenhuma chamada HTTP é feita**, limiar é lido da config sem exigir deploy |
| `tests/Feature/Console/RecuperacaoEnviarReforcoEmail24hTest.php` | Cobertura do comando de reforço 24h (idempotência, limiar configurável) |
| `tests/Feature/Pages/Pedido/EmissaoTest.php` | Acesso sem token/token inválido/de outro pedido/expirado é `403`, token válido renderiza e pré-preenche o formulário (PF e PJ), submissão válida persiste `issuance_data`, submissão incompleta não altera nada, submissão completa em pedido pago dispara o job de registro, em pedido não pago não dispara |
| `tests/Feature/CrossCutting/GfsisSecurityTest.php` | Nenhum asset de frontend (fonte ou build) expõe a senha do GFSIS, `GfsisClient` nunca hardcoda a `base_url` fora do ponto de configuração, `auth()`/`criarPedidoVenda()` usam a URL configurada, o webhook responde `200` sem esperar o job, o job de webhook é sempre despachado para a fila, nunca executado inline |
| `tests/Unit/GfsisStatusSeederTest.php` | Seeder cria/atualiza os 5 status esperados |
| `tests/Unit/Migrations/AddGfsisCertificadoIdToProductVariantsTest.php` | Coluna `gfsis_certificado_id` existe em `product_variants` |
| `tests/Unit/Migrations/DropForeignAndMakeNullableGfsisOrderIdOnGfsisEventsTest.php` | `gfsis_events.gfsis_order_id` é nullable e sem FK, permitindo eventos órfãos |

## 14. Referências oficiais do GFSIS

Links para a documentação pública de API do GFSIS
([gfsis.readme.io](https://gfsis.readme.io/reference/getting-started-with-your-api)),
organizados por assunto. **Onde a doc oficial e o código deste repositório
divergem, o código é a fonte da verdade** — os links abaixo são material de
apoio, não substituem os arquivos referenciados nas seções acima.

| Assunto | Link | Implementado neste código? |
| --- | --- | --- |
| Primeiros passos | [Getting started](https://gfsis.readme.io/reference/getting-started-with-your-api) | — |
| Autenticação | [Autenticação](https://gfsis.readme.io/reference/autenticação-1) | ✅ `GfsisClient::auth()` |
| Token de acesso | [Token de acesso](https://gfsis.readme.io/reference/token-de-acesso) | ✅ `GfsisClient::auth()` |
| Registrar pedido | [Registrar pedido](https://gfsis.readme.io/reference/registrar-pedido) | ✅ `GfsisClient::criarPedidoVenda()` |
| Situação de um pedido | [Situação de um pedido](https://gfsis.readme.io/reference/situação-de-um-pedido) | ❌ não chamado — `GfsisReconcileStuckOrders` só loga (seção 8) |
| Consulta último pedido por CPF | [Consulta último pedido CPF](https://gfsis.readme.io/reference/consulta-último-pedido-cpf) | ❌ não implementado |
| Consulta último pedido por CNPJ | [Consulta último pedido CNPJ](https://gfsis.readme.io/reference/consulta-último-pedido-cnpj) | ❌ não implementado |
| Pedido válido por cliente | [Pedido válido por cliente](https://gfsis.readme.io/reference/pedido-válido-por-cliente) | ❌ não implementado |
| Cancela o pedido | [Cancela o pedido](https://gfsis.readme.io/reference/cancela-o-pedido) | ❌ não implementado — não existe cancelamento/estorno do lado do GFSIS neste código |
| Status do pedido alterado (webhook) | [Status do pedido alterado](https://gfsis.readme.io/reference/status-do-pedido-alterado) | ✅ `GfsisWebhookController` + `ApplyGfsisStatusTransition` (parcial — só 4 dos campos do payload são interpretados, seção 9) |
| Códigos de erro | [Erros](https://gfsis.readme.io/reference/códigos-de-erro) | ✅ `GfsisErrorCode` mapeia 6 códigos |
| Certificados a vencer | [Certificados a vencer](https://gfsis.readme.io/reference/certificados-a-vencer) | ❌ não implementado |
| Certificados a vencer por período | [Certificados a vencer por período](https://gfsis.readme.io/reference/certificados-a-vencer-por-período) | ❌ não implementado |

Índice completo (todas as páginas da doc oficial, para busca livre):
[gfsis.readme.io/llms.txt](https://gfsis.readme.io/llms.txt).
