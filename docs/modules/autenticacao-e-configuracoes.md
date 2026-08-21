# Autenticação e Configurações (painel)

[← Voltar ao índice de módulos](README.md)

## Finalidade

Cobre o acesso do time interno ao painel administrativo — login, registro,
verificação de e-mail, autenticação de dois fatores (2FA) — via
[Laravel Fortify](https://laravel.com/docs/fortify), além da tela onde
cada usuário logado gerencia sua própria conta.

- **Guard**: `web` (`App\Models\User`) — separado do guard `customer` usado
  pelo cliente final (ver [Clientes](clientes.md)).
- **Rotas**: registradas automaticamente pelo Fortify (login, registro,
  verificação de e-mail, recuperação de senha, desafio 2FA); telas
  customizadas em `resources/views/pages/auth/*.blade.php`.
- **Configurações da conta**: `painel/configuracoes/`
  (`painel.configuracoes`), dentro do grupo `auth` + `verified`.

## Funcionalidades

- **Login / logout**, com **rate limiting** configurado no
  `FortifyServiceProvider`.
- **Registro de novo usuário interno**: qualquer pessoa com acesso à tela
  de registro pode criar uma conta — recebe automaticamente o papel
  `support` (criado sob demanda se ainda não existir).
- **Verificação de e-mail** obrigatória (middleware `verified` em todas as
  rotas de `painel/`).
- **Autenticação de dois fatores (2FA/TOTP)**: ativar/desativar, códigos de
  recuperação, com confirmação de setup.
- **Tela "Configurações"** (`painel/configuracoes/`): trocar senha,
  gerenciar 2FA, atualizar nome/e-mail do perfil, excluir a própria conta.

## Entidades envolvidas

| Model | Papel |
| --- | --- |
| `User` | Usuário do time interno — `role_id`, `is_active`, `last_login_at`, campos de 2FA (herdados do Fortify). |
| `Role` | Papéis: `admin`, `operations`, `finance`, `support`, `customer` (este último só para clientes). |

## Principais fluxos

`CreateNewUser` (Action que o Fortify usa para criar contas) sempre atribui
o papel `support` a um novo cadastro, independentemente de quem está se
registrando. `ResetUserPassword` é a Action usada na recuperação de senha
do painel.

A tela de Configurações reaproveita as validações de
`App\Concerns\PasswordValidationRules`/`ProfileValidationRules` (as mesmas
usadas por `CreateNewUser`), e delega desativação de 2FA a
`Laravel\Fortify\Actions\DisableTwoFactorAuthentication`.

## Como o usuário interage

Qualquer pessoa do time interno acessa `painel/` autenticando-se com e-mail
e senha (mais 2FA, se ativado); a partir do menu do painel, acessa
"Configurações" para gerenciar a própria conta.

## Regras de negócio importantes

- **Papéis existem, mas não restringem acesso a nenhuma rota hoje.**
  `App\Http\Middleware\RoleMiddleware` (alias `role`) está registrado em
  `bootstrap/app.php` e sabe recusar acesso por papel
  (`abort(403)` se `$user->role->slug` não estiver na lista permitida), mas
  **nenhuma rota do projeto usa esse middleware** — todas as rotas de
  `painel/` exigem apenas `auth` + `verified`, sem checagem de papel. Na
  prática, qualquer usuário interno ativo — `admin`, `operations`,
  `finance` ou `support` — tem acesso a 100% do painel, incluindo Vendas,
  Produtos, Formas de Pagamento e Configurações. A única distinção de
  papel que **é** aplicada em código hoje é `roles.slug = 'finance'` para
  decidir quem recebe o e-mail de "pagamento estornado" (ver
  [Pagamentos e Estornos](pagamentos-e-estornos.md)).
- **Registro é aberto, não por convite**: não há aprovação nem restrição de
  domínio de e-mail para criar uma conta de acesso ao painel — quem se
  registra entra automaticamente com papel `support`.
- `users.is_active` existe no schema, mas nenhuma rota/Action verifica esse
  campo para bloquear login — é usado apenas para marcar contas de sistema
  (ex.: a conta usada para reembolsos automáticos, ver
  [Pagamentos e Estornos](pagamentos-e-estornos.md)) como não destinadas a
  login.

## Relação com outros módulos

Este módulo é transversal: **todas** as telas de `painel/*` (Visão geral,
Vendas, Filas e Recuperação, Produtos, Formas de Pagamento, Clientes,
Relatórios) dependem dele para autenticar o acesso. Não há dependência na
direção contrária.
