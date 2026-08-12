# Digital Lock E-commerce — Project Phases

<!-- inputs: project-description.md@sha256:049af1f19974 user-stories.md@sha256:86bca8f6f097 database-schema.md@sha256:d408a546634e -->

## Overview

O build está dividido em **20 phases**. As **Phases 1–10 são o MVP**: fundação de frontend (design tokens e
componentes compartilhados) + as 9 páginas comerciais e de apoio, 100% estáticas, sem model, migration ou
qualquer chamada a banco de dados — exatamente o corte já registrado em `project-description.md` e confirmado
nesta sessão. Essas 10 phases estão detalhadas no mesmo nível de profundidade (critério de aceite + testes por
task). As **Phases 11–20 são o roadmap pós-MVP** (banco de dados, models, autenticação de papéis, catálogo
dinâmico, checkout, pagamento, emissão, renovação, revogação/arrependimento e painel administrativo) —
cobrem toda tabela do schema e toda user story restante, mas em nível mais enxuto (task + critério de aceite
mínimo + testes), a ser refinado com mais detalhe num re-run deste comando quando o time decidir começar essa
etapa. **Nenhuma migration roda antes da Phase 11** — é a fronteira explícita entre "hoje" e "depois".

Foundation-first foi aplicado de forma pragmática: como a Fase 1 do produto é **puramente estática** (sem
model, sem banco), a Phase 1 aqui é a fundação de **frontend** (design tokens + componentes), não de banco —
banco e models (normalmente a fundação de qualquer projeto Laravel) só entram na Phase 11, porque nada nas
Phases 1–10 depende deles. A partir da Phase 11 em diante, a ordem volta ao padrão: banco → models → papéis de
usuário → fluxos de negócio.

Todas as phases e sub-phases são numeradas (`Phase 1`, `Phase 8.2`, …) para referência por agentes de IA.

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase.
- Phases and sub-phases are numbered (`Phase 1`, `Phase 8.2`) for reference by AI agents.
- Business-logic tasks list the **feature tests** to generate; frontend-only tasks list validatable **acceptance criteria** and a **Design ref**.
- **Design ref** aponta para `.spec/init/design/Digital Lock Mockups.dc.html#<âncora>` (âncoras: `home`, `hub`, `ecnpj`, `ecpf`, `mei`, `renov`, `emitir`, `quem`, `sup`) — mockup de alta fidelidade que cobre as 9 páginas estáticas. Ele **supera** três detalhes descritos em `project-description.md`: tipografia é **Plus Jakarta Sans** (títulos/botões) + **IBM Plex Sans** (corpo), não Inter; botões usam **8px de raio** (`rounded-lg`), não pill; o header tem **fundo branco** (não preto — só o rodapé é `#14110f`). A cor de marca `#E40044` permanece a mesma nas duas fontes. Isso está registrado como decisão em Phase 1.
- Telas das Phases 11–20 sem design formal (checkout, minha conta, agendamento, painel administrativo) seguem a identidade visual extraída do mockup (paleta, tipografia, componentes da Phase 1) por decisão do desenvolvedor nesta sessão — não há mockup dedicado para elas.

---

## Phase 1: Fundação de Frontend — Design System e Componentes Compartilhados

**Goal:** estabelecer os tokens visuais (cores, tipografia, raio de borda) fiéis ao mockup aprovado e todos os
componentes Blade/Alpine reutilizados pelas 9 páginas estáticas. · **Depends on:** none · **Covers:** US-8.1,
US-8.2, US-8.3, US-8.4, US-8.5

### Phase 1.1: Tokens de design e tipografia

- [x] **Task:** Configurar tokens de cor no Tailwind (`resources/css/app.css`, bloco `@theme`)
  - **Acceptance criteria:**
    - Tokens definidos: `--color-brand: #E40044`, `--color-ink: #14110f`, `--color-muted: #6b6660`,
      `--color-muted-light: #9c968e`, `--color-surface-alt: #f7f5f2`, `--color-border: #e7e3de`,
      `--color-border-light: #d8d3cc`, `--color-highlight: #fdecf1`, `--color-cta-secondary: #DB3861`
    - Nenhum componente criado nesta fase usa cor fora dessa paleta (em especial, nenhum azul)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [x] **Task:** Trocar a tipografia do starter kit pela do mockup
  - **Acceptance criteria:**
    - `vite.config.js`: `bunny()` troca `'Instrument Sans'` por `'Plus Jakarta Sans'` (pesos 500/600/700/800)
      e `'IBM Plex Sans'` (pesos 400/500/600), igual ao `<helmet>` do mockup
    - `resources/css/app.css`: `--font-sans` (corpo) aponta para IBM Plex Sans; novo token `--font-heading`
      (títulos/botões/labels) aponta para Plus Jakarta Sans
    - Decisão registrada: supera a menção a "Inter" em `project-description.md` — o mockup aprovado é a fonte
      de verdade
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [x] **Task:** Definir convenção de botão (raio de borda)
  - **Acceptance criteria:**
    - Todo botão/CTA usa `rounded-lg` (8px), igual ao mockup — nenhum usa `rounded-full`
    - Decisão registrada: supera a menção a "botões em formato pill" em `project-description.md`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [x] **Task:** Registrar a regra de nomenclatura obrigatória como checklist de revisão de conteúdo
  - **Acceptance criteria:**
    - Documentado (comentário no componente de conteúdo ou nota em `CLAUDE.md`/`.ai/rules`) o uso obrigatório
      de: "Certificado Digital" (nunca "Certificado" isolado), "e-CNPJ"/"e-CPF" (nunca "PJ"/"PF" isolados),
      "AC Digital Múltipla" (nunca "AC Digital"), "Autoridade de Registro credenciada no ICP-Brasil" (nunca
      "Certificadora"), "Validação por videoconferência" (nunca "videochamada", "reunião" ou "entrevista"),
      "Padrão ICP-Brasil" (nunca "certificado oficial" ou "certificado do governo")
    - Todo texto copiado do documento de referência nas Phases 2–10 é revisado contra essa lista antes de
      cada página ser dada como concluída
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.3

### Phase 1.2: Layout público e navegação

- [x] **Task:** Criar `x-layout` (`resources/views/components/layout.blade.php`)
  - **Acceptance criteria:**
    - Header: fundo branco, logo (círculo com borda 2.5px `#E40044` + wordmark "digital**lock**"), nav
      horizontal com 5 itens (Certificados, MEI, Renovação, Como emitir, Suporte), botão "Comprar" fixo à
      direita (fundo `#E40044`, `rounded-lg`)
    - `<x-slot:title>` define o `<title>` da página; `<x-slot:meta_description>` define a meta description
    - Rodapé: fundo `#14110f`, grid de 3 colunas (Empresa/dados · Navegação · Confiança e legal) + barra
      inferior com "Autoridade de Registro credenciada no ICP-Brasil" e link "↑ Topo"
    - Usado pelas 9 páginas; nenhuma delas duplica header/rodapé em HTML solto
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-8.2

- [x] **Task:** Criar `x-breadcrumb`
  - **Acceptance criteria:**
    - Formato `Início › [Seção] › [Subseção]`, separador "›"
    - Ausente apenas na Home; texto correto por página: Hub "Início › Certificado Digital"; e-CNPJ "Início ›
      Certificado Digital › e-CNPJ"; e-CPF "Início › Certificado Digital › e-CPF"; MEI "Início › Certificado
      para MEI"; Renovação "Início › Renovação"; Como emitir "Início › Como emitir"; Quem somos "Início › Quem
      somos"; Suporte "Início › Suporte"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-8.1, US-8.5

- [x] **Task:** Criar `x-eyebrow` (rótulo pequeno em destaque)
  - **Acceptance criteria:**
    - Texto em `#E40044`, uppercase, 11–12px, peso 600
    - Aceita o texto via slot; usado para "Etapa N", "Exclusivo desta página", "Painel editável sem dev",
      "Banco integral de perguntas · nove categorias", "Argumento mais forte da página"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-8.1

### Phase 1.3: Componentes de conteúdo compartilhado

- [x] **Task:** Criar `x-elegibilidade-videoconferencia`
  - **Acceptance criteria:**
    - Reproduz o texto oficial sem alteração: "Já teve certificado digital emitido a partir de 2018, com
      coleta de biometria facial e digital, em qualquer Autoridade de Registro, ou tem CNH emitida ou
      renovada a partir de 2017"
    - Dois blocos com "ou" entre eles, fundo `#fdecf1`
    - Componente único reutilizado nas 7 páginas onde aparece (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação, Como
      Emitir), sem HTML duplicado
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1

- [x] **Task:** Criar `x-passo-a-passo`
  - **Acceptance criteria:**
    - 4 passos idênticos onde aparece: "Escolha e pague / Agende / Valide ao vivo / Baixe e instale",
      numerados em círculo vermelho `#E40044`
    - Prop de variante de fundo (cartão branco sobre seção cinza, ou cartão cinza sobre seção branca) sem
      duplicar a marcação
    - Componente único reutilizado em 6 páginas (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação) + variação de 4
      colunas em Como Emitir
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-3.1

- [x] **Task:** Criar `x-credenciamento`
  - **Acceptance criteria:**
    - Ícone circular (check) + título "Autoridade de Registro credenciada" + texto padrão sobre ICP-Brasil/AC
      Digital Múltipla + link "Ver listagem oficial do ITI →"
    - Componente único reutilizado em 6 páginas (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-8.1, US-6.1

- [x] **Task:** Criar `x-card-produto`
  - **Acceptance criteria:**
    - Props: título, descrição curta, preço ("R$ [PREÇO]"), texto e destino do botão
    - Prop `:featured` aplica borda 2px `#E40044` (variação destacada — usada no card e-CNPJ da Home e no
      card "A1 · recomendado" da MEI); sem a prop usa borda 1px `#e7e3de`
    - Usado no grid de 3 (Home), grids de 2 (Hub, Renovação, MEI)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-1.1, US-1.2

- [x] **Task:** Criar `x-comparison-table`
  - **Acceptance criteria:**
    - Recebe colunas e linhas via prop/slot; cabeçalho `#f7f5f2`, bordas `#e7e3de`, células `13px`
    - Reutilizado nas 5 tabelas comparativas do site (A1×A3 no Hub, A1×A3 nas páginas e-CNPJ/e-CPF, tabela
      completa de SKUs, casos de uso do Hub, AC×AR em Quem Somos, tabela de problemas em Suporte) sem repetir
      marcação de tabela em cada página
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-8.1, US-1.3

### Phase 1.4: Componentes interativos (Alpine.js)

- [x] **Task:** Criar `x-faq-accordion`
  - **Acceptance criteria:**
    - Construído com Alpine.js puro (`x-data`, `x-show`) — não usa o componente Accordion do Flux (exclusivo
      Pro)
    - Cada pergunta abre/fecha individualmente ao clicar; ícone "+" muda de estado ao expandir
    - Recebe lista de perguntas (pergunta, resposta, âncora) via prop; cada item tem `id` de âncora próprio
      no DOM, navegável por link direto (`#ancora`)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-5.1, US-5.2

- [x] **Task:** Criar `x-purchase-panel` (seletor A1/A3 + preço + CTA)
  - **Acceptance criteria:**
    - Construído com Alpine.js puro — não usa o componente Tabs do Flux (exclusivo Pro)
    - Prop `:show-selector` controla se o seletor A1/A3 aparece (`true` em e-CNPJ/e-CPF, `false` em MEI, que
      já vem com A1 fixo, sem seletor visível)
    - Alternar A1/A3 atualiza o preço exibido na mesma URL, sem chamada ao servidor; opção selecionada com
      borda 2px `#E40044`, opção não selecionada com borda 1px `#d8d3cc`
    - Exibe "R$ [PREÇO]" e "ou R$ [PREÇO PIX] no Pix", botão "Comprar agora"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-8.1, US-2.1

---

## Phase 2: Página Home (`/`)

**Goal:** implementar a Home 100% estática, substituindo a `welcome` do starter kit. · **Depends on:** Phase 1
· **Covers:** US-1.1, US-3.1, US-1.3, US-5.1, US-8.4

- [x] **Task:** Rota e esqueleto da página
  - **Acceptance criteria:**
    - `Route::view('/', 'pages.home')->name('home')` substitui a rota `welcome` atual
    - `<title>`: "Certificado Digital A1 e A3 | e-CPF e e-CNPJ Online | Digital Lock"; meta description
      conforme documento de referência (`docs/oficial-docs`)
    - Usa `x-layout`, sem breadcrumb
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1, workflow "5. Frontend estático das páginas"

- [x] **Task:** Bloco 1 — dobra inicial
  - **Acceptance criteria:**
    - H1 "Certificado Digital sem sair de onde você está", linha de apoio, botões "Ver certificados"/"Falar
      no WhatsApp", selos abaixo dos botões
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1

- [x] **Task:** Bloco 2 — escolha de perfil (3× `x-card-produto`)
  - **Acceptance criteria:**
    - Cards e-CPF / e-CNPJ (`:featured`) / Sou MEI, cada um com preço "A partir de [PREÇO]" e botão próprio
    - Preço e destino dos 3 cartões renderizados no HTML, sem chamada assíncrona
    - Link "Não sabe qual escolher? Compare os tipos de certificado →" para `/certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1

- [x] **Task:** Blocos 3–4 — elegibilidade e passo a passo
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia` e `x-passo-a-passo` inseridos sem HTML próprio adicional
    - Link "Veja o passo a passo completo →" para `/como-emitir-certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-3.1, US-8.1

- [x] **Task:** Blocos 5–7 — A1 ou A3, diferenciais, renovação
  - **Acceptance criteria:**
    - Bloco 5: comparativo resumido A1/A3 (2 cards) + link "Ver comparativo completo →" para
      `/certificado-digital/`
    - Bloco 6: grid de 5 selos ("O que você encontra aqui")
    - Bloco 7: CTA "Renovar meu certificado" (fundo `#DB3861`) para `/renovacao-certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.3

- [x] **Task:** Bloco 8 — FAQ curto + Bloco 9 — contato
  - **Acceptance criteria:**
    - `x-faq-accordion` com 4 perguntas das categorias 1 (Antes de comprar) e 3 (Videoconferência e
      validação); link "Ver todas as dúvidas →" para `/como-emitir-certificado-digital/`
    - Formulário visual (Nome, E-mail, WhatsApp, Mensagem) e botão "Enviar" sem ação de submit — campo
      hardcoded, sem lógica real nesta fase
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-5.1

- [x] **Task:** Ordem de blocos no mobile
  - **Acceptance criteria:**
    - Em telas mobile a ordem vertical é 1, 2, 4, 3, 5, 6, 7, 8, 9 (o bloco de elegibilidade desce uma
      posição em relação ao desktop)
    - Nenhum scroll horizontal em nenhuma largura de tela
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.4

---

## Phase 3: Página Hub de Certificados (`/certificado-digital/`)

**Goal:** página de decisão que compara e-CPF×e-CNPJ e A1×A3, distribuindo para as páginas de produto. ·
**Depends on:** Phase 1 · **Covers:** US-1.2, US-1.3, US-1.4, US-6.1, US-5.1

- [ ] **Task:** Rota, esqueleto e breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Certificado Digital A1 e A3 | e-CPF e e-CNPJ | Digital Lock"
    - `x-breadcrumb` com "Início › Certificado Digital"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-1.2, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + Bloco 2 — e-CPF vs. e-CNPJ (2× `x-card-produto`)
  - **Acceptance criteria:**
    - H1 "Certificado Digital", botões "Ver certificados"/"Falar no WhatsApp"
    - 2 cards (e-CPF, e-CNPJ) + texto "Tem empresa e também precisa assinar como pessoa física? São
      certificados independentes."
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-1.2

- [ ] **Task:** Bloco 3 — tabela A1×A3 completa + Bloco 4 — tabela de todos os certificados
  - **Acceptance criteria:**
    - `x-comparison-table` com os 9 critérios (onde fica, validade, exige equipamento, uso em mais de um
      computador, enviar ao contador, se o computador quebrar, se o token for perdido, sistema operacional,
      software de nota fiscal)
    - Tabela dos 4 SKUs (e-CPF A1/A3, e-CNPJ A1/A3) com preço e botão próprio por linha
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-1.3

- [ ] **Task:** Bloco 5 — casos de uso
  - **Acceptance criteria:**
    - `x-comparison-table` com as 10 situações → certificado recomendado do documento de referência
    - `x-eyebrow` "Painel editável sem dev" acima do bloco
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-1.4

- [ ] **Task:** Blocos 6–9 — elegibilidade, passo a passo, renovação, credenciamento
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia`, `x-passo-a-passo`, CTA "Renovar meu certificado", `x-credenciamento`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-3.1, US-6.1

- [ ] **Task:** Bloco 10 — FAQ (categoria 1) + Bloco 11 — fechamento
  - **Acceptance criteria:**
    - `x-faq-accordion` com perguntas da categoria "Antes de comprar"
    - 2 botões "Comprar e-CPF"/"Comprar e-CNPJ" + link "Fale com a gente no WhatsApp"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-5.1

---

## Phase 4: Página Certificado e-CNPJ (`/certificado-digital/e-cnpj/`)

**Goal:** página de venda para pessoa jurídica, com seletor de variação A1/A3. · **Depends on:** Phase 1 ·
**Covers:** US-2.1

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Certificado Digital e-CNPJ A1 e A3 | Emissão Online | Digital Lock"
    - `x-breadcrumb` "Início › Certificado Digital › e-CNPJ"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-2.1, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + `x-purchase-panel` (`:show-selector="true"`)
  - **Acceptance criteria:**
    - H1 "Certificado Digital e-CNPJ", linha de apoio, selos (Padrão ICP-Brasil · Emissão por videoconferência
      · Sem taxa extra)
    - Seletor A1/A3 funcional trocando preço exibido, botão "Comprar agora"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 2 — "o que sua empresa faz com o e-CNPJ" (7 itens) + Bloco 3 — tabela A1×A3 (5 critérios)
  - **Acceptance criteria:**
    - Grid de 2 colunas com os 7 usos (NF-e/NFS-e/NFC-e, e-CAC, eSocial/EFD-Reinf, contratos, Conectividade
      Social/FGTS Digital, licitações, integração com sistema de gestão)
    - `x-comparison-table` com 5 critérios (onde fica, validade, uso em vários computadores, precisa de
      equipamento, software de nota fiscal)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 4 — elegibilidade + Bloco 5 — passo a passo
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia`, `x-passo-a-passo`
  - **Traces:** US-2.1, US-3.1

- [ ] **Task:** Bloco 6 — documentos (empresa/responsável)
  - **Acceptance criteria:**
    - 2 cards: "Da empresa" (ato constitutivo, documentos de eleição, cartão CNPJ) e "Do responsável"
      (documento de identidade oficial com foto e CPF)
    - Nota sobre biometria já cadastrada dispensar apresentação de documentos
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 7 — credenciamento + Bloco 8 — FAQ (categorias 1, 3, 5) + Bloco 9 — fechamento
  - **Acceptance criteria:**
    - `x-credenciamento`; `x-faq-accordion` priorizando contexto empresarial; fechamento com preço + botão
      "Comprar e-CNPJ"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-6.1, US-5.1

---

## Phase 5: Página Certificado e-CPF (`/certificado-digital/e-cpf/`)

**Goal:** página de venda para pessoa física, com seletor de variação A1/A3. · **Depends on:** Phase 1 ·
**Covers:** US-2.1

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Certificado Digital e-CPF A1 e A3 | Emissão Online | Digital Lock"
    - `x-breadcrumb` "Início › Certificado Digital › e-CPF"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-2.1, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + `x-purchase-panel` (`:show-selector="true"`)
  - **Acceptance criteria:**
    - H1 "Certificado Digital e-CPF", selos idênticos ao padrão das páginas de produto
    - Seletor A1/A3 funcional, botão "Comprar agora"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 2 — "o que você faz com o e-CPF" (7 itens) + Bloco 3 — tabela A1×A3 (5 critérios)
  - **Acceptance criteria:**
    - Grid de 2 colunas com os 7 usos (contratos/procurações, IR, e-CAC, gov.br nível ouro, INSS, Judiciário,
      procurador)
    - `x-comparison-table` com 5 critérios (onde fica, validade, uso em vários computadores, precisa de
      equipamento, sistema operacional)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 4 — elegibilidade + Bloco 5 — passo a passo
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia`, `x-passo-a-passo`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-2.1, US-3.1

- [ ] **Task:** Bloco 6 — documentos (identidade + CPF)
  - **Acceptance criteria:**
    - 2 cards: documento de identidade oficial com foto (RG, passaporte, título de eleitor, CNH; CNE para
      estrangeiro domiciliado, passaporte para não domiciliado) e CPF se não constar no documento
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-2.1

- [ ] **Task:** Bloco 7 — credenciamento + Bloco 8 — FAQ (categorias 1, 3, 5) + Bloco 9 — fechamento
  - **Acceptance criteria:**
    - `x-credenciamento`; `x-faq-accordion` priorizando contexto de pessoa física; fechamento com preço +
      botão "Comprar e-CPF"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecpf`
  - **Traces:** US-6.1, US-5.1

---

## Phase 6: Página Certificado para MEI (`/certificado-digital-para-mei/`)

**Goal:** página de captura por segmento, vendendo o mesmo SKU do e-CNPJ com A1 pré-selecionado, resolvendo a
confusão com o CCMEI. · **Depends on:** Phase 1 · **Covers:** US-2.2

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Certificado Digital para MEI | e-CNPJ com Emissão Online | Digital Lock"
    - `x-breadcrumb` "Início › Certificado para MEI" (nível único, sem "Certificado Digital" como pai)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + `x-purchase-panel` (`:show-selector="false"`)
  - **Acceptance criteria:**
    - H1 "Certificado Digital para MEI"; painel de preço único (sem seletor A1/A3 visível), botão "Comprar
      agora" leva ao mesmo produto do e-CNPJ com A1 pré-selecionado
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2

- [ ] **Task:** Bloco 2 — "Certificado Digital não é o mesmo que CCMEI"
  - **Acceptance criteria:**
    - 2 cards (CCMEI / Certificado Digital) + texto "São documentos diferentes e você provavelmente vai
      precisar dos dois."
    - Conteúdo deste bloco **não** é copiado para a página e-CNPJ (evita duplicidade de conteúdo)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2

- [ ] **Task:** Bloco 3 — "o que o MEI faz" (6 itens) + Bloco 4 — "você precisa de certificado se" (5 itens)
  - **Acceptance criteria:**
    - `x-eyebrow` "Exclusivo desta página" acima do Bloco 4
    - Conteúdo destes blocos **não** é copiado para a página e-CNPJ
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2

- [ ] **Task:** Bloco 5 — "O MEI compra o e-CNPJ" (2× `x-card-produto`, A1 `:featured` "recomendado")
  - **Acceptance criteria:**
    - Texto "Não existe versão específica de MEI e não existe preço diferente por ser microempreendedor";
      card A1 com rótulo "A1 · recomendado" (`:featured`), card A3 sem destaque
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2

- [ ] **Task:** Blocos 6–7 — elegibilidade + passo a passo
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia` e `x-passo-a-passo` inseridos sem HTML próprio adicional
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2, US-3.1

- [ ] **Task:** Bloco 8 — documentos (CCMEI, cartão CNPJ, identidade, CPF)
  - **Acceptance criteria:**
    - Grid com os 4 itens: CCMEI (baixado no Portal do Empreendedor), Cartão CNPJ, documento de identidade
      com foto, CPF (se não constar no documento)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-2.2

- [ ] **Task:** Bloco 9 — credenciamento + Bloco 10 — FAQ (categoria 7 + itens de 1 e 3) + Bloco 11 — fechamento
  - **Acceptance criteria:**
    - `x-credenciamento`; `x-faq-accordion` com categoria "MEI" priorizada; botão "Comprar certificado para
      MEI"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#mei`
  - **Traces:** US-6.1, US-5.1

---

## Phase 7: Página Renovação (`/renovacao-certificado-digital/`)

**Goal:** página curta para público qualificado, resolvendo as duas objeções (mesma certificadora? processo
diferente?). · **Depends on:** Phase 1 · **Covers:** US-4.1, US-4.2 (conteúdo estático)

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Renovação de Certificado Digital Online | e-CPF e e-CNPJ | Digital Lock"
    - `x-breadcrumb` "Início › Renovação"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial (fundo gradiente `#fdecf1`→branco)
  - **Acceptance criteria:**
    - H1 "Renovação de Certificado Digital", 2× `x-card-produto` (Renovar e-CPF / Renovar e-CNPJ, CTA
      `#DB3861`), selos (Padrão ICP-Brasil · Videoconferência sem custo extra · Aceita certificado de
      qualquer certificadora)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1

- [ ] **Task:** Bloco 2 — "não precisa ser na mesma certificadora" + Bloco 3 — passo a passo
  - **Acceptance criteria:**
    - Texto: "Certificado digital não tem fidelidade... Também não existe transferência ou migração."
    - `x-passo-a-passo` + nota "toda a documentação é apresentada novamente... não há processo simplificado"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1

- [ ] **Task:** Bloco 4 — "quando renovar" (2 cards)
  - **Acceptance criteria:**
    - Cards "Antes de vencer · ideal" e "Depois de vencer" + nota "certificado vencido não é renovado nem
      reativado. Em ambos os casos é emitido um certificado novo."
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1

- [ ] **Task:** Bloco 5 — elegibilidade + Bloco 6 — "renove no mesmo tipo ou mude" (2 cards)
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia`; cards "Estava no A3 e quer o A1?"/"Estava no A1 e quer o A3?" +
      link "Ver comparativo entre A1 e A3 →" para `/certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1, US-4.2

- [ ] **Task:** Bloco 7 — documentos (e-CPF/e-CNPJ) + Bloco 8 — credenciamento
  - **Acceptance criteria:**
    - 2 cards (documentos para e-CPF / documentos para e-CNPJ); `x-credenciamento` logo abaixo
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-4.1, US-6.1

- [ ] **Task:** Bloco 9 — FAQ (categoria 6) + Bloco 10 — fechamento
  - **Acceptance criteria:**
    - `x-faq-accordion` categoria "Renovação e vencimento"; botões "Renovar e-CPF"/"Renovar e-CNPJ"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#renov`
  - **Traces:** US-5.1

---

## Phase 8: Página Como Emitir (`/como-emitir-certificado-digital/`)

**Goal:** maior página do site — passo a passo completo em 6 etapas + banco integral de FAQ (9 categorias). É
a página mais usada pelo atendimento. · **Depends on:** Phase 1 · **Covers:** US-3.1, US-5.2

### Phase 8.1: Processo e etapas 1–6

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Como Emitir Certificado Digital Online | Passo a Passo | Digital Lock"
    - `x-breadcrumb` "Início › Como emitir"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + Bloco 2 — "o processo em quatro etapas" (`x-passo-a-passo`)
  - **Acceptance criteria:**
    - H1 "Como emitir seu Certificado Digital", botão "Ver certificados"; 4 passos numerados idênticos ao
      componente `x-passo-a-passo` + nota "o tempo depende principalmente do horário que você escolher"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

- [ ] **Task:** Bloco 3 — Etapa 1 "Confira se você se enquadra" (`x-eyebrow` + `x-elegibilidade-videoconferencia`)
  - **Acceptance criteria:**
    - `x-eyebrow` "Etapa 1"; `x-elegibilidade-videoconferencia`; nota "se você não se enquadra em nenhuma das
      duas, fale com a nossa equipe antes de comprar"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

- [ ] **Task:** Bloco 4 — Etapa 2 "Separe os documentos" (2 cards e-CPF/e-CNPJ)
  - **Acceptance criteria:**
    - Nota: "Havendo divergência nos dados, a emissão é suspensa até a regularização."
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

- [ ] **Task:** Bloco 5 — Etapa 3 "Agende sua videoconferência"
  - **Acceptance criteria:**
    - Texto sobre e-mail de agendamento + lista do que ter em mãos (número do pedido, nome completo, CPF do
      titular, data de nascimento, razão social/CNPJ se PJ, telefone, e-mail)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

- [ ] **Task:** Bloco 6 — Etapa 4 "Prepare o ambiente" + Bloco 7 — Etapa 5 "O que acontece na chamada"
  - **Acceptance criteria:**
    - Etapa 4: grid de 6 cuidados + nota LGPD sobre gravação/dossiê eletrônico
    - Etapa 5: texto sobre conferência (não entrevista) + regra de quem pode validar (titular, responsável
      legal PJ, procuração pública com validade de 90 dias, ou curatela)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

- [ ] **Task:** Bloco 8 — Etapa 6 "Emitir e instalar" + Bloco 9 — "Começando a usar"
  - **Acceptance criteria:**
    - 2 cards (A1 · duas senhas / A3 · PIN e PUK) + nota sobre cópia de segurança
    - Texto sobre validade jurídica só em meio eletrônico (documento impresso perde a validade)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-3.1

### Phase 8.2: Banco integral de FAQ e fechamento

- [ ] **Task:** Bloco 10 — banco integral de perguntas (9 categorias)
  - **Acceptance criteria:**
    - `x-eyebrow` "Banco integral de perguntas · nove categorias"
    - Chips visuais das 9 categorias (1 a 9, nomes conforme `project-description.md` → Key Concepts → Banco de
      FAQ)
    - `x-faq-accordion` com o banco completo de perguntas (todas as 9 categorias), cada pergunta com âncora
      própria
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-5.2

- [ ] **Task:** Bloco 11 — fechamento
  - **Acceptance criteria:**
    - H2 "Pronto para começar?" + botões "Comprar e-CPF"/"Comprar e-CNPJ"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-5.2

---

## Phase 9: Página Quem Somos (`/quem-somos/`)

**Goal:** reduzir risco percebido, provando o credenciamento e explicando AC×AR. · **Depends on:** Phase 1 ·
**Covers:** US-6.1, US-6.2, US-6.3

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Quem Somos | Autoridade de Registro Credenciada ICP-Brasil | Digital Lock"
    - `x-breadcrumb` "Início › Quem somos"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.1, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + Bloco 2 — "o que a Digital Lock faz"
  - **Acceptance criteria:**
    - H1 "Quem é a Digital Lock", linha de apoio; texto explicando o papel de AR (recebe o pedido, confere
      identidade, autoriza a emissão) e o padrão ICP-Brasil
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.1

- [ ] **Task:** Bloco 3 — "Somos credenciados. Confira você mesmo."
  - **Acceptance criteria:**
    - `x-eyebrow` "Argumento mais forte da página"
    - Botão "Ver a listagem oficial no site do ITI" aponta para a URL oficial do ITI (link externo, não uma
      imagem de selo)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.1

- [ ] **Task:** Bloco 4 — tabela AC × AR (`x-comparison-table`)
  - **Acceptance criteria:**
    - 3 critérios: o que faz, contato com o cliente, padrão do certificado
    - Nota: "O que muda entre as empresas é preço e atendimento, não a validade do documento."
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.2

- [ ] **Task:** Bloco 5 — "Como a gente trabalha" (grid de 4)
  - **Acceptance criteria:**
    - 4 cards: "Preço no site, sem orçamento", "Validação online, em todo o Brasil", "Sem taxa escondida",
      "Suporte que resolve"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.1

- [ ] **Task:** Bloco 6 — "Nossos dados" + Bloco 7 — fechamento
  - **Acceptance criteria:**
    - Grid com razão social, CNPJ, endereço completo, telefone, WhatsApp, e-mail, horário de atendimento
    - Estes dados aparecem **somente** aqui, no rodapé, nas páginas legais e na nota fiscal — nunca na dobra
      inicial, em título, headline ou bloco de diferenciais de qualquer página
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#quem`
  - **Traces:** US-6.3

---

## Phase 10: Página Suporte (`/suporte/`)

**Goal:** autoatendimento pós-venda com triagem por âncora, indexável mas fora de campanha paga. ·
**Depends on:** Phase 1 · **Covers:** US-5.3, US-5.4

- [ ] **Task:** Rota, esqueleto, breadcrumb
  - **Acceptance criteria:**
    - `<title>`: "Suporte ao Certificado Digital | Digital Lock"
    - `x-breadcrumb` "Início › Suporte"; página **sem** `noindex` (indexável), mas fora de campanha paga
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3, US-8.5

- [ ] **Task:** Bloco 1 — dobra inicial + Bloco 2 — triagem (4 cards por âncora)
  - **Acceptance criteria:**
    - 4 cards: "Comprei e não recebi nada" → `#bloco-3`, "Vou fazer a videoconferência" → `#bloco-4`,
      "Preciso instalar" → `#bloco-5`, "Já uso e deu problema" → `#bloco-6`
    - Cada link de âncora rola até o bloco correspondente na mesma página, sem recarregar
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3

- [ ] **Task:** Bloco 3 — "Comprei e ainda não recebi as instruções"
  - **Acceptance criteria:**
    - 4 respostas (Pix, cartão, e-mail de agendamento não veio, não sabe o número do pedido)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3

- [ ] **Task:** Bloco 4 — "Vou fazer a validação por videoconferência"
  - **Acceptance criteria:**
    - Grid de 5 cuidados + nota "validação não concluída não faz você perder a compra"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3

- [ ] **Task:** Bloco 5 — "Preciso instalar o certificado"
  - **Acceptance criteria:**
    - 2 cards (A1/A3) + nota sobre compatibilidade de sistema operacional
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3

- [ ] **Task:** Bloco 6 — "Já uso e deu problema" (`x-comparison-table`, 10 linhas)
  - **Acceptance criteria:**
    - Cada linha da tabela tem âncora própria (`id` no `<tr>` ou wrapper), para o atendimento enviar link
      direto do trecho
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.4

- [ ] **Task:** Bloco 7 — "Fale com a nossa equipe" (canais)
  - **Acceptance criteria:**
    - Grid de 4 (WhatsApp, Telefone, E-mail, Horário) + nota "tenha em mãos o número do pedido ou o CPF/CNPJ
      do titular"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#sup`
  - **Traces:** US-5.3

---

## Open Questions

- **Phases 11–20 (banco, models, checkout, pagamento, GFSIS, painel administrativo) permanecem adiadas para um
  próximo re-run deste comando** — decisão confirmada nesta sessão (2026-08-11). Nenhuma das 34 tabelas de
  `database-schema.md` (14 de lookup + 20 de domínio) está coberta por task nesta versão do documento; a
  cobertura mecânica de tabela→task só se aplica a partir do dia em que essas phases forem escritas. Pelo mesmo
  motivo, as stories funcionais/backend ainda não têm task própria — **excluídas por decisão do desenvolvedor,
  não por esquecimento**: US-2.3 (cadastro no checkout), US-2.4 (pagamento Pix/Safe2Pay), US-2.5 (nota fiscal),
  US-3.2 (e-mail de agendamento), US-3.3 (agendar/realizar videoconferência via GFSIS), US-3.4 (baixar/instalar
  certificado), US-5.5 (arrependimento/reembolso), US-5.6 (revogação), US-7.1/US-7.2/US-7.3 (painel
  administrativo).
- **`database-schema.md` não tem mais tabela para o banco de FAQ, casos de uso do Hub e tabela de problemas do
  Suporte** (`faq_categories`, `faq_questions`, `use_cases`, `support_issues` foram removidas do schema nesta
  sessão, para mantê-lo 1:1 com `Digital Lock | Estrutura de Banco de Dados, v1.0`, que não cobre esse escopo).
  Isso não bloqueia as Phases 1–10 (conteúdo estático, sem banco), mas **bloqueia a implementação futura de
  US-7.1, US-7.2 e US-7.3** (Feature Area 7 — edição sem deploy pelo painel administrativo) e da parte editável
  de US-5.4 (tabela de problemas de uso), já que hoje não existe tabela para persistir esse conteúdo. Antes de
  detalhar a Phase do painel administrativo, `database-schema.md` precisa de um adendo com essas tabelas (fora
  do escopo do documento de referência, teria que ser modelado à parte, como na versão anterior deste schema).

