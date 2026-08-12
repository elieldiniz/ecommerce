# Phases: telas-mockup-checkout-conta-painel

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh
.spec/features/telas-mockup-checkout-conta-painel/PHASES.md`.

## Phase 1: Fundação — componentes compartilhados e rotas

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T01 — Componente `x-checkout-steps`
      Arquivos: `resources/views/components/checkout-steps.blade.php`
      Mudança: Indicador de 3 passos ("1. Carrinho", "2. Dados e pagamento", "3. Dados de emissão") como
      componente único. Prop `:passo-ativo` (1|2|3) destaca o passo atual (fundo `--color-highlight`, texto
      `--color-ink` peso 600); passos anteriores marcados como concluídos ("ok"); passos futuros em
      `--color-muted-light`. Nenhum texto hardcoded fora do componente.
      Cobre: UI-01
      Acceptance criteria: renderizado com `:passo-ativo="2"` e `:passo-ativo="3"` mostra os 3 rótulos e destaca
      visualmente apenas o passo correspondente em cada caso; nenhum outro arquivo desta feature reescreve o
      HTML do indicador manualmente.
      Testes: `tests/Feature/Components/CheckoutStepsTest.php` — asserta os 3 rótulos e a marcação de
      ativo/concluído por valor de `:passo-ativo`.

- [ ] T02 — Componente `x-checkout-header`
      Arquivos: `resources/views/components/checkout-header.blade.php`
      Mudança: Cabeçalho reduzido de fluxo (logo "digital**lock**" + texto de contexto via prop `:contexto`
      — "Compra segura" nas 3 telas de compra, "Minha conta" na tela de pedidos — + link fixo "Ajuda" em
      `--color-brand` peso 600). Sem nav horizontal de 5 itens, sem botão "Comprar", sem rodapé, sem
      `x-breadcrumb` (UI-06). Extraído como componente compartilhado desde o início — reaproveitado nas 4 telas
      de loja (T08, T10, T11, T15) em vez de repetido inline, seguindo a mesma convenção anti-duplicação já
      usada para `x-checkout-steps` na Phase 1.
      Cobre: UI-06
      Acceptance criteria: renderizado com `:contexto="'Compra segura'"` e `:contexto="'Minha conta'"` mostra o
      logo, o texto de contexto correspondente e o link "Ajuda"; o HTML não contém nav horizontal de 5 itens,
      botão "Comprar", rodapé nem marcação de `x-breadcrumb`.
      Testes: `tests/Feature/Components/CheckoutHeaderTest.php` — testa os 2 valores de `:contexto` e a ausência
      das marcações de `x-layout`/`x-breadcrumb`.

- [ ] T03 — Componente `x-admin-layout`
      Arquivos: `resources/views/components/admin-layout.blade.php`
      Mudança: Layout comum do painel: sidebar fundo `--color-ink`, wordmark "digital**lock**", os 7 itens de
      navegação em escopo (Visão geral, Vendas, Fila de recuperação, Produtos, Formas de pagamento, Clientes,
      Relatórios — sem "Cupons" nem "Configurações"), rodapé com nome/papel do usuário autenticado, barra
      superior com `<x-slot:titulo>` e seletor de período estático. Prop `:item-ativo` destaca o item de nav da
      tela atual. Slot de conteúdo principal.
      Cobre: UI-02, UI-04
      Acceptance criteria: renderizado com cada um dos 7 valores de `:item-ativo` destaca exatamente 1 item por
      vez; o HTML não contém nenhuma marcação característica de `x-layouts::app.sidebar` (Flux) nem do
      header/rodapé de `x-layout`.
      Testes: `tests/Feature/Components/AdminLayoutTest.php` — testa os 7 valores de `:item-ativo` e a ausência
      das marcações dos outros layouts.

- [ ] T04 — Componente `x-badge-status`
      Arquivos: `resources/views/components/badge-status.blade.php`
      Mudança: Badge único com prop `:variante` (`emitido`/`agendado`/`aguardando`/`erro`/`neutro`), cada
      variante com combinação fixa de cor de fundo/texto dentro da paleta de tokens (`emitido` fundo
      `#e4f0e8`/texto `#1e5c34`; `agendado` fundo `--color-highlight`/texto `#B8003A`; `aguardando` fundo
      `#fbf0d8`/texto `#7a5606`; `erro`/`neutro` com famílias de cor equivalentes). Rótulo via slot.
      Cobre: UI-03
      Acceptance criteria: as 5 variantes renderizam combinações de cor distintas e consistentes; nenhuma cor
      usada fora da paleta de tokens (`--color-brand`, `--color-ink`, `--color-muted`, `--color-surface-alt`,
      `--color-border`, `--color-highlight`, `--color-cta-secondary` e as 3 combinações de badge acima).
      Testes: `tests/Feature/Components/BadgeStatusTest.php` — testa as 5 variantes e a ausência de cor hex fora
      da lista permitida.

- [ ] T05 — Componentes de apoio ao painel: `x-kpi-card`, `x-funil-operacional`, `x-timeline`
      Arquivos: `resources/views/components/kpi-card.blade.php`,
      `resources/views/components/funil-operacional.blade.php`, `resources/views/components/timeline.blade.php`
      Mudança: `x-kpi-card` (props rótulo, valor, texto de apoio opcional); `x-funil-operacional` (prop com
      coleção de estágios: nome, quantidade, percentual opcional); `x-timeline` (prop com coleção de eventos:
      data/hora, descrição, origem). Evitam duplicar marcação entre as telas do painel que compartilham esses
      padrões visuais.
      Cobre: nenhum RF isoladamente — consumidos em T17 (RF-16, RF-17), T23 (RF-28), T24 (RF-29), T33 (RF-45)
      Acceptance criteria: cada componente renderiza corretamente com dados de exemplo (kpi-card mostra
      rótulo+valor; funil-operacional mostra os estágios na ordem recebida; timeline mostra os eventos na ordem
      recebida).
      Testes: `tests/Feature/Components/PainelSupportComponentsTest.php` — 1 método por componente.

- [ ] T06 — Controller de emissão (`Pedido\ShowEmissaoController`)
      Arquivos: `app/Http/Controllers/Pedido/ShowEmissaoController.php`
      Mudança: Single Action Controller (`__invoke(Request $request, int|string $id)`). Lê `?tipo=pj` da query
      string (default PF quando ausente) e retorna `view('pages.pedido.emissao', [...])` com o `$id` e o tipo de
      titular mock. Não faz consulta a banco, não instancia Eloquent Model — apenas resolve qual bloco central
      mostrar (RNF-04).
      Cobre: CT-03 (habilita RF-06 a RF-13, implementados em T11-T14)
      Acceptance criteria: `GET /pedido/1042/emissao/` sem query string retorna a variação PF; `GET
      /pedido/1042/emissao/?tipo=pj` retorna a variação PJ; o arquivo do controller não importa nenhuma classe
      de `App\Models\*`.
      Testes: cobertura funcional incluída em `tests/Feature/Pages/Pedido/EmissaoTest.php` (T11-T14); checklist
      RNF-04 verificado em T36.

- [ ] T07 — Registrar as 13 rotas em `routes/web.php`
      Arquivos: `routes/web.php`
      Mudança: Adicionar as 4 rotas públicas de loja fora de qualquer middleware (`checkout/`,
      `pedido/{id}/pagamento/` via `Route::view()`, `pedido/{id}/emissao/` via
      `Route::get(..., ShowEmissaoController::class)`, `minha-conta/pedidos/` via `Route::view()`); adicionar as
      8 rotas do painel **dentro** do grupo `Route::middleware(['auth', 'verified'])` já existente
      (`routes/web.php:16-18`), todas via `Route::view()` com os nomes de view do CT-01..CT-12. Nomear todas as
      rotas seguindo o padrão `dot.case` já usado. Prefixo de caminho `/painel/*` no mesmo domínio (não
      subdomínio, conforme decisão da SPEC).
      Cobre: CT-01, CT-02, CT-03, CT-04, CT-05, CT-06, CT-07, CT-08, CT-09, CT-10, CT-11, CT-12, RF-47
      Acceptance criteria: `vendor/bin/sail artisan route:list` mostra as 13 rotas novas com os nomes e paths
      esperados; as 8 rotas do painel aparecem com o middleware `auth`; as 4 rotas de loja não aparecem com
      nenhum middleware de autenticação; a suíte de testes das 9 páginas existentes continua passando.
      Testes: cobertura funcional feita pelos testes de página das tasks seguintes e pelo guard transversal
      (T35).

## Phase 2: Checkout & Emissão (loja, rotas públicas)

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T08 — Checkout: rota, esqueleto, `x-checkout-header`, `x-checkout-steps`, blocos "Seus dados" e "Como você prefere pagar"
      Arquivos: `resources/views/pages/checkout.blade.php`
      Mudança: `<x-checkout-header contexto="Compra segura" />`, `<x-checkout-steps :passo-ativo="2" />`, bloco
      "Seus dados" com os 6 campos + checkbox de opt-in, bloco "Como você prefere pagar" com as 3 opções e Pix
      destacado visualmente.
      Cobre: RF-01, RF-02, UI-01, UI-06
      Acceptance criteria: `GET /checkout/` retorna 200 com `pages.checkout`; rótulo "Seus dados" e os 6
      campos/checkbox presentes; as 3 opções de pagamento aparecem com os textos exatos do mockup e Pix com
      destaque visual de selecionado.
      Testes: `tests/Feature/Pages/CheckoutTest.php::test_route_renders_the_checkout_view`,
      `::test_block_seus_dados_renders_six_fields_and_opt_in`,
      `::test_block_forma_pagamento_renders_three_options_pix_selected`.

- [ ] T09 — Checkout: bloco "Seu pedido" (resumo lateral)
      Arquivos: `resources/views/pages/checkout.blade.php`
      Mudança: Resumo lateral fixo com item comprado, campo de cupom + botão "Aplicar", subtotal, desconto de
      cupom, desconto do Pix, total e botão "Finalizar compra" sem `action`/`method` funcional.
      Cobre: RF-03
      Acceptance criteria: subtotal, desconto cupom, desconto Pix e total aparecem como valores fixos; o botão
      "Finalizar compra" não tem `method="POST"` real associado.
      Testes: `tests/Feature/Pages/CheckoutTest.php::test_block_resumo_renders_summary_and_finalizar_button_without_real_submission`.

- [ ] T10 — Pagamento Pix: rota, esqueleto, `x-checkout-header`, `x-checkout-steps`, blocos "Escaneie para pagar" e "Variação boleto"
      Arquivos: `resources/views/pages/pedido/pagamento.blade.php`
      Mudança: `<x-checkout-header contexto="Compra segura" />` + `<x-checkout-steps :passo-ativo="2" />`. Bloco
      "Escaneie para pagar": placeholder de QR Code, "Pedido #{{ $id }} · R$ [VALOR]", copia-e-cola truncado +
      botão "Copiar código", texto estático "Expira em 29:47", mensagem de espera estática. Bloco "Variação
      boleto" com o texto literal do mockup. Nenhum `setInterval`/`setTimeout`/`fetch` real.
      Cobre: RF-04, RF-05, UI-01, UI-06, RNF-03
      Acceptance criteria: `GET /pedido/1042/pagamento/` retorna 200 com `pages.pedido.pagamento`; os 5
      elementos do bloco Pix e o texto da variação boleto estão presentes; o HTML não contém `setInterval(`,
      `setTimeout(`, `fetch(` nem `XMLHttpRequest`.
      Testes: `tests/Feature/Pages/Pedido/PagamentoTest.php` — asserta os 5 elementos, o texto da variação
      boleto e a ausência de polling real.

- [ ] T11 — Emissão: rota (controller), esqueleto, `x-checkout-header`, `x-checkout-steps`, blocos "Confirmação" e "O que acontece agora"
      Arquivos: `resources/views/pages/pedido/emissao.blade.php`
      Mudança: `<x-checkout-header contexto="Compra segura" />` + `<x-checkout-steps :passo-ativo="3" />`. Bloco
      "Confirmação": ícone de check, "Pagamento confirmado", "Pedido #{{ $id }} · R$ [VALOR] no Pix" — idêntico
      entre PF/PJ. Bloco "O que acontece agora": `<x-passo-a-passo>` reaproveitado com título e os 4 passos via
      prop/dado, sem HTML bespoke. Inclui condicionalmente o parcial PF (T12) ou PJ (T13) conforme
      `$tipoTitular`.
      Cobre: RF-06, RF-09, RF-13 (blocos 1 e 3), UI-01, UI-06
      Acceptance criteria: `GET /pedido/1042/emissao/` retorna 200 com `pages.pedido.emissao`; bloco
      "Confirmação" mostra ícone+"Pagamento confirmado"+"Pedido #1042 · R$ [VALOR] no Pix"; os 4 cartões de "O
      que acontece agora" são renderizados pela mesma instância do componente `x-passo-a-passo` usado no
      checkout.
      Testes: `tests/Feature/Pages/Pedido/EmissaoTest.php::test_block_confirmacao_renders_success_icon_order_and_value`,
      `::test_block_o_que_acontece_agora_reuses_passo_a_passo_component`.

- [ ] T12 — Emissão PF: seções "Titular" e "Endereço"
      Arquivos: `resources/views/pages/pedido/partials/emissao-pf.blade.php`
      Mudança: Seção "Titular" com os 5 campos (nome completo, CPF, data de nascimento, e-mail, telefone com
      DDD). Seção "Endereço" com os 7 campos independentes (CEP, logradouro, número, complemento, bairro,
      município, UF), cada um como elemento de formulário distinto. Sem `<form method="POST">` funcional.
      Cobre: RF-07, RF-08
      Acceptance criteria: `GET /pedido/1042/emissao/` (sem query string) mostra os 5+7 campos e não mostra a
      seção "Empresa".
      Testes: `tests/Feature/Pages/Pedido/EmissaoTest.php::test_pf_variation_renders_titular_and_endereco_sections`.

- [ ] T13 — Emissão PJ: seções "Empresa", "Responsável" e "Endereço da empresa"
      Arquivos: `resources/views/pages/pedido/partials/emissao-pj.blade.php`
      Mudança: Seção "Empresa" com os 4 campos (razão social, CNPJ, e-mail da empresa, telefone com DDD) no
      lugar de "Titular". Seção "Responsável pelo uso do certificado" com os 5 campos + texto explicativo. Seção
      "Endereço da empresa" com os mesmos 7 campos independentes de RF-08, rotulados como endereço da empresa.
      Cobre: RF-10, RF-11, RF-12
      Acceptance criteria: `GET /pedido/1042/emissao/?tipo=pj` mostra os 3 blocos (Empresa, Responsável,
      Endereço da empresa) e não mostra a seção "Titular".
      Testes: `tests/Feature/Pages/Pedido/EmissaoTest.php::test_pj_variation_renders_empresa_responsavel_and_endereco_da_empresa_sections`.

- [ ] T14 — Emissão: teste de paridade PF×PJ (RF-13)
      Arquivos: `tests/Feature/Pages/Pedido/EmissaoTest.php`
      Mudança: Nenhuma mudança de view. Requisita a mesma rota sem query string e com `?tipo=pj`, extrai o HTML
      dos blocos "Confirmação" e "O que acontece agora" e assere que texto/estrutura é idêntico entre as duas
      respostas.
      Cobre: RF-13
      Acceptance criteria: comparação de snapshot dos blocos 1 e 3 entre a variação PF e a variação PJ não
      mostra nenhuma diferença de texto ou estrutura.
      Testes: `tests/Feature/Pages/Pedido/EmissaoTest.php::test_blocks_confirmacao_and_o_que_acontece_agora_are_identical_between_pf_and_pj`.

## Phase 3: Minha Conta (loja, rota pública)

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T15 — Minha conta: rota, esqueleto, `x-checkout-header`, 3 cartões de pedido por estado
      Arquivos: `resources/views/pages/minha-conta/pedidos.blade.php`
      Mudança: `<x-checkout-header contexto="Minha conta" />` — sem `x-checkout-steps` (esta tela não faz parte
      do funil de 3 passos). 3 cartões de pedido de exemplo usando `<x-badge-status>`: "Emitido" (titular,
      validade até, pago em, botões "Ver nota fiscal"/"Baixar certificado"/"Renovar"), "Agendado" (titular,
      data/hora da videoconferência, pago em, botões "Ver o que levar"/"Reagendar"), "Faltam seus dados" (pago
      em, situação, botão "Preencher agora"). Nenhum botão dispara ação real.
      Cobre: RF-14, UI-03, UI-06
      Acceptance criteria: `GET /minha-conta/pedidos/` retorna 200 com `pages.minha-conta.pedidos`; os 3
      cartões, cada um com o conjunto de campos e botões do seu estado, estão presentes; nenhum botão dispara
      ação real.
      Testes: `tests/Feature/Pages/MinhaConta/PedidosTest.php::test_route_renders_the_pedidos_view`,
      `::test_renders_three_order_cards_one_per_state_with_state_specific_fields_and_buttons`.

- [ ] T16 — Minha conta: tabela "Estados possíveis"
      Arquivos: `resources/views/pages/minha-conta/pedidos.blade.php`
      Mudança: Tabela com as 5 linhas exatas do mockup (Faltam seus dados, Em processamento, Agendado, Emitido,
      Vencendo) e coluna "Origem" com o nome do campo do banco (uso apenas como texto mock).
      Cobre: RF-15
      Acceptance criteria: a tabela tem exatamente 5 linhas com os textos de situação e origem do mockup.
      Testes: `tests/Feature/Pages/MinhaConta/PedidosTest.php::test_renders_estados_possiveis_table_with_five_rows`.

## Phase 4: Painel · Visão Geral, Vendas, Detalhe da Venda, Recuperação

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T17 — Painel visão geral: rota, `x-admin-layout`, blocos "Indicadores" e "Funil operacional"
      Arquivos: `resources/views/pages/painel/visao-geral.blade.php`
      Mudança: `<x-admin-layout item-ativo="visao-geral" titulo="Visão geral">`. Bloco "Indicadores": 5×
      `<x-kpi-card>` (Faturamento, Ticket médio, Taxa de conversão, Aguardando dados, Falha de integração) com
      valor mock e texto de apoio quando aplicável. Bloco "Funil operacional": `<x-funil-operacional>` com os 5
      estágios (Pedidos criados, Pagos, Dados completos, Enviados ao GFSIS, Emitidos), percentual de conversão
      entre estágios, nota "A maior queda revela o gargalo."
      Cobre: RF-16, RF-17, UI-02
      Acceptance criteria: `GET /painel/` sem sessão redireciona (302); autenticado retorna 200 com
      `pages.painel.visao-geral`; os 5 cartões de KPI aparecem com rótulo e valor; os 5 estágios do funil
      aparecem na ordem, cada um com quantidade e percentual (exceto o primeiro).
      Testes: `tests/Feature/Pages/Painel/VisaoGeralTest.php::test_route_requires_authentication`,
      `::test_block_indicadores_renders_five_kpi_cards`,
      `::test_block_funil_renders_five_stages_with_conversion`.

- [ ] T18 — Painel visão geral: blocos "Exige ação" e "Vendas por dia"
      Arquivos: `resources/views/pages/painel/visao-geral.blade.php`
      Mudança: Tabela "Exige ação" com as 5 filas exatas (Pagos sem dados de emissão, Falha de envio ao GFSIS,
      Conversões não enviadas, Reembolsos pendentes, Certificados vencendo em 30 dias), quantidade, "mais
      antigo" e botão "Abrir" sem ação real. Placeholder de gráfico de barras "Vendas por dia" com texto
      indicativo, sem biblioteca de gráfico real.
      Cobre: RF-18, RF-19
      Acceptance criteria: a tabela "Exige ação" tem exatamente 5 linhas com os textos de fila do mockup; o
      placeholder de gráfico está presente com texto indicativo.
      Testes: `tests/Feature/Pages/Painel/VisaoGeralTest.php::test_block_exige_acao_renders_five_queues`,
      `::test_block_vendas_por_dia_renders_chart_placeholder`.

- [ ] T19 — Painel vendas: rota, `x-admin-layout`, blocos "Filtros" e tabela de pedidos
      Arquivos: `resources/views/pages/painel/vendas/index.blade.php`
      Mudança: `<x-admin-layout item-ativo="vendas" titulo="Vendas">`. Bloco "Filtros" com os 7 controles
      (Período, Status do pagamento, Status da emissão, Forma de pagamento, Produto, Origem, busca), todos
      visuais. Tabela de pedidos com as 7 colunas (Pedido, Cliente, Produto, Valor, Pagamento, Emissão, Data —
      Pagamento e Emissão sempre separadas), ≥ 6 linhas usando `<x-badge-status>`, paginação mock "Mostrando 1 a
      25 de 312".
      Cobre: RF-20, RF-21, UI-02, UI-03
      Acceptance criteria: `GET /painel/vendas/` sem sessão redireciona (302); autenticado retorna 200; os 7
      controles de filtro aparecem sem alterar a listagem via requisição real; pelo menos 6 linhas com as 7
      colunas, Pagamento e Emissão em colunas distintas; paginação mock visível.
      Testes: `tests/Feature/Pages/Painel/VendasIndexTest.php::test_route_requires_authentication`,
      `::test_block_filtros_renders_seven_controls`,
      `::test_table_renders_seven_columns_pagamento_and_emissao_separated_with_mock_pagination`.

- [ ] T20 — Painel vendas: bloco "Ações em lote"
      Arquivos: `resources/views/pages/painel/vendas/index.blade.php`
      Mudança: 3 botões ("Exportar CSV", "Reenviar ao GFSIS", "Disparar recuperação"), nenhum com `action`
      funcional.
      Cobre: RF-22
      Acceptance criteria: os 3 botões estão presentes, sem `action` funcional associado.
      Testes: `tests/Feature/Pages/Painel/VendasIndexTest.php::test_block_acoes_em_lote_renders_three_buttons_without_real_action`.

- [ ] T21 — Detalhe da venda: rota, `x-admin-layout`, blocos "Cabeçalho" e "Itens"
      Arquivos: `resources/views/pages/painel/vendas/show.blade.php`
      Mudança: `<x-admin-layout item-ativo="vendas" titulo="Pedido #{{ $id }}">`. Bloco "Cabeçalho": número do
      pedido, data/hora de criação, nome do cliente, 2× `<x-badge-status>` lado a lado (pagamento e emissão).
      Tabela "Itens" com as 5 colunas (SKU, Produto, Titular, Preço tabela, Preço praticado), ≥ 1 linha.
      Cobre: RF-23, RF-24, UI-02, UI-03
      Acceptance criteria: `GET /painel/vendas/1042/` sem sessão redireciona (302); autenticado retorna 200; o
      cabeçalho mostra número do pedido, data de criação, cliente e 2 badges de status distintos; ao menos 1
      linha de item com as 5 colunas.
      Testes: `tests/Feature/Pages/Painel/VendasShowTest.php::test_route_requires_authentication`,
      `::test_block_cabecalho_renders_order_number_date_client_and_two_status_badges`,
      `::test_table_itens_renders_five_columns`.

- [ ] T22 — Detalhe da venda: blocos "Financeiro" e "Emissão e GFSIS"
      Arquivos: `resources/views/pages/painel/vendas/show.blade.php`
      Mudança: Cartão "Valores" (subtotal, desconto cupom, desconto Pix, total, taxa do gateway, líquido
      previsto) + cartão "Pagamento" (método, status, ID no gateway, TXID, end-to-end, pago em, previsão de
      repasse). Cartão "Dados do titular" + cartão "Integração" (`gfsis_order_id`, código GFSIS, status GFSIS,
      agendamento, validade até, sincronizado em, tentativas) + botão "Reenviar ao GFSIS" sem ação real.
      Cobre: RF-25, RF-26
      Acceptance criteria: os 2 cartões financeiros e os 2 cartões de emissão/GFSIS com as linhas de
      campo/valor listadas estão presentes; o botão "Reenviar ao GFSIS" não tem ação real associada.
      Testes: `tests/Feature/Pages/Painel/VendasShowTest.php::test_block_financeiro_renders_valores_and_pagamento_cards`,
      `::test_block_emissao_gfsis_renders_titular_and_integracao_cards_and_resend_button`.

- [ ] T23 — Detalhe da venda: blocos "Origem da venda" e "Linha do tempo"
      Arquivos: `resources/views/pages/painel/vendas/show.blade.php`
      Mudança: Tabela "Origem da venda" com as 7 linhas (campanha, origem e meio, gclid, página de entrada,
      dispositivo, sessões até a compra, status de conversão enviada). `<x-timeline>` com ≥ 6 eventos
      cronológicos (pedido criado, pagamento autorizado, dados de emissão preenchidos, enviado ao GFSIS,
      videoconferência realizada, certificado emitido), cada um com data/hora e origem.
      Cobre: RF-27, RF-28
      Acceptance criteria: a tabela "Origem da venda" tem as 7 linhas do mockup; pelo menos 6 eventos aparecem
      em ordem cronológica ascendente, cada um com data/hora e origem.
      Testes: `tests/Feature/Pages/Painel/VendasShowTest.php::test_table_origem_da_venda_renders_seven_rows`,
      `::test_block_linha_do_tempo_renders_six_events_in_chronological_order_with_origin`.

- [ ] T24 — Painel recuperação: rota, `x-admin-layout`, blocos "Indicadores" e "Fila ordenada por tempo"
      Arquivos: `resources/views/pages/painel/recuperacao.blade.php`
      Mudança: `<x-admin-layout item-ativo="recuperacao" titulo="Fila de recuperação">`. Bloco "Indicadores": 4×
      `<x-kpi-card>` (Pagos sem dados, Recuperados em 7 dias, Mais antigo, Falha de envio). Tabela "Fila
      ordenada por tempo" com colunas Pedido, Cliente, Valor, Dias, Contatos, Ação ("Ligar"/"Reenviar link"), ≥
      4 linhas ordenadas por "Dias" decrescente.
      Cobre: RF-29, RF-30, UI-02
      Acceptance criteria: `GET /painel/recuperacao/` sem sessão redireciona (302); autenticado retorna 200; os
      4 cartões de KPI aparecem com rótulo e valor; ao menos 4 linhas, ordenadas do maior para o menor valor de
      "Dias".
      Testes: `tests/Feature/Pages/Painel/RecuperacaoTest.php::test_route_requires_authentication`,
      `::test_block_indicadores_renders_four_kpi_cards`,
      `::test_table_fila_renders_rows_ordered_by_dias_descending`.

- [ ] T25 — Painel recuperação: blocos "Régua automática" e "Falhas de integração"
      Arquivos: `resources/views/pages/painel/recuperacao.blade.php`
      Mudança: Tabela "Régua automática" com as 5 linhas exatas (Imediato/E-mail, 2 horas/WhatsApp, 24
      horas/E-mail, 3 dias/WhatsApp, 5 dias/Painel), momento, canal, mensagem. Tabela "Falhas de integração" com
      colunas Pedido, Erro, Tentativas, Ação ("Corrigir e reenviar"), ≥ 2 linhas.
      Cobre: RF-31, RF-32
      Acceptance criteria: a tabela "Régua automática" tem exatamente 5 linhas com os 3 campos preenchidos; ao
      menos 2 linhas de exemplo na tabela "Falhas de integração" com as 4 colunas.
      Testes: `tests/Feature/Pages/Painel/RecuperacaoTest.php::test_table_regua_automatica_renders_five_rows`,
      `::test_table_falhas_de_integracao_renders_at_least_two_rows`.

## Phase 5: Painel · Produtos, Formas de Pagamento, Clientes, Relatórios

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T26 — Painel produtos: rota, `x-admin-layout`, blocos "Lista" e "Edição · dados do produto"
      Arquivos: `resources/views/pages/painel/produtos.blade.php`
      Mudança: `<x-admin-layout item-ativo="produtos" titulo="Produtos">`. Tabela "Lista" com colunas Produto,
      Tipo, Slug, Variantes, "A partir de", Ativo, ≥ 3 linhas, botão "Novo produto" sem ação real. Bloco
      "Edição · dados do produto" com os 6 campos (nome, slug, tipo de titular, descrição curta, ordem, ativo)
      pré-preenchidos.
      Cobre: RF-33, RF-34, UI-02
      Acceptance criteria: `GET /painel/produtos/` sem sessão redireciona (302); autenticado retorna 200; ao
      menos 3 linhas de produto com as 6 colunas; botão "Novo produto" visível sem ação real; os 6 campos de
      edição pré-preenchidos.
      Testes: `tests/Feature/Pages/Painel/ProdutosTest.php::test_route_requires_authentication`,
      `::test_table_lista_renders_three_products_and_new_product_button`,
      `::test_block_edicao_produto_renders_six_prefilled_fields`.

- [ ] T27 — Painel produtos: blocos "Variantes do produto" e "Edição de variante"
      Arquivos: `resources/views/pages/painel/produtos.blade.php`
      Mudança: Tabela "Variantes do produto" com as 8 colunas (SKU, Tipo, Validade, Preço, Promocional,
      Vigência, Padrão, Ativo), ≥ 3 linhas, botão "Nova variante" sem ação real. Bloco "Edição de variante" com
      os 8 campos (SKU, tipo de certificado, validade em meses, preço, preço promocional, vigência da promoção,
      variante padrão, ativo) pré-preenchidos.
      Cobre: RF-35, RF-36
      Acceptance criteria: ao menos 3 linhas de variante com as 8 colunas; os 8 campos de edição de variante
      pré-preenchidos.
      Testes: `tests/Feature/Pages/Painel/ProdutosTest.php::test_table_variantes_renders_three_variants_with_eight_columns_and_new_variant_button`,
      `::test_block_edicao_variante_renders_eight_prefilled_fields`.

- [ ] T28 — Painel formas de pagamento: rota, `x-admin-layout`, tabelas "Formas de pagamento" e "Cupons"
      Arquivos: `resources/views/pages/painel/formas-pagamento.blade.php`
      Mudança: `<x-admin-layout item-ativo="formas-pagamento" titulo="Formas de pagamento">`. Tabela "Formas de
      pagamento" com exatamente 3 linhas (pix, cartão, boleto), colunas Código, Nome exibido, Desconto, Máx.
      parcelas, Ordem, Ativo. Tabela "Cupons" com colunas Código, Tipo, Valor, Usos, Limite, Vigência,
      Restrição, Ativo, ≥ 3 linhas, botão "Novo cupom" sem ação real.
      Cobre: RF-37, RF-38, UI-02
      Acceptance criteria: `GET /painel/formas-pagamento/` sem sessão redireciona (302); autenticado retorna
      200; exatamente 3 linhas na tabela de formas de pagamento; ao menos 3 linhas de cupom com as 8 colunas.
      Testes: `tests/Feature/Pages/Painel/FormasPagamentoTest.php::test_route_requires_authentication`,
      `::test_table_formas_pagamento_renders_exactly_three_rows`,
      `::test_table_cupons_renders_at_least_three_rows_and_new_coupon_button`.

- [ ] T29 — Painel formas de pagamento: bloco "Edição de cupom"
      Arquivos: `resources/views/pages/painel/formas-pagamento.blade.php`
      Mudança: Bloco com os 9 campos (código, tipo, valor, limite de usos, limite por cliente, restrito à
      variante, válido de, válido até, ativo) pré-preenchidos com valores de exemplo.
      Cobre: RF-39
      Acceptance criteria: os 9 campos de edição de cupom estão presentes, pré-preenchidos.
      Testes: `tests/Feature/Pages/Painel/FormasPagamentoTest.php::test_block_edicao_cupom_renders_nine_prefilled_fields`.

- [ ] T30 — Painel clientes: rota, `x-admin-layout`, blocos "Filtros e lista" e "Ficha do cliente · dados"
      Arquivos: `resources/views/pages/painel/clientes.blade.php`
      Mudança: `<x-admin-layout item-ativo="clientes" titulo="Clientes">`. Bloco "Filtros e lista" com os 4
      filtros (Tipo de pessoa, UF, Período de cadastro, Com certificado vencendo) + busca, tabela com colunas
      Nome ou razão social, Documento, Tipo, E-mail, Pedidos, Última compra, ≥ 3 linhas, paginação mock. Bloco
      "Ficha do cliente · dados" com razão social/nome, tipo de pessoa, documento, e-mail, telefone e os 7
      campos de endereço independentes.
      Cobre: RF-40, RF-41, UI-02
      Acceptance criteria: `GET /painel/clientes/` sem sessão redireciona (302); autenticado retorna 200; os 4
      filtros + busca e ao menos 3 linhas de cliente com as 6 colunas presentes; os 5 campos de identificação e
      os 7 campos de endereço presentes, cada componente de endereço em campo próprio.
      Testes: `tests/Feature/Pages/Painel/ClientesTest.php::test_route_requires_authentication`,
      `::test_block_filtros_e_lista_renders_four_filters_search_and_three_customer_rows`,
      `::test_block_ficha_renders_identification_fields_and_seven_independent_address_fields`.

- [ ] T31 — Painel clientes: blocos "Histórico de pedidos" e "Titulares vinculados"
      Arquivos: `resources/views/pages/painel/clientes.blade.php`
      Mudança: Tabela "Histórico de pedidos" com colunas Pedido, Produto, Valor, Pagamento, Emissão, Validade
      até, ≥ 2 linhas, usando `<x-badge-status>`. Tabela "Titulares vinculados" com colunas Titular, Documento,
      Tipo, Responsável, Certificado até, ≥ 2 linhas.
      Cobre: RF-42, RF-43, UI-03
      Acceptance criteria: ao menos 2 linhas de pedido com as 6 colunas; ao menos 2 linhas de titular com as 5
      colunas.
      Testes: `tests/Feature/Pages/Painel/ClientesTest.php::test_table_historico_de_pedidos_renders_at_least_two_rows`,
      `::test_table_titulares_vinculados_renders_at_least_two_rows`.

- [ ] T32 — Painel relatórios: rota, `x-admin-layout`, bloco "Seleção"
      Arquivos: `resources/views/pages/painel/relatorios.blade.php`
      Mudança: `<x-admin-layout item-ativo="relatorios" titulo="Relatórios">`. 9 cartões de relatório (Vendas
      por período, Vendas por produto, Funil operacional, Pagos sem dados, Base de renovação, Atribuição,
      Conciliação do gateway, Estornos, Cupons), cada um com nome e descrição curta; "Vendas por período" com
      destaque visual de selecionado por padrão.
      Cobre: RF-44, UI-02
      Acceptance criteria: `GET /painel/relatorios/` sem sessão redireciona (302); autenticado retorna 200; os
      9 cartões aparecem com nome e descrição; exatamente 1 cartão tem o destaque de selecionado.
      Testes: `tests/Feature/Pages/Painel/RelatoriosTest.php::test_route_requires_authentication`,
      `::test_block_selecao_renders_nine_report_cards_with_vendas_por_periodo_selected_by_default`.

- [ ] T33 — Painel relatórios: exemplo "Vendas por período"
      Arquivos: `resources/views/pages/painel/relatorios.blade.php`
      Mudança: Filtros (Período, Produto, Forma de pagamento, Origem, busca), 4× `<x-kpi-card>` (Faturamento,
      Pedidos, Ticket médio, Descontos), placeholder de gráfico de linha, tabela diária (Dia, Pedidos,
      Faturamento, Ticket médio, Desconto) com ≥ 3 linhas, botões "Exportar CSV"/"Exportar PDF" sem exportação
      real.
      Cobre: RF-45
      Acceptance criteria: os filtros, os 4 KPIs, o placeholder de gráfico, ao menos 3 linhas da tabela diária e
      os 2 botões de exportação estão presentes, sem exportação real.
      Testes: `tests/Feature/Pages/Painel/RelatoriosTest.php::test_example_vendas_por_periodo_renders_filters_four_kpis_chart_placeholder_daily_table_and_export_buttons`.

- [ ] T34 — Painel relatórios: exemplo "Base de renovação"
      Arquivos: `resources/views/pages/painel/relatorios.blade.php`
      Mudança: Tabela com colunas Titular, Documento, Produto, Vence em, Dias e Contato (botão "WhatsApp"), ≥ 3
      linhas.
      Cobre: RF-46
      Acceptance criteria: ao menos 3 linhas de exemplo com as 6 colunas presentes.
      Testes: `tests/Feature/Pages/Painel/RelatoriosTest.php::test_example_base_de_renovacao_renders_at_least_three_rows`.

## Phase 6: Verificação Transversal

Antes de implementar, leia:
1. `.spec/features/telas-mockup-checkout-conta-painel/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/telas-mockup-checkout-conta-painel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T35 — Teste transversal do guard de autenticação (RF-47)
      Arquivos: `tests/Feature/RouteGuardTest.php`
      Mudança: Nenhuma mudança de aplicação. Requisição sem sessão autenticada às 8 rotas do painel deve
      retornar 302 (redirect para login), nunca 200. Requisição sem sessão às 4 rotas de loja deve retornar 200,
      nunca 302.
      Cobre: RF-47
      Acceptance criteria: uma requisição de teste sem sessão autenticada a qualquer uma das 8 rotas do painel
      retorna 302 (nunca 200); a mesma requisição sem sessão a qualquer uma das 4 rotas de loja retorna 200
      (nunca 302).
      Testes: `tests/Feature/RouteGuardTest.php::test_guest_is_redirected_from_all_eight_painel_routes`,
      `::test_guest_gets_200_from_all_four_public_customer_routes`.

- [ ] T36 — Regressão transversal de NFRs (RNF-01, RNF-02, RNF-04, RNF-05, RNF-06)
      Arquivos: `tests/Feature/StaticSliceRegressionTest.php`
      Mudança: Nenhuma mudança de aplicação. Varre a resposta HTTP das 13 rotas (autenticando como usuário de
      teste para as 8 do painel) e assere ausência de `fetch(`/`XMLHttpRequest`/`wire:`/`$wire`, ausência de
      `<form>`/botão com submissão funcional real, ausência de cor hex fora da paleta de tokens permitida, e
      presença de `rounded-lg`/tipografia correta em botões/CTAs. RNF-04 é verificado por checklist manual
      documentado no corpo do teste (nenhum arquivo novo em `database/migrations/` ou `app/Models/`; o
      controller `ShowEmissaoController` não importa `App\Models\*`).
      Cobre: RNF-01, RNF-02, RNF-04, RNF-05, RNF-06
      Acceptance criteria: nas 13 telas, nenhuma chamada assíncrona real, nenhum formulário/botão com submissão
      funcional, nenhuma cor fora da paleta de tokens do Phase 1, tipografia e `rounded-lg` aplicados em todos
      os botões/CTAs; nenhuma migration/model/controller com lógica de negócio criada por esta feature.
      Testes: `tests/Feature/StaticSliceRegressionTest.php` (métodos por regra, um por rota×regra).
