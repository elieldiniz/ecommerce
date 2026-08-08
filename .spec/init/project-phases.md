# Digital Lock E-commerce — Project Phases

<!-- inputs: project-description.md@sha256:cb6009f4181e user-stories.md@sha256:5fb32d8adb92 database-schema.md@sha256:87d4ac98566e -->

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

- [ ] **Task:** Configurar tokens de cor no Tailwind (`resources/css/app.css`, bloco `@theme`)
  - **Acceptance criteria:**
    - Tokens definidos: `--color-brand: #E40044`, `--color-ink: #14110f`, `--color-muted: #6b6660`,
      `--color-muted-light: #9c968e`, `--color-surface-alt: #f7f5f2`, `--color-border: #e7e3de`,
      `--color-border-light: #d8d3cc`, `--color-highlight: #fdecf1`, `--color-cta-secondary: #DB3861`
    - Nenhum componente criado nesta fase usa cor fora dessa paleta (em especial, nenhum azul)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [ ] **Task:** Trocar a tipografia do starter kit pela do mockup
  - **Acceptance criteria:**
    - `vite.config.js`: `bunny()` troca `'Instrument Sans'` por `'Plus Jakarta Sans'` (pesos 500/600/700/800)
      e `'IBM Plex Sans'` (pesos 400/500/600), igual ao `<helmet>` do mockup
    - `resources/css/app.css`: `--font-sans` (corpo) aponta para IBM Plex Sans; novo token `--font-heading`
      (títulos/botões/labels) aponta para Plus Jakarta Sans
    - Decisão registrada: supera a menção a "Inter" em `project-description.md` — o mockup aprovado é a fonte
      de verdade
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [ ] **Task:** Definir convenção de botão (raio de borda)
  - **Acceptance criteria:**
    - Todo botão/CTA usa `rounded-lg` (8px), igual ao mockup — nenhum usa `rounded-full`
    - Decisão registrada: supera a menção a "botões em formato pill" em `project-description.md`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.2

- [ ] **Task:** Registrar a regra de nomenclatura obrigatória como checklist de revisão de conteúdo
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

- [ ] **Task:** Criar `x-layout` (`resources/views/components/layout.blade.php`)
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

- [ ] **Task:** Criar `x-breadcrumb`
  - **Acceptance criteria:**
    - Formato `Início › [Seção] › [Subseção]`, separador "›"
    - Ausente apenas na Home; texto correto por página: Hub "Início › Certificado Digital"; e-CNPJ "Início ›
      Certificado Digital › e-CNPJ"; e-CPF "Início › Certificado Digital › e-CPF"; MEI "Início › Certificado
      para MEI"; Renovação "Início › Renovação"; Como emitir "Início › Como emitir"; Quem somos "Início › Quem
      somos"; Suporte "Início › Suporte"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-8.1, US-8.5

- [ ] **Task:** Criar `x-eyebrow` (rótulo pequeno em destaque)
  - **Acceptance criteria:**
    - Texto em `#E40044`, uppercase, 11–12px, peso 600
    - Aceita o texto via slot; usado para "Etapa N", "Exclusivo desta página", "Painel editável sem dev",
      "Banco integral de perguntas · nove categorias", "Argumento mais forte da página"
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#emitir`
  - **Traces:** US-8.1

### Phase 1.3: Componentes de conteúdo compartilhado

- [ ] **Task:** Criar `x-elegibilidade-videoconferencia`
  - **Acceptance criteria:**
    - Reproduz o texto oficial sem alteração: "Já teve certificado digital emitido a partir de 2018, com
      coleta de biometria facial e digital, em qualquer Autoridade de Registro, ou tem CNH emitida ou
      renovada a partir de 2017"
    - Dois blocos com "ou" entre eles, fundo `#fdecf1`
    - Componente único reutilizado nas 7 páginas onde aparece (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação, Como
      Emitir), sem HTML duplicado
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1

- [ ] **Task:** Criar `x-passo-a-passo`
  - **Acceptance criteria:**
    - 4 passos idênticos onde aparece: "Escolha e pague / Agende / Valide ao vivo / Baixe e instale",
      numerados em círculo vermelho `#E40044`
    - Prop de variante de fundo (cartão branco sobre seção cinza, ou cartão cinza sobre seção branca) sem
      duplicar a marcação
    - Componente único reutilizado em 6 páginas (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação) + variação de 4
      colunas em Como Emitir
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-3.1

- [ ] **Task:** Criar `x-credenciamento`
  - **Acceptance criteria:**
    - Ícone circular (check) + título "Autoridade de Registro credenciada" + texto padrão sobre ICP-Brasil/AC
      Digital Múltipla + link "Ver listagem oficial do ITI →"
    - Componente único reutilizado em 6 páginas (Home, Hub, e-CNPJ, e-CPF, MEI, Renovação)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#ecnpj`
  - **Traces:** US-8.1, US-6.1

- [ ] **Task:** Criar `x-card-produto`
  - **Acceptance criteria:**
    - Props: título, descrição curta, preço ("R$ [PREÇO]"), texto e destino do botão
    - Prop `:featured` aplica borda 2px `#E40044` (variação destacada — usada no card e-CNPJ da Home e no
      card "A1 · recomendado" da MEI); sem a prop usa borda 1px `#e7e3de`
    - Usado no grid de 3 (Home), grids de 2 (Hub, Renovação, MEI)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-1.1, US-1.2

- [ ] **Task:** Criar `x-comparison-table`
  - **Acceptance criteria:**
    - Recebe colunas e linhas via prop/slot; cabeçalho `#f7f5f2`, bordas `#e7e3de`, células `13px`
    - Reutilizado nas 5 tabelas comparativas do site (A1×A3 no Hub, A1×A3 nas páginas e-CNPJ/e-CPF, tabela
      completa de SKUs, casos de uso do Hub, AC×AR em Quem Somos, tabela de problemas em Suporte) sem repetir
      marcação de tabela em cada página
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#hub`
  - **Traces:** US-8.1, US-1.3

### Phase 1.4: Componentes interativos (Alpine.js)

- [ ] **Task:** Criar `x-faq-accordion`
  - **Acceptance criteria:**
    - Construído com Alpine.js puro (`x-data`, `x-show`) — não usa o componente Accordion do Flux (exclusivo
      Pro)
    - Cada pergunta abre/fecha individualmente ao clicar; ícone "+" muda de estado ao expandir
    - Recebe lista de perguntas (pergunta, resposta, âncora) via prop; cada item tem `id` de âncora próprio
      no DOM, navegável por link direto (`#ancora`)
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-8.1, US-5.1, US-5.2

- [ ] **Task:** Criar `x-purchase-panel` (seletor A1/A3 + preço + CTA)
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

- [ ] **Task:** Rota e esqueleto da página
  - **Acceptance criteria:**
    - `Route::view('/', 'pages.home')->name('home')` substitui a rota `welcome` atual
    - `<title>`: "Certificado Digital A1 e A3 | e-CPF e e-CNPJ Online | Digital Lock"; meta description
      conforme documento de referência (`docs/oficial-docs`)
    - Usa `x-layout`, sem breadcrumb
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1, workflow "5. Frontend estático das páginas"

- [ ] **Task:** Bloco 1 — dobra inicial
  - **Acceptance criteria:**
    - H1 "Certificado Digital sem sair de onde você está", linha de apoio, botões "Ver certificados"/"Falar
      no WhatsApp", selos abaixo dos botões
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1

- [ ] **Task:** Bloco 2 — escolha de perfil (3× `x-card-produto`)
  - **Acceptance criteria:**
    - Cards e-CPF / e-CNPJ (`:featured`) / Sou MEI, cada um com preço "A partir de [PREÇO]" e botão próprio
    - Preço e destino dos 3 cartões renderizados no HTML, sem chamada assíncrona
    - Link "Não sabe qual escolher? Compare os tipos de certificado →" para `/certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.1

- [ ] **Task:** Blocos 3–4 — elegibilidade e passo a passo
  - **Acceptance criteria:**
    - `x-elegibilidade-videoconferencia` e `x-passo-a-passo` inseridos sem HTML próprio adicional
    - Link "Veja o passo a passo completo →" para `/como-emitir-certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-3.1, US-8.1

- [ ] **Task:** Blocos 5–7 — A1 ou A3, diferenciais, renovação
  - **Acceptance criteria:**
    - Bloco 5: comparativo resumido A1/A3 (2 cards) + link "Ver comparativo completo →" para
      `/certificado-digital/`
    - Bloco 6: grid de 5 selos ("O que você encontra aqui")
    - Bloco 7: CTA "Renovar meu certificado" (fundo `#DB3861`) para `/renovacao-certificado-digital/`
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-1.3

- [ ] **Task:** Bloco 8 — FAQ curto + Bloco 9 — contato
  - **Acceptance criteria:**
    - `x-faq-accordion` com 4 perguntas das categorias 1 (Antes de comprar) e 3 (Videoconferência e
      validação); link "Ver todas as dúvidas →" para `/como-emitir-certificado-digital/`
    - Formulário visual (Nome, E-mail, WhatsApp, Mensagem) e botão "Enviar" sem ação de submit — campo
      hardcoded, sem lógica real nesta fase
  - **Design ref:** `.spec/init/design/Digital Lock Mockups.dc.html#home`
  - **Traces:** US-5.1

- [ ] **Task:** Ordem de blocos no mobile
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
    - Chips visuais das 9 categorias (1 a 9, nomes conforme `database-schema.md` → Lookup Table Seeds →
      `faq_categories`)
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

## Phase 11: Fundação de Banco de Dados

**Goal:** criar todas as migrations e seeders de lookup do schema definido em `database-schema.md`, na ordem
certa de dependências. · **Depends on:** none (mas só começa após a Phase 10 entregue, por decisão do time) ·
**Covers:** as 25 tabelas do schema

### Phase 11.1: Tabelas de lookup

- [ ] **Task:** Migrations + seeders das 11 tabelas de lookup
  - **Acceptance criteria:**
    - Criadas `roles`, `certificate_types`, `certificate_formats`, `payment_methods`, `order_statuses`,
      `payment_statuses`, `certificate_statuses`, `appointment_statuses`, `revocation_request_statuses`,
      `withdrawal_request_statuses`, `faq_categories`, cada uma seguindo as colunas de `database-schema.md`
    - Seeders populam exatamente os valores de "Lookup Table Seeds" (ex.: `roles`: cliente, administrador)
  - **Feature tests:** `LookupTablesSeededTest` — cada tabela de lookup contém as slugs esperadas após
    `db:seed`
  - **Traces:** roles, certificate_types, certificate_formats, payment_methods, order_statuses,
    payment_statuses, certificate_statuses, appointment_statuses, revocation_request_statuses,
    withdrawal_request_statuses, faq_categories

### Phase 11.2: Catálogo e extensão de usuários

- [ ] **Task:** Migration `certificate_skus` + seeder dos 4 SKUs
  - **Acceptance criteria:**
    - Colunas conforme schema (`price`/`price_pix` decimal(10,2), `validity_months`, `is_active`),
      `UNIQUE(certificate_type_id, certificate_format_id)`
    - Seeder cria e-CPF A1, e-CPF A3, e-CNPJ A1, e-CNPJ A3
  - **Feature tests:** `CertificateSkuUniquePerTypeAndFormatTest` — segunda linha com a mesma combinação
    type+format lança erro de constraint
  - **Traces:** certificate_skus

- [ ] **Task:** Migration adiciona `role_id` à tabela `users`
  - **Acceptance criteria:**
    - FK `role_id → roles.id`, not null; usuários pré-existentes (se houver) recebem o papel "cliente" antes
      de a coluna virar not null
  - **Feature tests:** `UsersRoleIdBackfilledTest`
  - **Traces:** users, roles

### Phase 11.3: Transação

- [ ] **Task:** Migration `orders` (sem a FK de `renews_certificate_id` ainda)
  - **Acceptance criteria:**
    - `renews_certificate_id` criada como `unsignedBigInteger nullable` **sem** constraint FK nesta
      migration; índices em `status_id` e composto `(user_id, status_id)`
  - **Traces:** orders

- [ ] **Task:** Migrations `certificate_holders`, `payments`, `invoices`
  - **Acceptance criteria:**
    - `certificate_holders.order_id` unique; `payments.amount decimal(10,2)`; `invoices.issued_at` **not
      null** (linha só existe após emissão real da nota fiscal, nunca criada preemptivamente)
  - **Traces:** certificate_holders, payments, invoices

### Phase 11.4: Ciclo de vida do certificado

- [ ] **Task:** Migration `certificates` + migration posterior adicionando a FK de `orders.renews_certificate_id`
  - **Acceptance criteria:**
    - `certificates` criada com `order_id` unique, `status_id`, `validation_deadline_at`, `approved_at`,
      `issued_at`, `expires_at`, `revoked_at`
    - Migration **seguinte** adiciona a constraint FK `orders.renews_certificate_id → certificates.id` —
      resolve a referência circular documentada em `database-schema.md`
  - **Feature tests:** `MigrateFreshRunsWithoutForeignKeyErrorTest`
  - **Traces:** certificates, orders

- [ ] **Task:** Migrations `appointments`, `revocation_requests`, `withdrawal_requests`, `status_histories`
  - **Acceptance criteria:**
    - `appointments`: índices `certificate_id`/`status_id`, `scheduling_token` unique
    - `revocation_requests`/`withdrawal_requests`: `processed_by_user_id` nullable FK `users.id`;
      `withdrawal_requests` **sem** coluna `order_id` própria
    - `status_histories`: colunas polimórficas `auditable_type`/`auditable_id`, índice composto
  - **Traces:** appointments, revocation_requests, withdrawal_requests, status_histories

### Phase 11.5: Conteúdo gerenciável

- [ ] **Task:** Migrations `faq_questions`, `use_cases`, `support_issues`
  - **Acceptance criteria:**
    - `faq_questions.anchor` e `support_issues.anchor` unique; `use_cases.recommended_certificate` é varchar
      livre (não FK)
  - **Traces:** faq_questions, use_cases, support_issues

---

## Phase 12: Models e Relacionamentos Eloquent

**Goal:** criar todos os models com relacionamentos, casts e fillables completos, sem deixar relações para
fases futuras. · **Depends on:** Phase 11 · **Covers:** as 25 tabelas do schema

### Phase 12.1: Models de catálogo e lookup

- [ ] **Task:** Models de lookup (`Role`, `CertificateType`, `CertificateFormat`, `PaymentMethod`,
      `OrderStatus`, `PaymentStatus`, `CertificateStatus`, `AppointmentStatus`, `RevocationRequestStatus`,
      `WithdrawalRequestStatus`, `FaqCategory`)
  - **Acceptance criteria:**
    - Cada model tem `$fillable` correto e relacionamento inverso (`hasMany`) para as tabelas que o
      referenciam
  - **Traces:** roles, certificate_types, certificate_formats, payment_methods, order_statuses,
    payment_statuses, certificate_statuses, appointment_statuses, revocation_request_statuses,
    withdrawal_request_statuses, faq_categories

- [ ] **Task:** Model `CertificateSku` (`belongsTo` type/format, `hasMany` orders, cast decimal, scope `active()`)
  - **Acceptance criteria:**
    - `belongsTo(CertificateType::class)`, `belongsTo(CertificateFormat::class)`, `hasMany(Order::class)`;
      `price`/`price_pix` com cast `decimal:2`; scope `active()` filtra `is_active = true`
  - **Traces:** certificate_skus

- [ ] **Task:** Estender `User` (`belongsTo(Role::class)`, `hasMany` orders/revocationRequests/withdrawalRequests)
  - **Acceptance criteria:**
    - `User::role()` `belongsTo`; `User::orders()` `hasMany`; `User::processedRevocationRequests()` e
      `User::processedWithdrawalRequests()` `hasMany` via `processed_by_user_id`
  - **Traces:** users, roles

### Phase 12.2: Models de transação e ciclo de vida

- [ ] **Task:** Models `Order`, `CertificateHolder`, `Payment`, `Invoice`
  - **Acceptance criteria:**
    - `Order::renewsCertificate()` (`belongsTo` Certificate), `Order::certificateHolder()` (`hasOne`),
      `Order::payments()` (`hasMany`), `Order::invoice()` (`hasOne`)
  - **Traces:** orders, certificate_holders, payments, invoices

- [ ] **Task:** Models `Certificate`, `Appointment`, `RevocationRequest`, `WithdrawalRequest`, `StatusHistory`
  - **Acceptance criteria:**
    - `Certificate::appointments()` `hasMany` (ordenável por `created_at desc` para achar o vigente);
      `StatusHistory` usa `morphTo('auditable')`
  - **Traces:** certificates, appointments, revocation_requests, withdrawal_requests, status_histories

### Phase 12.3: Models de conteúdo

- [ ] **Task:** Models `FaqQuestion`, `UseCase`, `SupportIssue`
  - **Acceptance criteria:**
    - `FaqQuestion::category()` `belongsTo`; scopes `active()`/`ordered()` nos três models
  - **Traces:** faq_questions, use_cases, support_issues

---

## Phase 13: Papéis de Usuário e Extensão de Autenticação

**Goal:** estender a autenticação Fortify já existente com o papel cliente/administrador. · **Depends on:**
Phase 12 · **Covers:** Painel administrativo (key concept)

- [x] **Task:** Login, registro, verificação de e-mail, redefinição de senha e 2FA (Laravel Fortify)
  - **Acceptance criteria:**
    - `vendor/bin/sail artisan test --compact tests/Feature/Auth` passa
  - **Traces:** users

- [x] **Task:** Perfil, aparência, segurança e exclusão de conta (Livewire, `resources/views/pages/settings/`)
  - **Acceptance criteria:**
    - `vendor/bin/sail artisan test --compact tests/Feature/Settings` passa
  - **Traces:** users

- [ ] **Task:** Atribuir papel "cliente" por padrão no registro
  - **Acceptance criteria:**
    - Action `CreateNewUser` (Fortify) atribui `role_id` do papel "cliente" a todo novo cadastro
  - **Feature tests:** `UserRegistrationAssignsCustomerRoleTest` — registro via Fortify cria usuário com
    `role_id` apontando para "cliente"
  - **Traces:** roles, users

- [ ] **Task:** Gate de autorização para o papel "administrador"
  - **Acceptance criteria:**
    - Gate `administrador` retorna true apenas se `role.slug === 'administrador'`
  - **Feature tests:** `AdministratorGateDeniesCustomerTest` — cliente autenticado recebe 403 numa rota
    protegida pelo gate; `AdministratorGateAllowsAdminTest` — usuário administrador acessa normalmente
  - **Traces:** roles, users

---

## Phase 14: Catálogo Dinâmico

**Goal:** substituir o conteúdo estático de preço/SKU das páginas de produto (Phases 3–7) por dados reais de
`certificate_skus`. · **Depends on:** Phase 12 · **Covers:** US-2.1

- [ ] **Task:** Páginas e-CNPJ/e-CPF/MEI consultam `certificate_skus` por slug em vez do placeholder `[PREÇO]`
  - **Acceptance criteria:**
    - Controller/ViewModel busca o SKU ativo pelo slug de tipo (e-cpf/e-cnpj) e injeta preço/nome reais na view
  - **Feature tests:** `ProductPageShowsSkuPriceTest` — página e-CNPJ exibe `price`/`price_pix` do SKU
    correspondente
  - **Traces:** US-2.1, certificate_skus

- [ ] **Task:** Home e Hub exibem "a partir de" com o menor preço real por tipo
  - **Acceptance criteria:**
    - "A partir de R$ [PREÇO]" usa `MIN(price)` entre os SKUs ativos do mesmo `certificate_type`
  - **Feature tests:** `HomeShowsLowestPriceTest`
  - **Traces:** US-1.1, certificate_skus

- [ ] **Task:** `x-purchase-panel` recebe os SKUs reais via prop (preço não é mais hardcoded no Alpine)
  - **Acceptance criteria:**
    - Componente recebe os dois SKUs (A1/A3) do tipo da página via prop; o Alpine troca entre os valores
      recebidos, sem preço fixo no template
  - **Traces:** US-2.1, certificate_skus

- [ ] **Task:** Bloco "Todos os certificados" do Hub lista os 4 SKUs ativos do banco
  - **Acceptance criteria:**
    - Tabela consulta `certificate_skus::active()` em vez das 4 linhas estáticas
  - **Feature tests:** `HubListsActiveSkusOnlyTest` — SKU com `is_active=false` não aparece
  - **Traces:** US-1.3, certificate_skus

---

## Phase 15: Checkout e Cadastro

**Goal:** cadastro obrigatório no checkout (não precisa ser o titular) e criação de pedido + titular. ·
**Depends on:** Phase 12, Phase 14 · **Covers:** US-2.3

- [ ] **Task:** Form Request de cadastro no checkout (dados de quem paga)
  - **Acceptance criteria:**
    - Cadastro obrigatório para prosseguir; reaproveita conta já autenticada se o comprador já tiver login
  - **Feature tests:** `CheckoutRequiresRegistrationTest` — checkout sem dados de cadastro é bloqueado
  - **Traces:** US-2.3, orders

- [ ] **Task:** Form Request de dados do titular (`certificate_holders`)
  - **Acceptance criteria:**
    - Campos condicionais conforme `certificate_type` do SKU escolhido (CPF/nascimento para e-CPF; CNPJ/razão
      social/representante para e-CNPJ)
  - **Feature tests:** `CertificateHolderValidationTest` — CNPJ obrigatório para SKU e-CNPJ, CPF para e-CPF
  - **Traces:** US-2.3, certificate_holders

- [ ] **Task:** Action que cria `Order` (status `aguardando_pagamento`) + `CertificateHolder` numa transação
  - **Acceptance criteria:**
    - `Order` e `CertificateHolder` são criados dentro da mesma transação de banco
  - **Feature tests:** `CreateOrderCreatesHolderTest` — pedido e titular criados juntos; rollback se um dos
    dois falhar
  - **Traces:** US-2.3, orders, certificate_holders

- [ ] **Task:** Regra "titular pode ser diferente de quem paga"
  - **Acceptance criteria:**
    - Nenhum texto do checkout afirma que os dados devem ser do titular
  - **Feature tests:** `CheckoutAllowsDifferentHolderTest` — pedido aceito com e-mail do comprador diferente
    do e-mail do titular
  - **Traces:** US-2.3, certificate_holders

- [ ] **Task:** Tela de checkout (sem design ref formal — segue identidade visual da Phase 1)
  - **Acceptance criteria:**
    - Reutiliza `x-layout`; formulário em 2 passos (cadastro → titular) com componentes Flux
  - **Design ref:** nenhum — construir com a paleta/tipografia/componentes da Phase 1
  - **Traces:** US-2.3, orders

---

## Phase 16: Pagamento e Nota Fiscal

**Goal:** abstração de gateway com Pix como prioridade, e emissão de nota fiscal após confirmação. ·
**Depends on:** Phase 15 · **Covers:** US-2.4, US-2.5

- [ ] **Task:** Contrato `PaymentGatewayContract` + driver "manual/pendente" até o gateway ser decidido
  - **Acceptance criteria:**
    - Nenhuma integração real de gateway nesta fase (decisão em aberto); driver manual permite confirmar
      pagamento via Artisan command em ambiente de teste
  - **Feature tests:** `PaymentGatewayManualDriverConfirmsPaymentTest`
  - **Traces:** US-2.4, payments, payment_methods, payment_statuses

- [ ] **Task:** Pix como único `payment_method` ativo no checkout
  - **Acceptance criteria:**
    - Apenas o `payment_method` com `is_active = true` (Pix) aparece como opção no checkout
  - **Feature tests:** `CheckoutOffersPixOnlyTest`
  - **Traces:** US-2.4, payment_methods

- [ ] **Task:** Confirmação de pagamento atualiza `orders` e registra `status_histories`
  - **Acceptance criteria:**
    - `orders.status_id` → pago, `orders.paid_at` preenchido
  - **Feature tests:** `PaymentConfirmationUpdatesOrderStatusTest`; `PaymentConfirmationLogsStatusHistoryTest`
  - **Traces:** US-2.4, orders, status_histories

- [ ] **Task:** Geração de nota fiscal ao confirmar pagamento
  - **Acceptance criteria:**
    - Linha em `invoices` só é criada neste momento (nunca antes), `invoice_number` sequencial e `issued_at`
      preenchido junto
  - **Feature tests:** `InvoiceCreatedOnlyAfterPaymentTest`; `InvoiceNumberIsSequentialTest`
  - **Traces:** US-2.5, invoices

---

## Phase 17: Emissão do Certificado e Agendamento

**Goal:** orquestrar e comunicar o ciclo pagamento → agendamento → validação (externa) → emissão. ·
**Depends on:** Phase 16 · **Covers:** US-3.2, US-3.3, US-3.4

- [ ] **Task:** Criar `certificates` ao confirmar pagamento, com prazo de validação de 180 dias
  - **Acceptance criteria:**
    - Status inicial `aguardando_validacao`; `validation_deadline_at` = `paid_at` + 180 dias
  - **Feature tests:** `CertificateCreatedOnPaymentConfirmationTest`; `ValidationDeadlineIs180DaysTest`
  - **Traces:** US-3.3, certificates

- [ ] **Task:** Disparo assíncrono do e-mail de agendamento
  - **Acceptance criteria:**
    - `appointments.scheduling_token` único gerado; e-mail enviado via job em fila; provedor de e-mail ainda
      não decidido — usar driver `log`/`array` até a decisão
  - **Feature tests:** `SchedulingEmailQueuedOnPaymentConfirmationTest`
  - **Traces:** US-3.2, appointments

- [ ] **Task:** Endpoint de agendamento via `scheduling_token`
  - **Acceptance criteria:**
    - Reagendamento cria uma **nova** linha em `appointments` (histórico), nunca sobrescreve a anterior
  - **Feature tests:** `ReschedulingCreatesNewAppointmentRowTest`
  - **Traces:** US-3.3, appointments

- [ ] **Task:** Registro do resultado da validação (vindo da plataforma externa da certificadora)
  - **Acceptance criteria:**
    - Aprovado → `certificates.status_id` = aprovado, `approved_at` preenchido, `status_histories`
      registrado; reprovado → `appointments.status_id` = reprovado, cliente pode reagendar sem custo
  - **Feature tests:** `ValidationApprovalUpdatesCertificateStatusTest`;
    `ValidationRejectionAllowsRescheduleTest`
  - **Traces:** US-3.3, certificates, appointments, status_histories

- [ ] **Task:** Liberação para download/instalação
  - **Acceptance criteria:**
    - `certificates.status_id` → emitido; `issued_at` preenchido; `expires_at` = `issued_at` +
      `certificate_skus.validity_months`
  - **Feature tests:** `CertificateIssuanceSetsExpirationTest`
  - **Traces:** US-3.4, certificates

---

## Phase 18: Execução da Renovação

**Goal:** permitir que um pedido de renovação referencie o certificado anterior e troque de formato. ·
**Depends on:** Phase 15, Phase 17 · **Covers:** US-4.2

- [ ] **Task:** Fluxo de renovação preenche `orders.renews_certificate_id` com o certificado escolhido
  - **Acceptance criteria:**
    - Botões "Renovar meu certificado" levam ao mesmo checkout de compra (Phase 15), com o certificado a
      renovar pré-selecionado
  - **Feature tests:** `RenewalOrderLinksPreviousCertificateTest`
  - **Traces:** US-4.2, orders

- [ ] **Task:** Renovação permite escolher formato diferente do certificado anterior (A1↔A3)
  - **Acceptance criteria:**
    - Nenhuma restrição de `certificate_sku_id` baseada no formato do certificado anterior
  - **Feature tests:** `RenewalAllowsFormatChangeTest`
  - **Traces:** US-4.2, certificate_skus

- [ ] **Task:** Renovação sempre exige nova validação completa (reaproveita a Phase 17 integralmente)
  - **Acceptance criteria:**
    - Nenhum campo de `certificate_holders` é pré-preenchido a partir do certificado anterior
  - **Feature tests:** `RenewalRequiresFullValidationTest`
  - **Traces:** US-4.2, certificate_holders

---

## Phase 19: Revogação e Arrependimento

**Goal:** fluxos de solicitação de revogação (irreversível) e de direito de arrependimento (7 dias). ·
**Depends on:** Phase 17 · **Covers:** US-5.5, US-5.6

- [ ] **Task:** Endpoint autenticado para solicitar revogação
  - **Acceptance criteria:**
    - Apenas titular, responsável legal (PJ) ou comprador original podem solicitar
  - **Feature tests:** `CertificateOwnerCanRequestRevocationTest`
  - **Traces:** US-5.6, revocation_requests

- [ ] **Task:** Processamento administrativo da revogação (irreversível)
  - **Acceptance criteria:**
    - `certificates.status_id` → revogado, `revoked_at` preenchido; `revocation_requests.processed_by_user_id`
      e `status_id` → concluída; `status_histories` registrado
  - **Feature tests:** `RevocationIsIrreversibleTest` — certificado revogado não pode voltar a outro status
  - **Traces:** US-5.6, certificates, revocation_requests, status_histories

- [ ] **Task:** Endpoint de solicitação de arrependimento, validando a janela de 7 dias
  - **Acceptance criteria:**
    - Janela contada a partir de `certificates.approved_at`
  - **Feature tests:** `WithdrawalRequestWithin7DaysAllowedTest`; `WithdrawalRequestAfter7DaysRejectedTest`
  - **Traces:** US-5.5, withdrawal_requests, certificates

- [ ] **Task:** Reembolso de arrependimento exige revogação concluída
  - **Acceptance criteria:**
    - `withdrawal_requests.refunded_at` só é preenchido depois de `revocation_requests` concluída para o
      mesmo certificado
  - **Feature tests:** `WithdrawalRefundRequiresRevocationTest`
  - **Traces:** US-5.5, withdrawal_requests, revocation_requests

---

## Phase 20: Painel Administrativo — Gestão de Conteúdo

**Goal:** CRUD para os três blocos de conteúdo editável (FAQ, casos de uso, problemas de suporte) e
substituição do conteúdo estático das páginas por dados dinâmicos. · **Depends on:** Phase 12, Phase 13 ·
**Covers:** US-7.1, US-7.2, US-7.3

- [ ] **Task:** Painel Livewire protegido pelo gate `administrador`
  - **Acceptance criteria:**
    - Rota do painel inacessível a clientes (403)
  - **Feature tests:** `AdminPanelBlocksNonAdminTest`
  - **Traces:** US-7.1, roles

- [ ] **Task:** CRUD de `faq_questions` (categoria, pergunta, resposta, âncora, posição, ativo/inativo)
  - **Acceptance criteria:**
    - Alteração publicada reflete imediatamente nas páginas que exibem aquela pergunta, sem deploy
  - **Feature tests:** `AdminCanEditFaqQuestionTest`; `FaqEditReflectsOnPublicPageTest`
  - **Traces:** US-7.1, faq_questions

- [ ] **Task:** CRUD de `use_cases` (situação, recomendação em texto livre, posição, ativo/inativo)
  - **Acceptance criteria:**
    - Alteração publicada reflete imediatamente no bloco "Ainda em dúvida?" do Hub, sem deploy
  - **Feature tests:** `AdminCanEditUseCaseTest`
  - **Traces:** US-7.2, use_cases

- [ ] **Task:** CRUD de `support_issues` (situação, resposta, âncora, posição, ativo/inativo)
  - **Acceptance criteria:**
    - Alteração publicada reflete imediatamente na tabela de problemas de uso da página Suporte, sem deploy
  - **Feature tests:** `AdminCanEditSupportIssueTest`
  - **Traces:** US-7.3, support_issues

- [ ] **Task:** Páginas públicas passam a renderizar FAQ/casos de uso/problemas a partir do banco
  - **Acceptance criteria:**
    - A distribuição de categoria por página continua sendo regra fixa da aplicação (não editável pelo
      painel), conforme decisão registrada em `database-schema.md`
  - **Feature tests:** `PublicPagesRenderFaqFromDatabaseTest`
  - **Traces:** US-5.1, US-1.4, US-5.4, faq_questions, use_cases, support_issues

## Open Questions

- **Telas sem design formal** (checkout, minha conta, agendamento, painel administrativo — Phases 15, 17, 20):
  construídas seguindo a identidade visual da Phase 1 (paleta, tipografia, componentes), sem mockup dedicado —
  decisão confirmada nesta sessão. Um mockup formal, se vier depois, deve ser aplicado num re-run deste
  comando.
- **Gateway de pagamento e provedor de e-mail:** ainda não decididos (ver `project-description.md`). Phases 16
  e 17 já preveem abstrações (`PaymentGatewayContract`, driver de e-mail `log`/`array`) para não bloquear o
  desenvolvimento, mas a integração real fica pendente da escolha do fornecedor.
- **Ferramenta do painel administrativo** (Filament vs. Livewire próprio): a Phase 20 assume Livewire próprio,
  conforme a inclinação já registrada em `database-schema.md`/`user-stories.md`; decisão final ainda pendente.
- **Duração do A3 (1, 2 ou 3 anos):** Phase 11/14 assumem valor fixo por SKU (`certificate_skus.validity_months`,
  decisão já registrada em `database-schema.md`). Se o negócio decidir permitir escolha de duração com preço
  variável, as Phases 14/16 precisam ser revisadas.
- **Phases 11–20 estão em nível "enxuto"** por decisão do desenvolvedor nesta sessão (MVP com detalhe total,
  backend com esqueleto). Recomenda-se rodar `init:project-phases` novamente para aprofundar critérios de
  aceite e testes dessas phases quando o time decidir iniciar essa etapa.
