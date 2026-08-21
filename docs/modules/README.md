# Módulos do sistema — Digital Lock E-commerce

Esta pasta documenta, módulo a módulo, o que existe hoje implementado no
repositório: para que serve, quais telas/rotas o compõem, quais Models e
tabelas ele usa, os principais fluxos e como ele se conecta com os demais
módulos. Cada arquivo cita os arquivos reais do código (rotas, Actions,
Models, componentes Livewire) para quem quiser aprofundar.

> Toda a documentação aqui foi levantada lendo o código-fonte (rotas,
> Actions, Models, migrations, seeders e os componentes Livewire de
> `resources/views/pages/`), não a partir do nome das classes ou de
> suposições. Onde o comportamento real diverge do que o nome sugere (ex.:
> carrinho persistido que não é usado pelo checkout), isso é anotado
> explicitamente.
>
> Para fluxos técnicos ponta-a-ponta mais detalhados (payloads de API,
> decisões de implementação, questões em aberto), ver também
> [`docs/fluxos-tecnicos.md`](../fluxos-tecnicos.md),
> [`docs/safe2pay.md`](../safe2pay.md) e [`docs/gfsis.md`](../gfsis.md).

## Índice de módulos

| Módulo | O que cobre |
| --- | --- |
| [Visão geral](visao-geral.md) | Dashboard do painel (`painel/`): KPIs, funil operacional e filas que exigem ação. |
| [Carrinho e Checkout](carrinho-e-checkout.md) | Vitrine → carrinho → checkout (Pix/Cartão/Boleto) → tela de pagamento. |
| [Pagamentos e Estornos](pagamentos-e-estornos.md) | Cobrança via Safe2Pay, webhook, reconciliação automática e estornos (Pix/Cartão). |
| [Emissão (GFSIS)](emissao-gfsis.md) | Coleta dos dados de titular e registro/acompanhamento do certificado na GFSIS após o pagamento. |
| [Vendas](vendas.md) | Gestão de pedidos no painel: listagem, filtros, exportação e detalhe com linha do tempo. |
| [Filas e Recuperação](filas-e-recuperacao.md) | Fila de recuperação de vendas pagas sem dados, falhas de integração e rotinas agendadas. |
| [Produtos](produtos.md) | Catálogo (Certificado Digital e-CPF/e-CNPJ), variantes A1/A3, preços e promoções. |
| [Formas de Pagamento](formas-de-pagamento.md) | Configuração de Pix/Cartão/Boleto (desconto, parcelas) e cupons de desconto. |
| [Clientes](clientes.md) | Cadastro de clientes, área "Minha conta" e autenticação do cliente (guard `customer`). |
| [Relatórios](relatorios.md) | 9 relatórios operacionais/financeiros com filtros e exportação CSV. |
| [Autenticação e Configurações (painel)](autenticacao-e-configuracoes.md) | Login/2FA do time interno (Laravel Fortify), papéis (`roles`) e tela de configurações da conta. |

## Como o sistema se conecta, em uma frase

`Produto` (com suas variantes) → cliente adiciona ao `Carrinho` → segue para
o `Checkout` (que hoje cobra apenas 1 variante, ver
[Carrinho e Checkout](carrinho-e-checkout.md#carrinho-e-checkout-não-estão-conectados)) →
`CreateOrderFromCart` grava o `Pedido` (snapshot dos itens) → a `Forma de
pagamento` escolhida decide qual Action de cobrança roda contra a Safe2Pay →
o webhook da Safe2Pay confirma o pagamento → o pedido é marcado `paid` e
entra na fila de `Emissão (GFSIS)` → o cliente preenche os dados de titular →
o item é registrado na GFSIS → tudo isso alimenta, ao mesmo tempo, o painel
de `Vendas`, a `Visão geral`, as `Filas e Recuperação` e os `Relatórios`.

Veja o detalhamento dessas transições — com os arquivos reais envolvidos —
na seção "Relação com outros módulos" de cada documento.
