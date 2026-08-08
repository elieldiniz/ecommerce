---
paths:
  - 'resources/views/**'
---

# Views

## Nomenclatura obrigatória em texto voltado ao cliente
Todo texto de cliente (blade views/components) deve usar: "Certificado Digital" (nunca "Certificado" isolado), "e-CNPJ"/"e-CPF" (nunca "PJ"/"PF" isolados), "AC Digital Múltipla" (nunca "AC Digital"), "Autoridade de Registro credenciada no ICP-Brasil" (nunca "Certificadora"), "Validação por videoconferência" (nunca "videochamada", "reunião" ou "entrevista"), "Padrão ICP-Brasil" (nunca "certificado oficial" ou "certificado do governo"). Todo conteúdo copiado do documento de referência (Phases 2-10) deve ser revisado contra esta lista antes da página ser dada como concluída. Fonte: US-8.3 / project-description.md.

## Identidade visual: raio de botão, paleta e tipografia do mockup aprovado
O mockup aprovado (.spec/init/design/Digital Lock Mockups.dc.html) supera project-description.md em 3 pontos: botões usam `rounded-lg` (8px), nunca `rounded-full`/pill; tipografia é Plus Jakarta Sans (títulos/botões/labels, token `--font-heading`) + IBM Plex Sans (corpo, token `--font-sans`), não Inter; header do site é fundo branco, não preto (só o rodapé é `#14110f`). Paleta de marca fica em tokens Tailwind: --color-brand (#E40044), --color-ink, --color-muted, --color-muted-light, --color-surface-alt, --color-border, --color-border-light, --color-highlight, --color-cta-secondary — nenhuma cor fora dessa paleta (em especial nenhum azul).
