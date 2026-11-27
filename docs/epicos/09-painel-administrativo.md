# Épico 09 — Painel administrativo

**Objetivo:** o administrador cadastra a prateleira e acompanha pedidos, pagamentos e indicações.
**Prioridade:** alta na proposta, desde que o fluxo de compra já funcione. Telas simples, sem framework de admin.
**Antes de:** polir indicadores. CRUD e listas vêm primeiro.

Todas as rotas deste épico ficam atrás do middleware do Ticket 06.

---

## Ticket 27 — [ADMIN] - Abrir a casca do painel

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (painel administrativo).
**Menções na documentação:** `Desafio Técnico`, seção 4 (interface funcional) e seção 6 (autenticação).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 06, Ticket 04.

**Descrição:** Layout próprio do admin, sem o cabeçalho de vitrine: menu para livros, categorias, pedidos e afiliados. Página inicial `/admin` com quatro números: livros publicados, pedidos pagos, total vendido em centavos formatado e comissões confirmadas. Os números podem ser consultas diretas; o refinamento visual é o Ticket 30.

**Explicação:** É a porta dos fundos da livraria. Quem administra cai num menu claro e já vê, de relance, se a loja tem livros e se já vendeu alguma coisa.

**Pontos de atenção:**

- Reutilizar componentes de flash e formatação de dinheiro da loja. Não criar um segundo design system.
- Cliente comum recebe 403, inclusive nas APIs que o painel chamar.
- Links do menu só aparecem para `is_admin`.

**DoD:**

- Admin entra e vê o menu e os quatro números, mesmo que zero.
- Cliente recebe 403 em `/admin`.
- O total vendido soma apenas pedidos `paid`.

---

## Ticket 28 — [ADMIN] - Cadastrar categorias e livros

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (cadastro e edição de produtos e categorias).
**Menções na documentação:** `Desafio Técnico`, seção 4 (validação dos dados recebidos e persistência).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 07, Ticket 27.

**Descrição:** CRUD de categorias (nome) e de livros (categoria, título, autor, descrição, preço em reais no formulário, conteúdo, destaque, publicado, capa opcional). Lista com busca por título. Editar não troca o slug se o título mudar, para não quebrar link já compartilhado. Excluir livro que já está em algum `order_items` é bloqueado; nesse caso a ação oferecida é despublicar.

**Explicação:** O administrador coloca livros novos na prateleira e organiza as estantes. Se alguém já comprou um título, ele não é apagado: sai da vitrine e continua na biblioteca de quem pagou.

**Pontos de atenção:**

- Preço digitado em reais (`37,90` ou `37.90`) convertido para centavos no servidor. Validar maior que zero.
- Capa: imagem, no máximo 2 MB, guardada no disco público. Trocar capa apaga o arquivo anterior.
- `content` obrigatório, porque a leitura da plataforma depende dele.
- Validação no Form Request. A página Vue só espelha os erros.
- Slug único na criação. Na edição, slug estável.
- Markdown/texto gravado como conteúdo. A sanitização da leitura já está no Ticket 22.

**DoD:**

- Admin cria categoria, cria livro publicado e o livro aparece na vitrine.
- Livro despublicado some da vitrine e continua legível para quem já comprou.
- Preço `37,90` vira `3790` no banco.
- Excluir livro já comprado é recusado com mensagem clara.
- Cliente não acessa essas rotas.

---

## Ticket 29 — [ADMIN] - Acompanhar pedidos, pagamentos e usuários

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (usuários, pedidos e histórico de pagamentos).
**Menções na documentação:** `Desafio Técnico`, seção 4 (consulta de pagamentos) e seção 6 (histórico de transações).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 19, Ticket 27.

**Descrição:** Lista de pedidos com filtro por status, abrindo o detalhe com itens e tentativas de pagamento (aqui o admin pode ver `gateway_id` e o status; continuar sem despejar o JSON cru na tela, mas pode haver um bloco recolhido para suporte). Lista de usuários com nome, e-mail, se é admin e data de cadastro. Ação única sobre usuário: promover ou remover admin, impossível sobre a própria conta.

**Explicação:** Quando uma compra trava, a pessoa que opera a loja abre o pedido e vê as tentativas de Pix. Ela também vê quem tem conta e quem pode entrar no painel, sem editar a vida do cliente.

**Pontos de atenção:**

- Admin não marca pedido como pago na mão. Status de pagamento só muda pela confirmação do gateway (Tickets 16 e 18). Evita "pago" sem lastro.
- Não permitir que o único admin se rebaixe se isso for a própria sessão. Regra simples: ninguém altera o próprio `is_admin`.
- Paginar as duas listas.
- Não exibir hash de senha nem token.

**DoD:**

- Admin filtra pedidos pendentes e abre o detalhe com as tentativas.
- Admin promove outro usuário e esse usuário passa a entrar no `/admin`.
- Admin não consegue tirar o próprio acesso por essa tela.
- Não há botão de "marcar como pago".

---

## Ticket 30 — [ADMIN] - Ver vendas e afiliados

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (indicadores de vendas e gerenciamento de afiliados) e seção 2 (histórico do afiliado).
**Menções na documentação:** `Desafio Técnico`, seção 7 (funcionalidade) e seção 6 (histórico).

**Status:** Concluído
**Prioridade:** média. Primeiro candidato a corte se o prazo apertar.
**Depende de:** Ticket 26, Ticket 27.

**Descrição:** Na home do admin, além dos quatro números do Ticket 27, mostrar os últimos 5 pedidos pagos. Página `/admin/afiliados` com uma linha por usuário que tenha clique ou comissão: código, quantidade de cliques, quantidade de pedidos atribuídos pagos e soma das comissões. Sem edição de percentual por pessoa. O percentual continua global na config.

**Explicação:** O administrador enxerga se as indicações estão gerando vendas e quanto de comissão isso representa. Não é um financeiro completo: é um quadro simples para acompanhar.

**Pontos de atenção:**

- Somar só comissões `confirmed` e só pedidos `paid`.
- Usuário sem nenhum clique e sem comissão não precisa aparecer.
- Consultas agregadas com `withCount` / `sum`, sem carregar todas as linhas na memória.
- Este ticket não implementa payout, nota fiscal nem gráfico elaborado. Uma tabela basta.

**DoD:**

- A home do admin lista os cinco pagamentos mais recentes.
- A página de afiliados mostra cliques, vendas pagas e total de comissão de quem indicou.
- Usuário sem atividade de afiliado não polui a lista.
- Não existe ação de saque ou de mudança de percentual na tela.
