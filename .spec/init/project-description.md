# Digital Lock E-commerce — Project Description

## Overview

O **Digital Lock E-commerce** é a loja virtual própria da Digital Lock, uma **Autoridade de Registro (AR) de
certificação digital credenciada no ICP-Brasil**, vinculada à AC Digital Múltipla. O projeto substitui a loja
atual da empresa — hoje operada em uma plataforma de terceiros (Nuvemshop) — por uma aplicação Laravel própria.

O produto vendido é o **certificado digital** (e-CPF e e-CNPJ, nos formatos A1 e A3), emitido **100% online por
videoconferência** — esse é o diferencial central do negócio e o argumento que aparece no topo de toda página
comercial, já que o cliente nunca precisa se deslocar fisicamente para validar sua identidade. O público é
composto por pessoas físicas (assinatura de documentos, IR, serviços de governo), empresas e MEIs (emissão de
nota fiscal eletrônica, assinatura em nome do CNPJ), clientes recorrentes renovando certificados vencendo, e
clientes pós-venda buscando suporte. A própria equipe de atendimento da Digital Lock usa páginas do site (Como
Emitir, Suporte) como material de apoio, enviando links com âncora direta para trechos específicos.

O núcleo funcional do sistema cobre: **catálogo de 4 SKUs** de certificado, **checkout com Pix como forma de
pagamento prioritária** e emissão de nota fiscal, um **banco mestre único e categorizado de FAQ** gerenciável
sem deploy, e um **painel administrativo** para editar FAQ, casos de uso do hub de certificados e a tabela de
problemas de uso da página de Suporte — tudo sem depender de programador. O fluxo de emissão em si (pagamento →
e-mail de agendamento → validação ao vivo → liberação do certificado) é orquestrado e comunicado pelo site, mas
a validação por videoconferência acontece em uma plataforma própria da certificadora, fora do escopo de
construção deste projeto.

**Fronteira do MVP**: a primeira fase executável do projeto é o **frontend estático das 9 páginas comerciais e
de apoio** (sitemap raso, no máximo dois níveis), com todo o conteúdo (copy, tabelas comparativas, FAQ)
hardcoded e sem nenhuma conexão com model, banco de dados, autenticação ou pagamento — essas camadas entram em
fases seguintes. O sitemap completo da operação também prevê páginas legais (`/politica-de-privacidade/`,
`/termos-de-uso/`, `/trocas-e-devolucoes/`) e páginas transacionais com `noindex`/`nofollow`
(`/carrinho/`, `/checkout/`, `/pedido/confirmacao/`, `/minha-conta/`), mas nenhuma delas entra nesta fase.
Ficam **fora de escopo do projeto por ora**: migração/redirecionamento da plataforma atual,
camada de analytics/mensuração, estrutura de campanha de mídia paga, textos jurídicos das páginas legais
(pendentes de redação jurídica) e a página de parceria com contadores/ERP (decisão de negócio ainda não
fechada).

### Key Concepts

- **AR (Autoridade de Registro):** entidade credenciada no ICP-Brasil responsável por validar a identidade do
  titular antes da emissão do certificado digital. A Digital Lock é uma AR vinculada à AC Digital Múltipla.
- **e-CPF:** certificado digital para pessoa física, usado para assinar documentos, declarar Imposto de Renda
  e acessar serviços do governo.
- **e-CNPJ:** certificado digital para empresa/CNPJ, usado para emitir nota fiscal eletrônica e assinar em
  nome da empresa. MEI compra o mesmo SKU do e-CNPJ, com a variação A1 pré-selecionada.
- **Formato A1 / A3:** as duas variações de mídia do certificado. A1 e A3 aparecem como seletor na mesma
  página quando o preço é igual entre variações; viram URL própria quando o produto muda de contexto de venda
  (e-CPF vs. e-CNPJ vs. MEI).
- **SKU de certificado:** unidade de venda do catálogo. Existem 4: e-CPF A1, e-CPF A3, e-CNPJ A1, e-CNPJ A3.
- **Emissão por videoconferência:** validação de identidade ao vivo, feita em plataforma própria da
  certificadora (sistema GFSIS, fora do escopo de construção deste site), que substitui o deslocamento físico
  do cliente.
- **Banco de FAQ:** banco mestre único e categorizado de perguntas frequentes, com 9 categorias (1. Antes de
  comprar, 2. Compra e pagamento, 3. Videoconferência e validação, 4. Emissão e instalação, 5. Uso do
  certificado, 6. Renovação e vencimento, 7. MEI, 8. Revogação/garantia/devolução, 9. Suporte e atendimento).
  Cada página comercial exibe o subconjunto de categorias relevante ao seu contexto; a página "Como Emitir"
  hospeda o banco completo. Cada pergunta tem âncora própria para linkagem direta pelo atendimento.
- **Renovação:** fluxo de recompra para cliente cujo certificado está vencendo. Tecnicamente é uma nova
  emissão (não existe reativação), com toda a documentação apresentada de novo; não exige a mesma certificadora
  nem o mesmo formato A1/A3 do certificado anterior.
- **Painel administrativo:** área editável sem deploy para gerenciar o banco de FAQ, o bloco de "casos de uso"
  do hub de certificados e a tabela de problemas de uso da página de Suporte.
- **AR vs. AC:** a Autoridade de Registro (AR) — papel da Digital Lock — valida a identidade do titular e
  autoriza a emissão, com contato direto com o cliente; a Autoridade Certificadora (AC) — a AC Digital Múltipla
  — emite tecnicamente o certificado. Distinção usada na página Quem Somos para reduzir risco percebido.
- **ITI:** Instituto Nacional de Tecnologia da Informação, órgão federal que administra o ICP-Brasil e mantém a
  listagem pública oficial de ARs credenciadas — usada como prova verificável de credenciamento (rodapé e
  página Quem Somos).
- **Prazo de validação:** o cliente tem 180 dias, contados da compra, para concluir a validação e emissão do
  certificado.
- **Direito de arrependimento:** 7 dias corridos a partir da aprovação do certificado. Devolução de valores
  exige revogação do certificado; equipamentos (token/cartão/leitora), se houver, devem retornar em estado de
  novo.
- **Garantia legal:** 90 dias a partir da validação/entrega, conforme Código de Defesa do Consumidor. Não cobre
  produtos de outras certificadoras nem mídia danificada por defeito de uso.
- **Revogação:** torna o certificado permanente e integralmente inutilizável — não pode ser desfeita. Pode ser
  solicitada pelo titular, responsável (pessoa jurídica), empresa/órgão do titular, ou pela própria AR/AC/Comitê
  Gestor do ICP-Brasil.
- **Declaração de Práticas de Negócio (DPN):** documento formal da AR que rege o ciclo de emissão, regras de
  revogação e obrigações do titular. Versão publicada é a 5.0 (mar/2023) e ainda não menciona a validação por
  videoconferência — atualização junto à AC é uma pendência externa, registrada mas não bloqueante.

## Tech Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.5.9 |
| Framework | Laravel v13.24.0 |
| Starter kit | Livewire Starter Kit oficial — Livewire v4.3.5, Flux UI v2.15.1 (camada Free), Fortify v1.37.3 |
| Rendering / componentes | Livewire/Blaze v1.0.14, Alpine.js (embutido no Livewire) |
| CSS | Tailwind CSS v4 (`@tailwindcss/vite`) |
| Build frontend | Vite v8 (`laravel-vite-plugin`) |
| Banco de dados | MySQL 8.4 |
| Armazenamento de arquivos | Storage nativo do Laravel |
| Ambiente de desenvolvimento local | Laravel Sail (Docker) |
| Testes | PHPUnit v12.5.23, Larastan v3.9, Laravel Pint v1.27 |
| Controle de versão | GitHub |
| Hospedagem | cPanel (deploy via SSH) |
| Execução de fila em produção | `Schedule::command('queue:work --stop-when-empty')` a cada minuto via cron (cPanel compartilhado não sustenta worker persistente) |
| Painel administrativo | Livewire próprio (inclinação da equipe — consistente com o restante do site, que já usa Livewire + Flux; decisão final ainda pendente de confirmação) |
| Gateway de pagamento | Safe2Pay |
| Sistema de emissão | GFSIS — sistema da Autoridade Certificadora que recebe os dados do titular, orquestra a validação por videoconferência e emite o certificado; integração feita pelo e-commerce via API/webhook |
| Provedor de e-mail | Em aberto — decisão adiada pela equipe |

**Identidade visual** (extraída do site real da Digital Lock, não inventada): cor primária de marca `#E40044`,
preto `#000000` e branco `#FFFFFF` como base, botões em formato pill, ícone de impressão digital (fingerprint)
como elemento gráfico da marca, tipografia sans-serif bold nos títulos, fonte Inter (recomendação padrão do
Flux UI).

**Convenções de código já decididas:** controllers focados, com ações isoladas fora do CRUD padrão viradas em
Single Action Controllers (`__invoke`); validação sempre extraída para Form Request classes, nunca inline;
blocos de conteúdo repetidos entre páginas (elegibilidade de videoconferência, "como funciona", credenciamento,
card de produto, accordion de FAQ) implementados como Blade components únicos, nunca HTML duplicado; accordion
de FAQ e seletor de variante A1/A3 construídos à mão com Alpine.js puro, já que os componentes equivalentes do
Flux UI (Accordion, Tabs) são exclusivos da versão Pro e a equipe decidiu usar apenas a Free.

**Regras de URL e SEO (definidas no documento de estrutura, valem para todo o site):** uma URL por intenção,
nunca duas rotas para a mesma página; slug fixo definido antes do desenvolvimento, sem id numérico, data ou
parâmetro; canonical declarada em toda página indexável; uma única versão de domínio (sem duplicação com/sem
`www`, com/sem barra final); páginas transacionais sempre `noindex`/`nofollow`; variação de produto por seletor
na mesma página quando o preço é igual entre A1/A3, e por URL própria quando o produto muda de contexto de
venda; estrutura de redirecionamento planejada como parte do desenvolvimento, não como correção posterior.

**Documentos de referência** (fonte de verdade para conteúdo e estrutura — não reescrever, disponíveis em
`docs/oficial-docs/`): `Digital Lock | Estrutura e Conteúdo do E-commerce, v1.0` (34 páginas — sitemap completo,
regras de URL, copy bloco a bloco de cada página, banco de FAQ com 9 categorias, regras transversais de
conteúdo e nomenclatura, pendências) e `Digital Lock | Wireframes das Páginas, v1.0` (estrutura visual bloco a
bloco das 9 páginas, hierarquia de componentes confirmada). Um terceiro documento, `Digital Lock | Estrutura de
Banco de Dados, v1.0` (05/08/2026, em `.spec/init/`), é a fonte de verdade do modelo de dados — tabelas, campos,
relacionamentos e as convenções de banco (CPF/CNPJ só dígitos, valores monetários em `decimal(10,2)`, sem
exclusão de registro histórico) — e é quem decide o gateway de pagamento (Safe2Pay) e nomeia o sistema de
emissão (GFSIS) acima.

**Regras de nomenclatura (uso obrigatório em todo texto voltado ao cliente):** "Certificado Digital" (nunca
"Certificado" isolado), "e-CNPJ"/"e-CPF" (nunca "PJ"/"PF" isolados), "AC Digital Múltipla" (nunca "AC Digital"),
"Autoridade de Registro credenciada no ICP-Brasil" (nunca "Certificadora"), "Validação por videoconferência"
(nunca "videochamada", "reunião" ou "entrevista"), "Padrão ICP-Brasil" (nunca "certificado oficial" ou
"certificado do governo").

## Core Workflows

### 1. Compra e emissão de certificado digital

1. Cliente navega até uma página de produto (Hub, e-CNPJ, e-CPF ou MEI) e escolhe o SKU — via seletor A1/A3
   quando o preço é igual entre variações na mesma página, ou via URL própria quando o produto muda de
   contexto de venda.
2. Cliente inicia o checkout. Cadastro é obrigatório para prosseguir, mas quem se cadastra **não precisa ser o
   titular do certificado** (ex.: contador comprando para o cliente).
3. Pagamento é realizado, com **Pix como forma prioritária**, via gateway **Safe2Pay**.
4. Pagamento confirmado → nota fiscal da compra é emitida → cliente recebe e-mail com link de agendamento de
   videoconferência.
5. Validação de identidade ao vivo acontece na plataforma própria da certificadora (fora do escopo deste
   site).
6. Certificado é emitido e liberado para download/instalação pelo cliente.

### 2. Renovação de certificado digital

Cliente cujo certificado está vencendo acessa a página de Renovação e refaz o fluxo de compra (Workflow 1)
para o mesmo tipo de certificado que já possui.

### 3. Autoatendimento via banco de FAQ

1. Cliente (ou atendimento em seu nome) acessa uma página comercial e visualiza o subconjunto de perguntas da
   categoria relevante, ou acessa "Como Emitir" para o banco completo.
2. Cada pergunta tem âncora própria, permitindo que o atendimento envie um link direto para uma resposta
   específica sem que o cliente precise navegar pela página inteira.
3. Perguntas frequentes cobrem, entre outros temas, elegibilidade para videoconferência, instalação e
   problemas de uso — a página de Suporte também expõe uma tabela dedicada de problemas de uso (Bloco 6, com
   âncora própria por linha).

Distribuição de categorias por página (categorias definidas em Key Concepts → Banco de FAQ):

| Página | Categorias exibidas |
|---|---|
| `/` (Home) | Seleção curta das categorias 1 e 3 |
| `/certificado-digital/` (Hub) | 1 |
| `/certificado-digital/e-cnpj/` | 1, 3 e 5, com prioridade para contexto empresarial |
| `/certificado-digital/e-cpf/` | 1, 3 e 5, com prioridade para contexto de pessoa física |
| `/certificado-digital-para-mei/` | 7, mais itens selecionados de 1 e 3 |
| `/renovacao-certificado-digital/` | 6 |
| `/como-emitir-certificado-digital/` | Banco completo (todas as 9 categorias) |
| `/suporte/` | 4, 8 e 9 |
| `/trocas-e-devolucoes/` (fora de escopo nesta fase) | 8 |

### 4. Gestão de conteúdo via painel administrativo

Alguém sem conhecimento de programação edita, pelo painel administrativo (Livewire próprio, inclinação da
equipe), três blocos de conteúdo sem depender de deploy: o banco mestre de FAQ, o bloco de "casos de uso" do
hub de certificados, e a tabela de problemas de uso da página de Suporte. Essa camada é planejada para uma fase
posterior à do frontend estático inicial.

### 5. Frontend estático das páginas (Fase 1 — primeira feature a ser construída)

Construção das views Blade das 9 rotas comerciais e de apoio (`/`, `/certificado-digital/`,
`/certificado-digital/e-cnpj/`, `/certificado-digital/e-cpf/`, `/certificado-digital-para-mei/`,
`/renovacao-certificado-digital/`, `/como-emitir-certificado-digital/`, `/quem-somos/`, `/suporte/`), com todo
conteúdo copiado literalmente do documento `Digital Lock | Estrutura e Conteúdo do E-commerce, v1.0`
(seção 5, copy bloco a bloco de cada página — não inventar nem parafrasear) e todos os blocos compartilhados
(header/rodapé, breadcrumb, elegibilidade de videoconferência, passo a passo, credenciamento, card de produto,
FAQ accordion, seletor A1/A3) extraídos em Blade components reutilizáveis. Fase puramente de apresentação: sem
model, migration, controller com lógica real, submit de formulário funcional, ou **qualquer chamada a banco de
dados** — preço, FAQ e demais dados futuros do banco entram como conteúdo estático de exemplo (marcadores
`[PREÇO]` do documento de referência viram valores de exemplo fixos no código). Rotas servidas via
`Route::view()` ou controller mínimo sem lógica de negócio. Critérios de aceite, detalhamento de componentes e
rotas desta fase estão definidos na feature spec correspondente, a ser produzida pela cadeia `/plan`.

## Open Questions

- **Provedor de e-mail:** decisão adiada pela equipe (necessário para o e-mail de agendamento de
  videoconferência no fluxo de emissão).
- **Ferramenta do painel administrativo:** inclinação da equipe é por Livewire próprio (consistência com o
  restante do stack), mas a decisão final entre isso e Filament ainda não foi confirmada.
- **Página de parceria com contadores/ERP:** decisão de negócio ainda não fechada; fora de escopo até então.
- **Textos jurídicos das páginas legais** (Política de Privacidade, Termos de Uso): pendentes de redação
  jurídica externa ao time de desenvolvimento.
- **3DS (autenticação de cartão de crédito) via Safe2Pay MPI:** implementar ou não — decisão do dono do
  negócio, não bloqueia o cartão funcionar (a cobrança em si não depende disso). Sem 3DS, uma compra com
  cartão roubado/clonado que o titular verdadeiro contesta depois (chargeback) é prejuízo integral da Digital
  Lock — o banco do cliente estorna o valor e a loja não tem como reaver. Com 3DS autenticado com sucesso, esse
  risco de chargeback passa a ser do emissor do cartão/bandeira, não da loja (liability shift). Cobre só 4 das
  8 bandeiras aceitas (Visa, Mastercard, Elo, Amex — via `Safe2Pay.Mpi`, script `verify_3DS2.min.js`); JCB,
  Diners, Discover e Aura continuam sem essa proteção mesmo se implementado. Custo: fricção extra no checkout
  (às vezes aparece uma tela de confirmação do banco antes de fechar a compra) e mais complexidade de
  integração/teste. Nenhuma exigência legal ou de bandeira encontrada que torne isso obrigatório (verificado
  contra a documentação oficial da Safe2Pay e a normativa 021 da ABECS, que é sobre outro assunto — padronização
  de mensagens de recusa, não 3DS). Levantamento completo: artifact "Plano do Cofre".
