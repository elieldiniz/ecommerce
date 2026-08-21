# Auditoria de Segurança — 2026-08-21

> Documento gerado a partir de uma auditoria de segurança completa do código-fonte
> deste repositório, com foco em requisições recebidas pela aplicação e dados
> fornecidos por usuários maliciosos. Rastreou-se o fluxo
> `Request → Route → Middleware → Validation → Controller/Livewire → Action → Model/DB → Response`
> em cada ponto sensível. Nenhum ataque real foi executado — análise estática de código.
>
> Use este documento como checklist de correção: vá item por item, na ordem de
> prioridade, marque `[x]` quando corrigido e testado, e adicione uma nota curta
> (commit/PR) abaixo do item corrigido.

## Como usar este documento

1. Corrija **um item por vez**, começando pelos `P0`.
2. Depois de corrigir, rode/atualize os testes relacionados (`vendor/bin/sail artisan test --compact --filter=...`).
3. Marque o checkbox do item e registre a referência do commit/PR na linha "Correção aplicada em:".
4. Não pule itens `P0`/`P1` — eles têm exploração viável sem credenciais ou com credenciais mínimas.

---

## Checklist rápido (status)

- [ ] 1. IDOR crítico em `pedido/{id}/pagamento/` — vazamento de token de emissão (**Critical / P0**)
- [ ] 2. Webhooks Safe2Pay/GFSIS sem verificação de autenticidade (**Critical / P0**)
- [ ] 3. Painel acessível via autorregistro + papel nunca aplicado às rotas (**Critical / P0**)
- [ ] 4. Sem rate limit no login/registro/recuperação de senha de clientes (**High / P1**)
- [ ] 5. Ausência de Policies / autorização a nível de objeto (**High / P1**)
- [ ] 6. Sem rate limit em `checkout/tokenizar-cartao` (card testing) (**Medium / P2**)
- [ ] 7. Disparo de jobs/e-mails em massa sem limite no painel (**Medium / P2**)
- [ ] 8. Relatórios sem paginação/streaming em "todo o período" (**Medium / P2**)
- [ ] 9. `AddToCart` não valida variante inexistente/inativa (**Low-Medium / P2**)
- [ ] 10. Mutação de estado via `GET` em `carrinho/adicionar/{id}` (**Low / P3**)
- [ ] 11. Campo `document` do checkout sem validação de formato CPF/CNPJ (**Low / P3**)

---

## Resumo executivo

| Severidade | Quantidade |
|---|---|
| Critical | 3 |
| High | 2 |
| Medium | 4 |
| Low | 2 (+1 adicional) |
| **Total** | **11** |

**Principais riscos:**
1. Um pedido de pagamento pode ser acessado/pago por qualquer pessoa via ID sequencial, vazando inclusive o token de emissão do certificado digital de terceiros.
2. Os webhooks de pagamento (Safe2Pay) e de emissão fiscal (GFSIS) aceitam qualquer POST sem verificar se veio realmente do provedor, permitindo forjar liberação de produto sem pagamento.
3. Qualquer visitante pode se autorregistrar e, após confirmar e-mail, acessar 100% do painel administrativo (dados de clientes, financeiro, configurações), porque a segregação de papéis existe no código mas nunca é aplicada às rotas.

---

## Vulnerabilidades

### 1. IDOR crítico em `pedido/{id}/pagamento/` — vazamento de dados financeiros e do token de emissão de terceiros

- **Severidade:** Critical
- **Prioridade:** P0 — corrigir imediatamente, explorável sem qualquer credencial.
- **Localização:** `routes/web.php:27`; `resources/views/pages/pedido/⚡pagamento.blade.php` (métodos `mount()`, `order()`, bloco de redirecionamento ~linhas 106-112)
- **Vetor de ataque:** A rota está fora de qualquer grupo de autenticação. `id` é o auto-incremento da tabela `orders`. Basta visitar `/pedido/1/pagamento/`, `/pedido/2/pagamento/`, etc. O componente faz `Order::query()->find($this->id)` sem nenhuma checagem de posse (sessão de checkout, cookie, ownership do cliente autenticado).
- **Impacto:** Vazamento de QR code PIX, linha digitável de boleto e valor de pedidos de terceiros. Mais grave: quando o pedido está `paid`, o componente redireciona automaticamente com `route('pedido.emissao', ['id' => ..., 'token' => $issuanceData->access_token])` — expondo na URL o token de emissão do certificado digital de outra pessoa, que dá acesso a `ShowEmissaoController`/`StoreEmissaoController` para ler/preencher CPF, endereço e demais dados pessoais de emissão. O método `tentarNovamente()` ainda permite gerar uma nova tentativa de cobrança para o pedido alheio.
- **Evidência:**
  ```php
  public function mount(int $id): void { $this->id = $id; } // sem verificação de posse

  #[Computed]
  public function order(): ?Order { return Order::query()->with([...])->find($this->id); }

  // ...
  if ($order->status?->slug === 'paid') {
      $this->redirect(route('pedido.emissao', ['id' => $order->id, 'token' => $issuanceData->access_token]));
  }
  ```
- **Correção recomendada:** amarrar o acesso a uma prova além do `{id}` da URL — gravar o `order_id`/token de checkout em sessão no momento da criação do pedido e validar em `mount()` (`abort_unless` comparando com a sessão OU com `Auth::guard('customer')->id() === $order->customer_id`, 403 caso contrário). Nunca expor `access_token` de emissão em redirecionamentos derivados só do `{id}`.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 2. Webhooks Safe2Pay e GFSIS sem verificação de autenticidade

- **Severidade:** Critical
- **Prioridade:** P0 — risco direto de fraude financeira antes de qualquer deploy em produção.
- **Localização:** `app/Http/Controllers/Webhooks/Safe2PayWebhookController.php` (arquivo inteiro); `app/Http/Controllers/Webhooks/GfsisWebhookController.php` (arquivo inteiro); `bootstrap/app.php:22` (isenção de CSRF); `app/Actions/Payments/ApplyPaymentStatusTransition.php:88-134`
- **Vetor de ataque:** `POST /webhooks/safe2pay` e `POST /webhooks/gfsis` estão corretamente isentas de CSRF (esperado para webhook externo), mas nenhum controle substituto existe: sem assinatura HMAC, sem secret compartilhado em header, sem allowlist de IP. Os controllers apenas decodificam o JSON e processam. Um atacante que descubra/deduza um `gateway_transaction_id` (ex.: de um pedido próprio ainda não pago) pode enviar `{"IdTransaction": "<id>", "TransactionStatus": {"Code": "3"}}` (autorizado).
- **Impacto:** `ApplyPaymentStatusTransition::applyAuthorizedSideEffects()` marca o pedido como `paid`, dispara `RegisterOrderItemWithGfsisJob` (registro real no GFSIS) e envia e-mail ao cliente com o link de emissão do certificado digital — produto real liberado sem pagamento verificado. Sem rate limit, também é possível tentar múltiplos IDs em sequência.
- **Evidência:** ausência total de verificação de origem nos dois controllers; `docs/safe2pay.md` documenta o fluxo sem qualquer etapa de validação de autenticidade.
- **Correção recomendada:** implementar validação de assinatura/token conforme oferecido pela Safe2Pay/GFSIS (comparando com `hash_equals()`), ou no mínimo allowlist de IPs dos servidores do gateway. Aplicar rate limiting na rota como defesa em profundidade.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 3. Broken Access Control no painel administrativo: autorregistro público + papel de segurança nunca aplicado

- **Severidade:** Critical
- **Prioridade:** P0 — mesma urgência dos itens 1 e 2.
- **Localização:** `config/fortify.php` (`Features::registration()` habilitado no guard `web`); `app/Actions/Fortify/CreateNewUser.php` (atribui automaticamente `Role::firstOrCreate(['slug' => 'support'])`); `routes/web.php:71-88` (grupo `['auth','verified']`, sem `role:...`); `app/Http/Middleware/RoleMiddleware.php` (implementado corretamente, mas nunca referenciado em nenhuma rota); `database/seeders/RoleSeeder.php` (evidencia a intenção de segregação: admin/operations/finance/support/customer, nunca aplicada em runtime).
- **Vetor de ataque:** Qualquer visitante acessa `GET /register`, cria conta própria (recebendo automaticamente o papel `support`), confirma o e-mail e passa a satisfazer `['auth','verified']` — o único controle de acesso de todas as rotas `painel/*` (vendas, clientes com PII, formas de pagamento, cupons, relatórios financeiros, configurações). Não há Policy, Gate ou `authorize()` em nenhum componente do painel que complemente essa checagem a nível de objeto.
- **Impacto:** Acesso administrativo total ao back-office da loja obtido sem qualquer aprovação, convite ou credencial prévia.
- **Correção recomendada:** (a) aplicar `role:admin,operations,finance,...` por sensibilidade nas rotas de `painel/*` usando o `RoleMiddleware` já existente; (b) tornar o registro de contas de staff um fluxo de convite/aprovação por admin, não uma rota pública do Fortify; (c) complementar com Policies para autorização a nível de objeto.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 4. Ausência de rate limiting no login/registro/recuperação de senha de clientes (guard `customer`)

- **Severidade:** High
- **Prioridade:** P1 — corrigir logo após os itens Critical.
- **Localização:** `resources/views/pages/auth/customer/login.blade.php`, `register.blade.php`, `forgot-password.blade.php`; `app/Providers/FortifyServiceProvider.php:61-71` (limiters `login`/`two-factor` cobrem só o guard `web`, nativo do Fortify)
- **Vetor de ataque:** As telas de cliente são componentes Livewire independentes que chamam `Auth::guard('customer')->attempt()` diretamente, fora do fluxo `throttle:login` do Fortify. Não há `RateLimiter::for(...)` dedicado nem lockout/CAPTCHA.
- **Impacto:** Brute force / credential stuffing ilimitado contra contas de clientes.
- **Correção recomendada:** criar `RateLimiter::for('customer-login', ...)` (ex.: 5/min por e-mail+IP) e aplicar manualmente dentro do método `login()`/`register()`/`sendResetLink()` do componente, já que não são rotas HTTP throttláveis por middleware simples.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 5. Ausência de Policies / autorização a nível de objeto em todo o projeto

- **Severidade:** High (estrutural)
- **Prioridade:** P1 — antes de conceder contas com papéis não administrativos.
- **Localização:** projeto inteiro — não existe `app/Policies`; nenhuma ocorrência de `Gate::`/`$this->authorize()` fora dos métodos `authorize()` de FormRequest (que não checam posse de objeto).
- **Vetor de ataque:** As telas `painel/{id}` (clientes, vendas, produtos, formas de pagamento, cupons) confiam apenas em `auth+verified` no nível de rota. Isso só é seguro na prática se todo usuário do guard `web` for necessariamente admin com acesso irrestrito — premissa contradita pelo achado #3.
- **Impacto:** Qualquer expansão futura de contas com papéis restritos herdaria acesso irrestrito a todos os objetos, silenciosamente.
- **Correção recomendada:** introduzir Policies para os models administrativos sensíveis e aplicar `$this->authorize(...)` nos componentes.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 6. Ausência de rate limiting em `checkout/tokenizar-cartao` (risco de card testing)

- **Severidade:** Medium
- **Prioridade:** P2
- **Localização:** `app/Http/Controllers/Checkout/TokenizeCardController.php`; `app/Http/Requests/TokenizeCardRequest.php` (`authorize()` retorna `true` — rota pública por design)
- **Vetor de ataque:** Endpoint público aceita dados de cartão de qualquer visitante e repassa à Safe2Pay, retornando sucesso/erro — sem rate limit, é vetor clássico de validação em massa de cartões roubados às custas da conta Safe2Pay do lojista.
- **Impacto:** Fraude/card testing, possível bloqueio da conta no gateway.
- **Correção recomendada:** `RateLimiter::for('card-tokenization', ...)` por IP (ex.: 5/min) aplicado na rota.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 7. Disparo de jobs/e-mails em massa sem limite no painel (`reenviarGfsis`, `dispararRecuperacao`)

- **Severidade:** Medium
- **Prioridade:** P2
- **Localização:** `resources/views/pages/painel/⚡vendas.blade.php` (métodos `reenviarGfsis()` e `dispararRecuperacao()`)
- **Vetor de ataque:** `reenviarGfsis()` roda `$this->filteredOrdersQuery()->get()` (todos os pedidos, se nenhum filtro estiver ativo) e despacha um job por resultado, sem limite. `dispararRecuperacao()` faz o mesmo de forma síncrona dentro da própria requisição HTTP.
- **Impacto:** Qualquer usuário do painel (ver achado #3 — potencialmente qualquer papel) pode disparar milhares de jobs de uma vez ou travar a própria requisição até timeout.
- **Correção recomendada:** usar `chunkById()`/`lazy()` em vez de `get()`; mover `dispararRecuperacao()` para processamento assíncrono em lote; exigir confirmação quando nenhum filtro estiver ativo.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 8. Relatórios (`⚡relatorios.blade.php`) sem paginação/streaming em consultas com "todo o período"

- **Severidade:** Medium
- **Prioridade:** P2
- **Localização:** `resources/views/pages/painel/⚡relatorios.blade.php` — `filteredPaidOrders()`, `estornos()`, `cuponsUsados()`, `baseDeRenovacao()`, `exportarCsv()`
- **Vetor de ataque:** O filtro de período aceita "Todo o período" (sem limite temporal); todas as consultas usam `->get()` sem chunk, inclusive a exportação CSV, carregando a tabela inteira em memória numa única requisição síncrona.
- **Impacto:** Com base de dados grande, risco de esgotamento de memória/timeout ao gerar relatório ou exportar CSV.
- **Correção recomendada:** usar `cursor()`/`lazy()` ou streaming para a geração de CSV; considerar mover exportações grandes para job assíncrono.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 9. `AddToCart` não valida existência/status ativo da variante de produto

- **Severidade:** Low/Medium
- **Prioridade:** P2
- **Localização:** `app/Actions/Cart/AddToCart.php:33-36`; `routes/web.php:40-55`
- **Vetor de ataque:** A rota só faz `(int) $productVariantId`; a Action cria o item do carrinho sem checar se a variante existe ou está `is_active`.
- **Impacto:** ID inexistente gera erro 500 não tratado (violação de FK); variante desativada pelo admin ainda pode ser adicionada ao carrinho e seguir para o checkout.
- **Correção recomendada:** `ProductVariant::where('id', $id)->where('is_active', true)->firstOrFail()` antes de criar o item, com 404 amigável.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 10. Mutação de estado via `GET` em `carrinho/adicionar/{productVariantId}`

- **Severidade:** Low
- **Prioridade:** P3
- **Localização:** `routes/web.php:40-55`
- **Vetor de ataque:** Rota `GET` que altera o carrinho — GET não é protegido por token CSRF, então um `<img src="...">` em outro site poderia adicionar item ao carrinho da vítima.
- **Impacto:** Baixo — poluição de carrinho, sem acesso a dados/pagamento.
- **Correção recomendada:** converter para `POST`.
- **Correção aplicada em:** _(preencher após corrigir)_

---

### 11. Campo `document` no checkout sem validação de formato (CPF/CNPJ)

- **Severidade:** Low
- **Prioridade:** P3
- **Localização:** `resources/views/pages/⚡checkout.blade.php:465` (`customerRules()`: `'document' => ['required', 'string', 'max:14']`)
- **Vetor de ataque/Impacto:** Nenhuma regra de dígitos ou checksum de CPF/CNPJ — qualquer string de até 14 caracteres é aceita e usada como chave de `updateOrCreate` para `Customer`. Não é vetor de injeção (Eloquent usa binding), mas permite armazenar documentos malformados/inválidos.
- **Correção recomendada:** aplicar regra de validação (regex de dígitos + tamanho conforme `personType`, idealmente checksum de CPF/CNPJ) análoga à já usada em `postalCode`.
- **Correção aplicada em:** _(preencher após corrigir)_

---

## Pontos verificados SEM vulnerabilidade

### Rate Limiting
- Login/2FA do guard `web` (admin): `RateLimiter::for('login')` e `for('two-factor')` corretamente configurados no Fortify (5/min por e-mail+IP).
- `EnsureIssuanceAccessTokenIsValid`: token gerado por `Str::random(40)` (CSPRNG) com expiração de 30 dias e vínculo ao `order_id` — espaço de busca inviável mesmo sem throttle dedicado.

### Validação de entrada
- `TokenizeCardRequest` e `StoreIssuanceDataRequest`: regras adequadas de formato/tamanho para todos os campos.
- Todos os componentes Livewire públicos (login/registro/senha de cliente, carrinho, checkout) usam `$this->validate([...])` inline consistentemente.
- `finalizarCompra()` recalcula o total no backend e rejeita divergência do total enviado pelo front antes de chamar a Safe2Pay.
- `RemoveFromCart`/`UpdateCartItemQuantity` escopam sempre por `$cart->items()->where('id', ...)` — sem IDOR entre carrinhos.

### SQL Injection
- Único uso de `whereRaw` no projeto (`⚡vendas.blade.php`) é um subquery correlacionado 100% literal, sem input do usuário.
- Nenhum `orderBy` dinâmico por coluna vinda do usuário em nenhuma tela administrativa (vendas, clientes, relatórios, produtos, formas de pagamento) — todas fixas no código.
- Termos de busca sempre usados como valor de bind (`like`), nunca concatenados.

### Mass Assignment
- Todos os 34 Models usam `#[Fillable([...])]` restrito; nenhum usa `$guarded = []`.
- Nenhum uso de `$request->all()` em todo o código.
- Campos sensíveis (`role_id`, `total`, `access_token`) sempre atribuídos server-side, nunca vindos direto de input validado.

### XSS
- Única ocorrência de `{!! !!}` no projeto (SVG de QR Code do 2FA) é gerada server-side a partir do secret TOTP do próprio usuário autenticado — não é dado de usuário.
- Nenhum `@php` monta HTML cru sem escape; nenhum `onclick`/atributo perigoso com interpolação; e-mails (`app/Mail`) usam `{{ }}` corretamente.

### CSRF
- Isenção de CSRF limitada exatamente às 2 rotas de webhook (a falta de controle substituto ali é o achado #2, não uma falha de CSRF em si).

### Autenticação/Sessão
- Guards `web`/`customer` isolados corretamente (providers, tabelas de reset e brokers distintos).
- `Logout` invalida sessão e regenera token CSRF corretamente.
- Senhas de cliente e segredos de 2FA (`password`, `two_factor_secret`, `remember_token`) marcados `#[Hidden]` nos Models — não vazam na serialização.

### Upload de arquivos
- Não existe nenhuma funcionalidade de upload de arquivo implementada na aplicação hoje (produtos usam URL de imagem, não upload) — sem superfície de ataque nessa frente.

### SSRF/serviços externos
- URLs base da Safe2Pay/GFSIS sempre vêm de `config('services.*')`, nunca de input do usuário.
- Dados de cartão validados antes do envio; cobrança usa apenas token do cofre, nunca dado bruto.
- Webhooks e seus Jobs de processamento não fazem nenhuma requisição HTTP de saída a partir do payload recebido (sem SSRF de segunda ordem).

### Exposição de informações
- `APP_DEBUG` default `false` em `config/app.php`.
- Exceções customizadas (`Payments`/`Gfsis`/`Refunds`) sempre retornam mensagem genérica ao cliente, nunca o payload bruto da API externa.
- Nenhum log grava número de cartão, CVV, senha ou credenciais de API.

---

## Correções prioritárias (ordem recomendada)

1. **P0** — Amarrar posse em `pedido/{id}/pagamento/` (item 1) e parar de vazar `access_token` de emissão em redirects.
2. **P0** — Autenticar os webhooks Safe2Pay/GFSIS (item 2).
3. **P0** — Restringir acesso ao painel por papel e revisar o fluxo de autorregistro (item 3).
4. **P1** — Rate limit no login/registro/recuperação de clientes (item 4) e em tokenizar-cartão (item 6, adiantado por relação direta).
5. **P1** — Introduzir Policies para autorização a nível de objeto (item 5).
6. **P2** — Corrigir jobs/relatórios sem limite (itens 7 e 8) e validação de variante ativa no carrinho (item 9).
7. **P3** — GET idempotente no carrinho (item 10) e validação de formato de documento (item 11).

## Pontos já bem implementados

- Proteção contra Mass Assignment (100% dos Models com fillable restrito, zero uso de `$request->all()`).
- Ausência de SQL Injection (bindings parametrizados consistentes, nenhum `orderBy` dinâmico).
- Escaping Blade consistente (nenhum XSS explorável encontrado).
- Isolamento correto entre guards `web` e `customer`.
- Tratamento seguro de dados de cartão (nunca persistidos/logados em texto claro, token do cofre usado nas cobranças).
- Mensagens de erro genéricas para o cliente final, sem vazamento de stack trace/payload de API externa.
