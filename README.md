# Digital Lock — E-commerce

E-commerce da Digital Lock, Autoridade de Registro credenciada no ICP-Brasil, para venda e emissão de Certificados Digitais (e-CPF e e-CNPJ, formatos A1 e A3) com validação por videoconferência.

A aplicação cobre toda a jornada: vitrine de produtos, carrinho, checkout com Pix/Boleto/Cartão de crédito, emissão do certificado junto à GFSIS após o pagamento e um painel administrativo para gestão de vendas, produtos, clientes, formas de pagamento e cupons.

## Principais funcionalidades

- **Vitrine e carrinho**: catálogo de produtos (Certificado Digital e-CPF, e-CNPJ e variações para MEI), carrinho persistido por sessão ou por cliente autenticado.
- **Checkout com 3 formas de pagamento** (via Safe2Pay): Pix, Boleto e Cartão de crédito (com tokenização e consulta de parcelamento).
- **Cupons de desconto** aplicáveis no checkout.
- **Emissão de certificado (GFSIS)**: após o pagamento, o cliente recebe um link de emissão por e-mail, preenche os dados de titular (PF/PJ) e o pedido é enviado automaticamente à GFSIS para emissão.
- **Área do cliente** (`minha-conta/`): histórico de pedidos, dados cadastrais e alteração de senha, com autenticação própria via guard `customer`.
- **Painel administrativo** (`painel/`), autenticado via Laravel Fortify (guard `web`): visão geral, vendas, produtos, formas de pagamento, cupons, clientes, relatórios e configurações.
- **Estornos** de pagamentos (Pix e Cartão).
- **Webhooks** da Safe2Pay (status de pagamento) e da GFSIS (status de emissão), processados de forma assíncrona via filas.
- **Reconciliação automática** de pagamentos e pedidos travados na GFSIS via comandos agendados.

## Tecnologias utilizadas

- **Backend**: PHP 8.3+, [Laravel 13](https://laravel.com/)
- **Frontend reativo**: [Livewire 4](https://livewire.laravel.com/) (componentes single-file, estilo `⚡nome.blade.php`)
- **UI**: [Flux UI](https://fluxui.dev/), Tailwind CSS 4, Vite
- **Performance**: [Livewire Blaze](https://github.com/livewire/blaze) (otimização de renderização de componentes Blade)
- **Autenticação**: [Laravel Fortify](https://laravel.com/docs/fortify) (painel administrativo)
- **Banco de dados**: MySQL (Sail) / SQLite (ambiente padrão via `.env`)
- **Filas**: driver `database`
- **Ambiente de desenvolvimento**: [Laravel Sail](https://laravel.com/docs/sail) (Docker)
- **Qualidade**: [Pint](https://laravel.com/docs/pint) (estilo de código), [Larastan/PHPStan](https://github.com/larastan/larastan) (análise estática), PHPUnit (testes)
- **Integrações externas**:
  - [Safe2Pay](https://safe2pay.com.br/) — gateway de pagamento (Pix, Boleto e Cartão de crédito)
  - GFSIS — sistema de emissão de Certificado Digital

## Requisitos

- Docker e Docker Compose (para rodar via Laravel Sail)
- Alternativamente, para rodar sem Docker: PHP ^8.3, Composer, Node.js 22 e um banco de dados MySQL ou SQLite

## Instalação

```bash
# Clonar o repositório
git clone <url-do-repositorio>
cd ecommerce

# Instalar dependências PHP
composer install

# Copiar o arquivo de ambiente
cp .env.example .env

# Instalar dependências JS
npm install
```

## Configuração

1. Gere a chave da aplicação:

   ```bash
   php artisan key:generate
   ```

2. Configure o arquivo `.env` (veja a seção de variáveis de ambiente abaixo).

3. Suba os containers (via Sail) e rode as migrations:

   ```bash
   vendor/bin/sail up -d
   vendor/bin/sail artisan migrate
   ```

Também é possível rodar tudo de uma vez com o script do Composer:

```bash
composer setup
```

Esse script instala as dependências PHP e JS, cria o `.env`, gera a `APP_KEY`, roda as migrations e faz o build dos assets.

## Variáveis de ambiente

Além das variáveis padrão do Laravel (`APP_*`, `DB_*`, `SESSION_*`, `MAIL_*`, etc.), este projeto utiliza:

| Variável | Descrição |
| --- | --- |
| `SAFE2PAY_API_KEY_SANDBOX` | Chave de API da Safe2Pay para o ambiente sandbox |
| `SAFE2PAY_API_KEY_PRODUCTION` | Chave de API da Safe2Pay para produção |
| `SAFE2PAY_IS_SANDBOX` | Define se as chamadas à Safe2Pay usam o ambiente sandbox (`true`/`false`). **Pix não possui sandbox na Safe2Pay** — mesmo com essa flag em `true`, toda cobrança Pix é real |
| `GFSIS_LOGIN` | Usuário de autenticação na API da GFSIS |
| `GFSIS_SENHA` | Senha de autenticação na API da GFSIS |
| `GFSIS_BASE_URL` | URL base da API da GFSIS |

Consulte `.env.example` para a lista completa de variáveis disponíveis.

## Como executar o projeto

Com o [Laravel Sail](https://laravel.com/docs/sail):

```bash
vendor/bin/sail up -d
```

A aplicação fica disponível em `http://localhost` (porta configurável via `APP_PORT`).

Para desenvolvimento com hot-reload dos assets (Vite) e observação da fila/logs em paralelo:

```bash
composer dev
```

Para compilar os assets manualmente:

```bash
npm run build   # produção
npm run dev     # desenvolvimento
```

## Estrutura do projeto

```
app/
├── Actions/          # Casos de uso (Cart, Checkout, Payments, Refunds, Gfsis, Fortify)
├── Console/Commands/ # Comandos agendados (reconciliação de pagamentos e GFSIS, e-mails de recuperação)
├── Http/Controllers/ # Checkout (tokenização de cartão), Pedido (emissão), Webhooks
├── Jobs/              # Processamento assíncrono de webhooks e registro na GFSIS
├── Livewire/          # Actions de suporte aos componentes Livewire
├── Mail/              # E-mails transacionais (link de emissão)
├── Models/            # Modelos Eloquent
├── Notifications/     # Notificações (reset de senha, estorno)
└── Support/
    ├── Safe2Pay/       # Cliente HTTP, payload builder e enums do gateway de pagamento
    └── Gfsis/          # Cliente HTTP, payload builder e enums da integração de emissão

resources/views/
├── components/         # Componentes Blade reutilizáveis
├── layouts/            # Layouts (site, auth, painel)
├── pages/              # Páginas e componentes Livewire single-file (⚡nome.blade.php)
│   ├── auth/customer/  # Login, registro e recuperação de senha do cliente
│   ├── certificado-digital/
│   ├── minha-conta/    # Área logada do cliente
│   ├── painel/         # Painel administrativo
│   └── pedido/         # Pagamento e emissão do pedido
└── partials/

routes/web.php          # Única fonte de rotas da aplicação (não há routes/api.php)
database/migrations/     # Estrutura do banco (pedidos, pagamentos, produtos, clientes, GFSIS, etc.)
docs/                     # Documentação técnica interna (fluxos, docs oficiais do projeto)
```

Há dois guards de autenticação independentes: `web` (`App\Models\User`, painel administrativo, via Fortify) e `customer` (`App\Models\Customer`, área do cliente, com componentes Livewire próprios).

Para uma visão funcional e técnica de cada módulo do sistema (Vendas, Produtos, Formas de Pagamento, Clientes, Relatórios, Filas e Recuperação, etc.) e de como eles se conectam entre si, ver a [Documentação dos módulos](docs/modules/README.md).

## Testes

O projeto usa PHPUnit, com suítes `Unit` e `Feature` (`phpunit.xml`).

```bash
# Rodar toda a suíte
vendor/bin/sail artisan test --compact

# Rodar um arquivo específico
vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php

# Filtrar por nome de teste
vendor/bin/sail artisan test --compact --filter=nomeDoTeste
```

## Comandos úteis

```bash
# Formatação de código (Pint)
vendor/bin/sail composer lint
vendor/bin/sail composer lint:check   # apenas verifica, sem alterar

# Análise estática (Larastan/PHPStan)
vendor/bin/sail composer types:check

# Roda lint, análise estática e testes (mesma pipeline do CI)
vendor/bin/sail composer ci:check
```

O CI (`.github/workflows/tests.yml`) executa `composer setup` seguido de `composer ci:check` a cada push/PR na branch `main`.

## Comandos agendados

- `GfsisReconcileStuckOrders` — reconcilia pedidos travados na integração com a GFSIS.
- `ReconcilePendingPayments` — reconcilia pagamentos pendentes junto à Safe2Pay.
- `RecuperacaoEnviarReforcoEmail24h` — envia e-mail de reforço para carrinhos/pedidos em recuperação após 24h.

## Integrações externas

- **Safe2Pay**: gateway responsável pelas cobranças de Pix, Boleto e Cartão de crédito, e pelo webhook de atualização de status de pagamento. Detalhes da implementação: [Documentação da integração com Safe2Pay](docs/safe2pay.md).
- **GFSIS**: sistema responsável pela emissão do certificado digital após confirmação do pagamento, com webhook próprio de atualização de status. Detalhes da implementação: [Documentação da integração com GFSIS](docs/gfsis.md).

## Documentação adicional

- [Documentação dos módulos](docs/modules/README.md) — visão funcional e técnica de cada módulo do sistema e como eles se relacionam.
- [Fluxos técnicos](docs/fluxos-tecnicos.md) — fluxos ponta-a-ponta detalhados, decisões de implementação e questões em aberto.
