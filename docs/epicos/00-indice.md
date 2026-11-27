# Libris te liberant — índice dos épicos

Nome de trabalho: **Libris te liberant**. Livraria de livros e materiais digitais, com checkout Pix no sandbox do Juntos Se Faz, biblioteca pessoal e programa de afiliados simplificado.

Stack fechada: Laravel (starter oficial com Inertia e Vue 3), MySQL 8, gateway Juntos Se Faz em staging.

Não há transcrição de reunião. Cada ticket aponta a seção do arquivo `Desafio Técnico` e o trecho da proposta de produto que justificam a tarefa.

Cada ticket tem **Status:** `Pendente` ou `Concluído`. Ao encerrar a implementação, o status do ticket muda no mesmo turno.

## Ordem de implementação

Faça um épico por vez, na ordem abaixo. Dentro do épico, siga o número do ticket.

| Ordem | Arquivo | O que entrega |
|---:|---|---|
| 1 | `01-fundacao-e-ambiente.md` | Projeto sobe, banco sobe, visual base, segredos fora do Git |
| 2 | `02-acesso-e-papeis.md` | Conta de cliente e conta de administrador |
| 3 | `03-catalogo-da-loja.md` | Vitrine com livros de exemplo |
| 4 | `04-carrinho-e-pedidos.md` | Intenção de compra gravada, ainda sem cobrar |
| 5 | `05-pagamentos.md` | Pix criado e consultado no gateway |
| 6 | `06-confirmacao-e-webhook.md` | Pagamento confirmado libera o livro |
| 7 | `07-biblioteca-do-cliente.md` | Histórico, detalhe e leitura na plataforma |
| 8 | `08-programa-de-afiliados.md` | Link, clique, atribuição e comissão |
| 9 | `11-api-da-aplicacao.md` | Chave nas configurações e consulta dos próprios dados de compra e venda |
| 10 | `09-painel-administrativo.md` | Operação da loja |
| 11 | `10-qualidade-e-entrega.md` | Testes, segurança e README do edital |
| 12 | `12-pagsmile.md` | O mesmo Pix também no sandbox da Pagsmile |

Os épicos 01 a 07, o 10 e o 11 fecham o desafio. Os épicos 08 e 09 são o diferencial ligado à vaga (afiliados e painel). O épico 12 é o diferencial do edital: os dois gateways. Ele entra depois do Ticket 19 e não bloqueia biblioteca, afiliados, API nem README. Não comece por eles.

O épico 11 é a API da Libris te liberant: chave pessoal e leitura dos próprios dados. O cliente do Juntos Se Faz (Ticket 14) é o caminho inverso, o Libris chamando o gateway. A API fica depois dos afiliados para já nascer com compras e vendas; se o épico 08 for cortado, ela segue com as vendas zeradas.

## Corte se o prazo apertar

Nesta ordem, pare de acrescentar:

1. Indicadores do painel (Ticket 30).
2. Gestão de usuários no admin além de listar e promover admin (Ticket 29, parte de usuários).
3. Leitura formatada do livro; deixe o conteúdo acessível numa página protegida simples (Ticket 22).
4. Extrato visual do afiliado; mantenha o cálculo gravado no banco (Ticket 26, parte de tela).

Não corte: catálogo, checkout, criação e consulta de pagamento no gateway, a chave de API e a consulta dos próprios dados, status na tela, validação, persistência, `.env.example`, `docs/api.md` e o README com o uso de IA.

## Decisões já fechadas

Estas escolhas valem para todos os tickets. Não reabrir durante a implementação, a menos que a documentação oficial do gateway contradiga um ponto de pagamento.

1. Checkout exige conta. Visitante navega e monta o carrinho; para pagar, entra ou cria conta. A biblioteca precisa de um dono.
2. Pedido e pagamento são tabelas diferentes. O pedido é a intenção de compra. O pagamento é uma tentativa de liquidar esse pedido. Um pedido pode ter várias tentativas; só uma fica pendente por vez.
3. Dinheiro é inteiro em centavos no banco (`price_cents`, `total_cents`). A conversão para o formato que o gateway espera acontece só na borda da integração, depois de ler a documentação oficial. O exemplo público do gateway mostra `149.90` em reais, então a conversão não pode ser chutada.
4. Método de pagamento deste projeto: Pix, ambiente staging, sem dinheiro real. O gateway padrão é o Juntos Se Faz. A Pagsmile é a segunda opção do mesmo checkout, no épico 12.
5. Acesso à biblioteca não tem tabela própria. Um livro está liberado quando existe item de pedido pago daquele produto para aquele usuário.
6. Todo usuário autenticado pode indicar. O código de afiliado nasce no cadastro. Não há aprovação nem saque de comissão.
7. Atribuição pelo último clique, cookie de 7 dias. Compra do próprio afiliado não gera comissão.
8. Comissão é um percentual único em configuração (`AFFILIATE_COMMISSION_PERCENT`), calculada uma vez, quando o pagamento confirma.
9. Administrador é `users.is_admin`. Sem pacote de permissões.
10. Textos e capas dos livros são originais de exemplo. Nenhum livro protegido por direito autoral entra no repositório.
11. Carrinho vive na sessão. Não há tabela de carrinho.
12. Cada cliente traduz o vocabulário do próprio gateway para os status internos. Controller não interpreta string do gateway.
13. A API da Libris te liberant é somente leitura. O usuário entra na conta, gera uma chave nas configurações e, com ela, consulta os dados da própria conta: compras e vendas indicadas. A chave não cria pedido, não dispara Pix e não autentica a tela. Criar e consultar cobrança no gateway continua no checkout.
14. O front usa Yarn (`yarn.lock` versionado). O Docker Compose continua no projeto, com PHP, servidor HTTP, MySQL e o build do front feito por Yarn.

## Modelo mínimo

- `users` — conta, `is_admin`, código de afiliado.
- `categories` — nome e slug.
- `products` — livro: autor, capa, preço em centavos, conteúdo para leitura, destaque, publicado.
- `orders` — dono, status, total, afiliado atribuído (pode ser nulo).
- `order_items` — produto, preço e título copiados no momento da compra.
- `payments` — tentativa ligada ao pedido, id externo, status, corpos de request e response.
- `affiliate_clicks` — clique no link `?ref=`.
- `commissions` — uma comissão por pedido pago.

`affiliates` da proposta vira coluna em `users` (`affiliate_code` único). Uma tabela só para o código não acrescenta regra.

## Status de domínio

Pedido: `pending_payment`, `paid`, `canceled`, `expired`.

Pagamento: `pending`, `paid`, `failed`, `canceled`, `expired`.

Comissão: `pending` até o pagamento confirmar; então `confirmed`. Se o pagamento falha, a comissão nem chega a ser criada.
