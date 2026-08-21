# Clientes

[← Voltar ao índice de módulos](README.md)

## Finalidade

Cobre tanto a gestão de clientes pelo time interno (painel) quanto a
autoatendimento do próprio cliente: criar conta, entrar, recuperar senha e
gerenciar seus dados e pedidos em "Minha conta".

- **Rotas (painel)**: `painel/clientes/` (`painel.clientes`),
  `painel/clientes/{id}/` (`.show`)
- **Rotas (cliente)**: `cliente/login/`, `cliente/registro/`,
  `cliente/esqueci-senha/`, `cliente/redefinir-senha/{token}/`,
  `cliente/logout`; área logada em `minha-conta/pedidos/`,
  `minha-conta/dados/`, `minha-conta/senha/` (middleware `auth:customer`)
- **Guard de autenticação**: `customer` (`App\Models\Customer`) — **separado**
  do guard `web` usado pelo time interno (ver
  [Autenticação e Configurações](autenticacao-e-configuracoes.md)). Login,
  registro e recuperação de senha do cliente são componentes Livewire
  próprios, **sem Laravel Fortify**.

## Funcionalidades

### Painel (time interno)

- **Listagem de clientes** paginada, com filtros: tipo de pessoa (PF/PJ),
  UF, período de cadastro, "certificado vencendo em 90 dias", busca por
  nome/documento/e-mail.
- **Detalhe do cliente**: dados cadastrais, endereço primário, histórico de
  pedidos e de titulares de certificado já emitidos/em andamento
  (`IssuanceData` de todos os pedidos do cliente).

### Autoatendimento (cliente)

- **Registro** (`cliente/registro/`): nome, e-mail, senha — cadastro
  simplificado, sem CPF/CNPJ.
- **Login / logout**, **"esqueci minha senha"** e **redefinição de senha**
  por e-mail (`CustomerResetPasswordNotification`).
- **"Minha conta"**:
  - `minha-conta/pedidos/` — todos os itens comprados, com um card por
    item mostrando estado ("Aguardando dados do titular", "Aguardando
    aprovação do GFSIS", "Videoconferência agendada", "Certificado
    emitido", "Certificado vencendo/vencido") e link para a emissão
    pendente.
  - `minha-conta/dados/` — editar nome/e-mail/telefone e opt-in de
    marketing.
  - `minha-conta/senha/` — trocar senha (exige senha atual).

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `Customer` | Cliente — pode existir "incompleto" (só nome/e-mail) até a primeira compra. |
| `CustomerAddress` | Endereço(s) do cliente; o endereço com `is_primary = true` é o usado em cobrança e emissão. |
| `Order` / `OrderItem` / `IssuanceData` / `OrderItemGfsis` | Fonte dos cards de "Meus pedidos" e do histórico no painel. |
| `Role` | `customers.role_id` aponta para o papel `customer` — mesma tabela de papéis usada pelo time interno, mas os dois nunca se misturam (guards diferentes). |

## Principais fluxos

### Dois caminhos de criação de `Customer`

1. **Autoatendimento** (`App\Actions\Fortify\CreateNewCustomer`, usado por
   `cliente/registro/`): cria um `Customer` só com `legal_name`, `email`,
   `password`, `terms_accepted_at` — `document`, `holder_type_id` e `phone`
   ficam `null` (colunas tornadas anuláveis especificamente para viabilizar
   esse cadastro leve).
2. **Checkout** (ver [Carrinho e Checkout](carrinho-e-checkout.md)):
   `Customer::query()->updateOrCreate(['document' => $this->document], [...])`
   — identifica o cliente **pelo documento**, não pelo e-mail.

Como consequência: se a mesma pessoa primeiro se registra em
`cliente/registro/` (sem informar documento) e depois compra informando o
mesmo e-mail, o `updateOrCreate` do checkout busca por `document` — que no
registro ficou `null` — não encontra o registro existente e tenta **criar
um novo** `Customer` com aquele e-mail. Como `customers.email` é único,
isso levaria a um erro de unicidade em vez de completar o cadastro já
existente. O checkout já trata separadamente o caso de "e-mail já
cadastrado com outro documento" (mensagem de erro amigável pedindo login),
mas esse cenário específico — registro leve seguido de compra com o mesmo
e-mail — não passa por essa validação porque o `Customer` encontrado por
e-mail nem chega a ser comparado antes do `updateOrCreate` por documento.

### Sincronização de carrinho no login/registro

Tanto o login quanto o registro do cliente mesclam o carrinho de sessão
(`cart_session_id`) no carrinho do cliente ao autenticar — ver
[Carrinho e Checkout](carrinho-e-checkout.md).

## Como o usuário interage

- **Cliente**: se cadastra/loga de forma independente do site institucional
  e do painel; usa "Minha conta" para acompanhar a emissão do certificado
  comprado.
- **Time interno**: consulta o cadastro e o histórico de compras/emissões
  de qualquer cliente a partir do painel — **somente leitura**, não há
  edição de cliente pelo painel.

## Regras de negócio importantes

- Não existe fluxo de **exclusão/anonimização de cliente** no painel.
- O filtro "Certificado vencendo" na listagem do painel usa uma janela fixa
  de 90 dias (`certificate_expires_at` entre agora e agora+90 dias),
  diferente da janela de 30 dias usada na [Visão geral](visao-geral.md) e
  em [Relatórios](relatorios.md) — mesma métrica, janelas diferentes
  conforme o contexto de cada tela.

## Relação com outros módulos

- **[Carrinho e Checkout](carrinho-e-checkout.md)**: principal ponto de
  criação/atualização de `Customer`/`CustomerAddress` "completos".
- **[Emissão (GFSIS)](emissao-gfsis.md)**: "Meus pedidos" reflete
  diretamente o estado de `IssuanceData`/`OrderItemGfsis` de cada item do
  cliente.
- **[Vendas](vendas.md)**: todo pedido pertence a um `Customer`; o detalhe
  do pedido no painel mostra os dados do cliente.
- **[Relatórios](relatorios.md)**: relatórios de vendas/estornos buscam
  pedidos também por nome/documento do cliente.
