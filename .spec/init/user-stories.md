# Digital Lock E-commerce — User Stories

<!-- inputs: project-description.md@sha256:cb6009f4181e -->

## Overview

O **Digital Lock E-commerce** vende certificados digitais (e-CPF e e-CNPJ, formatos A1 e A3) com emissão 100%
online por videoconferência. Este backlog cobre a jornada completa descrita na descrição do projeto — desde a
decisão de compra até o pós-venda — mesmo que a primeira fase de construção seja apenas o frontend estático das
9 páginas comerciais e de apoio. Stories que dependem de backend real (checkout funcional, pagamento, e-mail,
painel administrativo) estão priorizadas como Medium/Low e ficam para fases seguintes; stories de conteúdo e
apresentação das 9 páginas são High, por serem o que está sendo construído agora.

**User Types:**
- **Visitante Indeciso** - ainda não sabe se precisa de e-CPF, e-CNPJ, nem qual variação (A1/A3); usa a Home e o
  Hub de certificados para decidir.
- **Cliente Pessoa Física** - compra e-CPF para assinar documentos, declarar Imposto de Renda ou acessar
  serviços do governo.
- **Cliente Pessoa Jurídica** - compra e-CNPJ para a empresa emitir nota fiscal e assinar em nome do CNPJ.
- **MEI** - microempreendedor individual que compra o mesmo SKU do e-CNPJ, mas parte de uma dúvida específica
  (certificado digital x CCMEI).
- **Cliente Pós-venda** - já comprou e busca instalação, resolução de problemas de uso ou revogação.
- **Administrador de Conteúdo** - equipe interna que mantém o banco de FAQ, os casos de uso do Hub e a tabela
  de problemas da página de Suporte, sem depender de deploy (fase futura, ferramenta ainda não decidida).
- **Digital Lock (negócio/operação)** - a própria empresa, cujos requisitos de consistência de marca,
  nomenclatura e não duplicação de conteúdo evitam retrabalho de atendimento e reclamação.

---

## 1. Descoberta e Decisão de Compra

### US-1.1: Escolher perfil na Home
**As a** Visitante Indeciso
**I want to** ver logo na Home três opções claras (e-CPF, e-CNPJ, Sou MEI), cada uma com preço a partir de e
botão próprio
**So that** eu chegue rápido na página de produto certa para o meu perfil

**Acceptance Criteria:**
- [ ] Bloco 2 da Home exibe 3 cartões: e-CPF, e-CNPJ, Sou MEI, cada um com texto curto, preço "A partir de
      [PREÇO]" e botão de destino (Ver e-CPF / Ver e-CNPJ / Ver opções para MEI)
- [ ] Preço e destino dos 3 cartões vêm renderizados no HTML, sem chamada assíncrona
- [ ] Abaixo dos cartões, link "Não sabe qual escolher? Compare os tipos de certificado." leva a
      `/certificado-digital/`
- [ ] No mobile, a ordem vertical dos blocos da Home é: 1, 2, 4, 3, 5, 6, 7, 8, 9

**Expected Result:** o visitante identifica seu perfil e chega à página de produto certa em um clique a partir
da Home.

---

### US-1.2: Comparar e-CPF vs. e-CNPJ no Hub
**As a** Visitante Indeciso
**I want to** ver lado a lado o que representa o e-CPF e o que representa o e-CNPJ
**So that** eu saiba por qual dos dois começar

**Acceptance Criteria:**
- [ ] Bloco 2 do Hub (`/certificado-digital/`) exibe 2 cartões — e-CPF (pessoa física) e e-CNPJ (empresa) —
      cada um com preço "a partir de [PREÇO]" e botão de destino
- [ ] Abaixo dos cartões, texto informa que quem tem empresa e também precisa assinar como pessoa física pode
      ter os dois certificados, por serem independentes

**Expected Result:** o visitante decide entre e-CPF e e-CNPJ antes de ver a diferença A1/A3.

---

### US-1.3: Comparar A1 vs. A3 no Hub
**As a** Visitante Indeciso
**I want to** ver uma tabela comparativa completa entre A1 e A3
**So that** eu escolha a variação certa sem depender de atendimento humano

**Acceptance Criteria:**
- [ ] Tabela comparativa cobre pelo menos: onde fica, validade, exige equipamento, uso em mais de um
      computador, se pode enviar ao contador, o que acontece se o computador quebrar, o que acontece se o
      token for perdido, sistemas operacionais suportados, compatibilidade com software de nota fiscal e
      "melhor para"
- [ ] Linha de fechamento reforça que o A1 atende a maioria dos casos e não exige comprar equipamento
- [ ] Bloco 4 do Hub lista os 4 SKUs completos (e-CPF A1, e-CPF A3, e-CNPJ A1, e-CNPJ A3) com preço e botão
      próprio por linha

**Expected Result:** o visitante entende a diferença técnica entre A1 e A3 e sabe qual comprar.

---

### US-1.4: Ver casos de uso por situação
**As a** Visitante Indeciso
**I want to** encontrar minha situação específica (ex.: "sou MEI e preciso emitir nota fiscal", "quero declarar
o Imposto de Renda") numa lista e ver direto qual certificado ela exige
**So that** eu decida sem precisar entender a terminologia técnica da norma

**Acceptance Criteria:**
- [ ] Bloco 5 do Hub lista pelo menos 10 situações comuns, cada uma apontando o certificado recomendado
      (ex.: e-CNPJ A1, e-CPF A1 ou A3)
- [ ] O conteúdo desta lista é estático nesta fase; crescer sem depender de programador fica registrado como
      requisito para a fase do painel administrativo (Feature Area 7)

**Expected Result:** o visitante bate sua situação real com a recomendação de certificado sem ambiguidade.

---

## 2. Catálogo e Compra

### US-2.1: Ver detalhes e escolher variante A1/A3 na página de produto
**As a** Cliente Pessoa Física ou Cliente Pessoa Jurídica
**I want to** ver a página do certificado (e-CPF ou e-CNPJ) com preço, seletor de variante A1/A3 e a lista do
que dá para fazer com o certificado
**So that** eu confirme minha escolha antes de comprar

**Acceptance Criteria:**
- [ ] Dobra inicial mostra preço (`[PREÇO]` ou `[PREÇO PIX]` no Pix), seletor A1/A3, botão "Comprar agora" e os
      selos (Padrão ICP-Brasil, Emissão por videoconferência, Sem taxa extra)
- [ ] Trocar entre A1 e A3 no seletor atualiza a variação exibida na mesma URL, sem chamada ao servidor
      (implementado com Alpine.js, não com o componente Tabs do Flux Pro)
- [ ] Bloco "para que serve" lista os usos do certificado (ex.: e-CNPJ: emitir NF-e/NFS-e/NFC-e, e-CAC,
      eSocial; e-CPF: assinar contratos, declarar IR, e-CAC, gov.br nível ouro)
- [ ] Bloco de documentos necessários para a videoconferência aparece de forma diferenciada por tipo de
      titular (empresa vs. pessoa física)

**Expected Result:** o cliente entende o que está comprando e em que variação, antes de avançar para o
checkout.

---

### US-2.2: Entender a diferença entre certificado digital e CCMEI
**As a** MEI
**I want to** entender por que o certificado digital não é o mesmo documento que o CCMEI
**So that** eu não confunda os dois nem deixe de comprar o que realmente preciso

**Acceptance Criteria:**
- [ ] Página `/certificado-digital-para-mei/` explica que o CCMEI comprova a condição de MEI e é gratuito no
      Portal do Empreendedor, enquanto o certificado digital é a identidade eletrônica usada para emitir nota
      fiscal e assinar em nome do CNPJ
- [ ] Página afirma que o MEI compra o mesmo SKU do e-CNPJ, sem produto nem preço exclusivo de MEI
- [ ] Botão de compra leva ao mesmo produto de e-CNPJ com a variante A1 pré-selecionada
- [ ] Conteúdo desta página (blocos "confusão que trava a compra" e "quem precisa") não é copiado para a
      página de e-CNPJ

**Expected Result:** o MEI entende a diferença e conclui a compra do e-CNPJ, com A1 já selecionado.

---

### US-2.3: Cadastrar-se no checkout
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** me cadastrar no checkout com meus próprios dados, mesmo quando não sou o titular do certificado
**So that** eu consiga concluir a compra para mim ou para outra pessoa/empresa

**Acceptance Criteria:**
- [ ] Cadastro é obrigatório para concluir a compra
- [ ] Nenhuma tela ou texto afirma que a compra dispensa cadastro
- [ ] Os dados do cadastro não precisam ser os do titular do certificado (ex.: contador comprando para o
      cliente)
- [ ] Dados do cadastro servem para emissão da nota fiscal e acompanhamento do pedido

**Expected Result:** qualquer pessoa consegue comprar em nome de outra, com o pedido rastreável pelos dados de
quem pagou.

---

### US-2.4: Pagar com Pix como forma prioritária
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** pagar via Pix e ter a confirmação imediata
**So that** eu já possa agendar minha videoconferência na sequência, sem esperar

**Acceptance Criteria:**
- [ ] Pix aparece como primeira opção no checkout, sinalizado como o mais rápido, com confirmação imediata
- [ ] Se boleto for adicionado futuramente, toda menção a confirmação imediata é ressalvada, informando
      compensação de 1 a 3 dias úteis e que o agendamento só libera depois disso
- [ ] Gateway de pagamento definitivo é uma decisão em aberto (ver Open Questions da descrição do projeto)

**Expected Result:** o pagamento via Pix confirma na hora e libera o próximo passo do fluxo (agendamento).

---

### US-2.5: Receber nota fiscal da compra
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** receber a nota fiscal referente à minha compra
**So that** eu tenha o comprovante fiscal da transação

**Acceptance Criteria:**
- [ ] Nota fiscal é emitida para toda compra concluída, independentemente da forma de pagamento
- [ ] Nota fiscal usa os dados de cadastro informados no checkout

**Expected Result:** todo pedido pago gera uma nota fiscal correspondente.

---

## 3. Emissão do Certificado

### US-3.1: Ver o passo a passo de emissão
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** ver, em qualquer página comercial, os 4 passos entre pagar e usar o certificado
**So that** eu saiba o que esperar antes de comprar

**Acceptance Criteria:**
- [ ] Os 4 passos aparecem de forma idêntica em todas as páginas onde surgem: (1) Escolha e pague — no Pix a
      confirmação é imediata; (2) Receba as instruções por e-mail e agende sua videoconferência; (3) Faça a
      validação — o atendente confere os documentos ao vivo, leva poucos minutos; (4) Baixe e instale — o
      certificado é liberado logo após a aprovação
- [ ] Implementado como um único Blade component (`x-passo-a-passo`), nunca reescrito manualmente em mais de
      uma página

**Expected Result:** o cliente entende o processo completo antes de decidir comprar, com o mesmo texto em
qualquer página onde aparece.

---

### US-3.2: Receber e-mail de agendamento após pagamento confirmado
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** receber um e-mail com o link de agendamento assim que meu pagamento for confirmado
**So that** eu escolha o dia e o horário da minha videoconferência de validação

**Acceptance Criteria:**
- [ ] E-mail é disparado automaticamente na confirmação do pagamento
- [ ] E-mail contém link de agendamento onde o cliente escolhe dia e horário
- [ ] Provedor de e-mail é uma decisão em aberto (ver Open Questions da descrição do projeto)

**Expected Result:** todo pagamento confirmado gera o disparo do e-mail de agendamento.

---

### US-3.3: Agendar e realizar a validação por videoconferência
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** agendar e realizar a validação de identidade por videoconferência, no dia e horário que eu
escolher
**So that** eu conclua a emissão do meu certificado sem me deslocar

**Acceptance Criteria:**
- [ ] Elegibilidade exige pelo menos uma das condições: certificado digital emitido a partir de 2018 com
      biometria facial e digital em qualquer AR, ou CNH emitida/renovada a partir de 2017
- [ ] Cliente tem 180 dias, contados da compra, para concluir a validação e a emissão
- [ ] A validação em si ocorre em plataforma própria da certificadora, fora do escopo de construção deste site
- [ ] Se a validação for reprovada, o cliente corrige o apontado e reagenda sem custo adicional

**Expected Result:** o cliente elegível conclui a validação remotamente, dentro do prazo de 180 dias.

---

### US-3.4: Baixar e instalar o certificado após aprovação
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica ou MEI
**I want to** baixar e instalar meu certificado assim que aprovado
**So that** eu comece a usá-lo (assinar documentos, emitir nota fiscal, acessar serviços do governo)

**Acceptance Criteria:**
- [ ] No A1: cliente recebe senha de emissão (uso único) e depois define senha de instalação (usada em cada
      computador); A1 não tem PIN
- [ ] No A3: certificado é gravado no token/cartão; cliente define PIN (uso diário) e recebe PUK (desbloqueio)
- [ ] Nenhuma senha pode ser recuperada pela Digital Lock ou pela certificadora em caso de perda
- [ ] Suporte na instalação está incluído, sem custo adicional

**Expected Result:** o cliente aprovado consegue instalar e começar a usar o certificado, com suporte
disponível se travar.

---

## 4. Renovação

### US-4.1: Ver a página de Renovação
**As a** Cliente Pessoa Física ou Cliente Pessoa Jurídica
**I want to** ver que posso renovar meu certificado mesmo tendo emitido em outra certificadora
**So that** eu não deixe de renovar por achar que preciso voltar à empresa original

**Acceptance Criteria:**
- [ ] Página `/renovacao-certificado-digital/` afirma que não é preciso ser na mesma certificadora e que não
      existe transferência/migração — renovação é sempre uma nova emissão
- [ ] Página informa que não há prazo mínimo de antecedência: dá para renovar antes ou depois do vencimento
- [ ] Página informa que certificado vencido não é reativado nem renovado tecnicamente — é emitido um novo
- [ ] Botões levam ao mesmo checkout de compra do certificado (e-CPF ou e-CNPJ), sem produto separado de
      renovação

**Expected Result:** o cliente com certificado vencendo ou vencido entende que pode renovar com qualquer
Autoridade de Registro e sem prazo mínimo.

---

### US-4.2: Renovar trocando de formato (A1 ↔ A3)
**As a** Cliente Pessoa Física ou Cliente Pessoa Jurídica
**I want to** renovar meu certificado num formato diferente do que eu tinha antes
**So that** eu adapte o certificado à minha necessidade atual (mais praticidade ou mais validade)

**Acceptance Criteria:**
- [ ] Cliente não é obrigado a renovar no mesmo formato do certificado anterior
- [ ] Toda a documentação é reapresentada na renovação, tanto para pessoa física quanto jurídica — não existe
      processo simplificado por já ter sido cliente
- [ ] Link "Ver comparativo entre A1 e A3" leva para `/certificado-digital/`

**Expected Result:** o cliente conclui a renovação no formato que escolher, com nova validação completa.

---

## 5. Autoatendimento, FAQ e Suporte Pós-venda

### US-5.1: Consultar FAQ por categoria em cada página comercial
**As a** Cliente Pessoa Física, Cliente Pessoa Jurídica, MEI ou Visitante Indeciso
**I want to** ver, em cada página comercial, só as perguntas frequentes relevantes para aquele contexto
**So that** eu resolva minha dúvida sem sair da página nem ler perguntas que não se aplicam a mim

**Acceptance Criteria:**
- [ ] Home exibe seleção curta das categorias 1 (Antes de comprar) e 3 (Videoconferência e validação)
- [ ] Hub exibe a categoria 1
- [ ] e-CNPJ e e-CPF exibem as categorias 1, 3 e 5 (Uso do certificado), com prioridade para o contexto
      empresarial ou de pessoa física, respectivamente
- [ ] MEI exibe a categoria 7, mais itens selecionados de 1 e 3
- [ ] Renovação exibe a categoria 6
- [ ] Suporte exibe as categorias 4 (Emissão e instalação), 8 (Revogação/garantia/devolução) e 9 (Suporte e
      atendimento)
- [ ] Cada pergunta tem âncora própria (URL com fragmento) para linkagem direta
- [ ] Accordion abre e fecha via Alpine.js puro (`x-data`, `x-show`), sem o componente Accordion do Flux Pro

**Expected Result:** cada página mostra só o FAQ do seu contexto, com o mesmo texto de resposta em todas as
páginas onde a pergunta se repete.

---

### US-5.2: Consultar o banco completo de FAQ na página Como Emitir
**As a** Cliente Pós-venda ou Cliente Pessoa Física/Jurídica/MEI
**I want to** encontrar todas as perguntas frequentes num único lugar, organizadas por categoria
**So that** eu resolva qualquer dúvida sobre o processo, do início ao fim

**Acceptance Criteria:**
- [ ] `/como-emitir-certificado-digital/` hospeda as 9 categorias completas do banco de FAQ
- [ ] Página também expõe, em blocos próprios com âncora, os 6 passos detalhados do processo formal (quem pode
      emitir, documentos, agendamento, preparação técnica, validação, emissão/instalação)
- [ ] Bloco de documentos e cada pergunta têm âncora própria, para o atendimento enviar o link direto do
      trecho

**Expected Result:** a página "Como Emitir" funciona como central de dúvidas, usada tanto por clientes quanto
pelo atendimento para linkar trechos específicos.

---

### US-5.3: Usar a triagem por âncora na página Suporte
**As a** Cliente Pós-venda
**I want to** indicar em que ponto do processo eu estou e ir direto ao bloco relevante
**So that** eu não precise ler a página inteira para achar minha resposta

**Acceptance Criteria:**
- [ ] Bloco de triagem exibe 4 cartões: "Comprei e não recebi nada", "Vou fazer a videoconferência", "Preciso
      instalar o certificado", "Já uso e deu problema"
- [ ] Cada cartão leva, por âncora, ao bloco correspondente na mesma página, sem recarregar
- [ ] Página `/suporte/` é indexável (sem `noindex`), mas fica fora de campanha paga

**Expected Result:** o cliente pós-venda chega à resposta certa em até 2 cliques a partir da página de Suporte.

---

### US-5.4: Consultar a tabela de problemas de uso
**As a** Cliente Pós-venda
**I want to** encontrar minha situação numa tabela de problemas comuns e ver a solução
**So that** eu resolva sozinho sem precisar abrir um atendimento

**Acceptance Criteria:**
- [ ] Tabela cobre pelo menos: sistema não reconhece o certificado, senha errada repetidas vezes, certificado
      vencido, configuração em sistema de nota fiscal, sistema de NF excluiu o certificado A3, envio do
      certificado ao contador, token perdido, mudança de responsável legal, e pedido de revogação
- [ ] Cada linha da tabela tem âncora própria, para o atendimento enviar o link direto do trecho
- [ ] Conteúdo desta tabela é estático nesta fase; editável sem depender do programador fica registrado como
      requisito para a fase do painel administrativo (Feature Area 7)

**Expected Result:** o cliente pós-venda encontra a solução do seu problema específico direto na tabela.

---

### US-5.5: Exercer o direito de arrependimento
**As a** Cliente Pessoa Física ou Cliente Pessoa Jurídica
**I want to** desistir da compra dentro do prazo legal e ser reembolsado
**So that** eu não fique preso a uma compra que não quero mais manter

**Acceptance Criteria:**
- [ ] Prazo de arrependimento é de 7 dias corridos, contados da aprovação do certificado
- [ ] Devolução dos valores exige a revogação do certificado
- [ ] Equipamentos (token, cartão, leitora), se houver, precisam retornar em estado de novo, desbloqueados e
      em perfeitas condições de uso

**Expected Result:** o cliente dentro do prazo consegue solicitar arrependimento e ser reembolsado após a
revogação.

---

### US-5.6: Solicitar revogação do certificado
**As a** Cliente Pós-venda
**I want to** solicitar a revogação do meu certificado quando necessário
**So that** eu invalide um certificado que não deve mais ser usado (perda de mídia, mudança de responsável
legal, comprometimento da chave)

**Acceptance Criteria:**
- [ ] Revogação torna o certificado permanente e integralmente inutilizável — não pode ser desfeita
- [ ] Pode ser solicitada pelo titular, pelo responsável (pessoa jurídica), pela empresa/órgão do titular, ou
      pela própria AR/AC/Comitê Gestor do ICP-Brasil
- [ ] Página de Suporte orienta o cliente a contatar a equipe (WhatsApp, telefone ou e-mail) para o
      procedimento

**Expected Result:** o cliente entende a irreversibilidade da revogação antes de solicitá-la e sabe por qual
canal pedir.

---

## 6. Confiança Institucional

### US-6.1: Verificar o credenciamento da Digital Lock como AR
**As a** Visitante Indeciso ou qualquer Cliente
**I want to** conferir, numa fonte oficial, que a Digital Lock é uma Autoridade de Registro credenciada
**So that** eu confie na empresa antes de comprar uma identidade eletrônica dela

**Acceptance Criteria:**
- [ ] Página Quem Somos e o rodapé exibem link para a listagem oficial de Autoridades de Registro no site do
      ITI
- [ ] Texto reforça que o certificado emitido tem o mesmo padrão técnico e validade jurídica de qualquer
      certificado ICP-Brasil, independentemente de onde foi comprado
- [ ] Implementado como Blade component único (`x-credenciamento`), reutilizado em todas as páginas onde
      aparece

**Expected Result:** o visitante confirma o credenciamento por conta própria, numa fonte pública e verificável
por terceiro.

---

### US-6.2: Entender a diferença entre AC e AR
**As a** Visitante Indeciso
**I want to** entender a diferença entre Autoridade Certificadora e Autoridade de Registro
**So that** eu não me confunda sobre quem é a Digital Lock e o que ela faz

**Acceptance Criteria:**
- [ ] Página Quem Somos exibe tabela comparativa AC x AR (o que faz, contato com o cliente, padrão do
      certificado)
- [ ] Texto deixa claro que a Digital Lock é a AR (contato direto com o cliente) e a AC Digital Múltipla é
      quem emite tecnicamente o certificado

**Expected Result:** o visitante entende os dois papéis e por que a Digital Lock é quem atende diretamente.

---

### US-6.3: Ver os dados públicos da empresa
**As a** qualquer Cliente
**I want to** ver a razão social, CNPJ, endereço e canais de atendimento da Digital Lock
**So that** eu confirme que estou comprando de uma empresa real e verificável, e saiba como entrar em contato

**Acceptance Criteria:**
- [ ] Rodapé exibe razão social, CNPJ e endereço completo, exigidos pelo Decreto 7.962/2013
- [ ] Rodapé exibe telefone, WhatsApp e e-mail
- [ ] Página Quem Somos repete os mesmos dados em bloco próprio
- [ ] Endereço e CNPJ não aparecem na dobra inicial, em títulos, headline ou bloco de diferenciais de nenhuma
      página — só no rodapé, em Quem Somos, nas páginas legais e na nota fiscal

**Expected Result:** os dados legais da empresa estão acessíveis e consistentes em todas as páginas onde devem
aparecer, sem virar argumento de venda.

---

## 7. Gestão de Conteúdo (Painel Administrativo)

### US-7.1: Editar o banco de FAQ sem depender de deploy
**As a** Administrador de Conteúdo
**I want to** criar, editar e categorizar perguntas do banco de FAQ pelo painel
**So that** o conteúdo fique sempre atualizado sem depender do programador

**Acceptance Criteria:**
- [ ] Cada pergunta é editável com categoria (uma das 9 categorias do banco) e âncora própria
- [ ] Alteração de uma resposta reflete em todas as páginas que exibem aquela pergunta, sem duplicação de
      texto entre páginas
- [ ] Ferramenta do painel (Filament ou Livewire próprio) ainda não está decidida (ver Open Questions da
      descrição do projeto)

**Expected Result:** o time de atendimento mantém o banco de FAQ atualizado sem abrir uma tarefa de
desenvolvimento.

---

### US-7.2: Editar o bloco de casos de uso do Hub sem depender de deploy
**As a** Administrador de Conteúdo
**I want to** adicionar, editar e remover linhas do bloco "Ainda em dúvida? Veja pela sua situação"
**So that** o Hub acompanhe dúvidas reais que aparecem no atendimento, sem esperar por um deploy

**Acceptance Criteria:**
- [ ] Cada linha (situação → certificado recomendado) é editável pelo painel
- [ ] Alteração publicada aparece imediatamente na página `/certificado-digital/`, sem deploy

**Expected Result:** o bloco de casos de uso cresce ao longo do tempo com base em dúvidas reais, mantido pelo
time de atendimento.

---

### US-7.3: Editar a tabela de problemas de uso da página Suporte sem depender de deploy
**As a** Administrador de Conteúdo
**I want to** adicionar, editar e remover linhas da tabela de problemas de uso
**So that** a página de Suporte reflita os problemas reais mais recentes reportados pelos clientes

**Acceptance Criteria:**
- [ ] Cada linha (situação → resposta) é editável pelo painel, com âncora própria mantida ou reatribuída
- [ ] Alteração publicada aparece imediatamente em `/suporte/`, sem deploy

**Expected Result:** a página de Suporte, que é a que mais muda com o tempo, é mantida pelo time de
atendimento sem depender do programador.

---

## 8. Consistência de Marca e Conteúdo Técnico (Fase 1)

### US-8.1: Nunca duplicar blocos de conteúdo compartilhados
**As a** Digital Lock (negócio/operação)
**I want to** que blocos repetidos entre páginas (elegibilidade de videoconferência, como funciona,
credenciamento, card de produto, FAQ accordion) existam como um único Blade component cada
**So that** uma correção de texto não gere divergência entre páginas nem retrabalho de atendimento

**Acceptance Criteria:**
- [ ] `x-elegibilidade-videoconferencia`, `x-passo-a-passo`, `x-credenciamento` e `x-card-produto` existem como
      componentes únicos em `resources/views/components`
- [ ] Nenhuma das 9 páginas contém HTML solto duplicando o conteúdo desses componentes
- [ ] Texto de elegibilidade para videoconferência é reproduzido palavra por palavra, sem formulações
      aproximadas (ex.: não usar "CNH a partir de 2018" nem "certificado de qualquer certificadora")

**Expected Result:** qualquer alteração de texto compartilhado é feita em um único lugar e se reflete em todas
as páginas.

---

### US-8.2: Aplicar a identidade visual da marca de forma consistente
**As a** Digital Lock (negócio/operação)
**I want to** que todas as páginas usem a paleta e a tipografia reais da marca
**So that** o site novo seja reconhecível como Digital Lock e não pareça um site genérico

**Acceptance Criteria:**
- [ ] Cor primária `#E40044` usada em botões de destaque e elementos de marca
- [ ] Preto `#000000` e branco `#FFFFFF` como base (barra de menu em preto, fundo do header em branco)
- [ ] Botões em formato pill (border-radius bem arredondado) em toda a interface
- [ ] Fonte Inter aplicada globalmente
- [ ] Nenhuma cor fora da paleta descrita é usada (em especial, nenhum azul)

**Expected Result:** a identidade visual é a mesma em todas as 9 páginas, batendo com o site real da Digital
Lock.

---

### US-8.3: Aplicar a nomenclatura obrigatória em todo o conteúdo
**As a** Digital Lock (negócio/operação)
**I want to** que o texto voltado ao cliente siga os termos padronizados
**So that** a comunicação seja consistente e não gere confusão sobre o produto

**Acceptance Criteria:**
- [ ] Usa sempre "Certificado Digital" (nunca "Certificado" isolado), "e-CNPJ"/"e-CPF" (nunca "PJ"/"PF"
      isolados), "AC Digital Múltipla" (nunca "AC Digital"), "Autoridade de Registro credenciada no ICP-Brasil"
      (nunca "Certificadora"), "Validação por videoconferência" (nunca "videochamada", "reunião" ou
      "entrevista") e "Padrão ICP-Brasil" (nunca "certificado oficial" ou "certificado do governo")
- [ ] Revisão de conteúdo das 9 páginas confirma zero ocorrência dos termos banidos

**Expected Result:** o texto publicado usa consistentemente a nomenclatura definida no documento de estrutura.

---

### US-8.4: Layout responsivo com ordem de blocos definida
**As a** Digital Lock (negócio/operação)
**I want to** que as 9 páginas sejam responsivas, com a ordem de blocos mobile da Home respeitada
**So that** a experiência funcione tanto em desktop quanto em celular, sem depender de zoom ou scroll lateral

**Acceptance Criteria:**
- [ ] Layout construído a partir do desktop, com adaptação mobile (não o inverso)
- [ ] Ordem mobile da Home é 1, 2, 4, 3, 5, 6, 7, 8, 9 (o bloco de elegibilidade desce uma posição)
- [ ] Nenhuma página tem scroll horizontal em telas mobile
- [ ] Breadcrumb (`Início › [Seção] › [Subseção]`) aparece em todas as páginas exceto a Home

**Expected Result:** as 9 páginas são utilizáveis e legíveis tanto em desktop quanto em mobile, seguindo a
ordem de blocos definida no wireframe.

---

### US-8.5: Aplicar as regras de URL e SEO do site
**As a** Digital Lock (negócio/operação)
**I want to** que cada página tenha uma única URL fixa, canonical e sem duplicação de domínio
**So that** o site não sofra penalização de SEO nem gere confusão de rota

**Acceptance Criteria:**
- [ ] Cada uma das 9 páginas tem exatamente uma URL, sem id numérico, data ou parâmetro no slug
- [ ] Toda página indexável declara uma tag canonical
- [ ] Domínio funciona em uma única versão (sem duplicação com/sem `www`, com/sem barra final)
- [ ] Variação de produto usa seletor na mesma URL quando o preço é igual entre A1/A3, e URL própria quando o
      produto muda de contexto de venda (e-CPF vs. e-CNPJ vs. MEI)

**Expected Result:** a estrutura de URLs das 9 páginas segue as regras técnicas definidas antes da primeira
linha de código, sem necessidade de correção posterior.

---

## Open Questions

- **Gateway de pagamento e provedor de e-mail:** ainda em aberto (ver Open Questions da descrição do projeto).
  Bloqueiam a implementação funcional de US-2.4, US-2.5 e US-3.2, mas não bloqueiam as stories de conteúdo
  estático desta fase.
- **Ferramenta do painel administrativo:** inclinação por Livewire próprio, decisão final pendente. Bloqueia a
  implementação funcional das stories da Feature Area 7.
- **Página de parceria com contadores/ERP:** decisão de negócio ainda não fechada; nenhuma story foi criada
  para ela nesta versão do backlog, por estar fora de escopo do projeto por ora.
- **Textos jurídicos das páginas legais** (Política de Privacidade, Termos de Uso, Trocas e Devoluções):
  pendentes de redação jurídica; nenhuma story de conteúdo foi criada para essas páginas nesta versão do
  backlog, por estarem fora de escopo do projeto por ora.

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Escolher perfil na Home | High | Pending |
| US-1.2 | Comparar e-CPF vs. e-CNPJ no Hub | High | Pending |
| US-1.3 | Comparar A1 vs. A3 no Hub | High | Pending |
| US-1.4 | Ver casos de uso por situação | High | Pending |
| US-2.1 | Ver detalhes e escolher variante A1/A3 na página de produto | High | Pending |
| US-2.2 | Entender a diferença entre certificado digital e CCMEI | High | Pending |
| US-3.1 | Ver o passo a passo de emissão | High | Pending |
| US-4.1 | Ver a página de Renovação | High | Pending |
| US-5.1 | Consultar FAQ por categoria em cada página comercial | High | Pending |
| US-5.2 | Consultar o banco completo de FAQ na página Como Emitir | High | Pending |
| US-5.3 | Usar a triagem por âncora na página Suporte | High | Pending |
| US-5.4 | Consultar a tabela de problemas de uso | High | Pending |
| US-6.1 | Verificar o credenciamento da Digital Lock como AR | High | Pending |
| US-6.2 | Entender a diferença entre AC e AR | High | Pending |
| US-6.3 | Ver os dados públicos da empresa | High | Pending |
| US-8.1 | Nunca duplicar blocos de conteúdo compartilhados | High | Pending |
| US-8.2 | Aplicar a identidade visual da marca de forma consistente | High | Pending |
| US-8.3 | Aplicar a nomenclatura obrigatória em todo o conteúdo | High | Pending |
| US-8.4 | Layout responsivo com ordem de blocos definida | High | Pending |
| US-8.5 | Aplicar as regras de URL e SEO do site | High | Pending |
| US-2.3 | Cadastrar-se no checkout | Medium | Pending |
| US-3.4 | Baixar e instalar o certificado após aprovação | Medium | Pending |
| US-4.2 | Renovar trocando de formato (A1 ↔ A3) | Medium | Pending |
| US-5.5 | Exercer o direito de arrependimento | Medium | Pending |
| US-5.6 | Solicitar revogação do certificado | Medium | Pending |
| US-2.4 | Pagar com Pix como forma prioritária | Low | Pending |
| US-2.5 | Receber nota fiscal da compra | Low | Pending |
| US-3.2 | Receber e-mail de agendamento após pagamento confirmado | Low | Pending |
| US-3.3 | Agendar e realizar a validação por videoconferência | Low | Pending |
| US-7.1 | Editar o banco de FAQ sem depender de deploy | Low | Pending |
| US-7.2 | Editar o bloco de casos de uso do Hub sem depender de deploy | Low | Pending |
| US-7.3 | Editar a tabela de problemas de uso da página Suporte sem depender de deploy | Low | Pending |
