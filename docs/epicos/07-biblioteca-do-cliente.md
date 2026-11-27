# Épico 07 — Biblioteca do cliente

**Objetivo:** quem pagou encontra a compra, entende o status e lê o livro na própria plataforma.
**Prioridade:** alta.
**Antes de:** afiliados e admin. O fluxo do comprador fecha aqui.

---

## Ticket 20 — [BIBLIOTECA] - Listar o histórico de compras

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (histórico de compras e status dos pagamentos).
**Menções na documentação:** `Desafio Técnico`, seção 6 (histórico de transações) e seção 4 (consulta de pagamentos e exibição de status).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 19.

**Descrição:** Página `/minha-conta/compras` do usuário logado. Uma linha por pedido: data, livros, total, status do pedido e status da última tentativa de pagamento. Link para o detalhe. Pedidos de outros usuários não entram na query (`where user_id`).

**Explicação:** É o extrato da pessoa. Ela olha e sabe o que comprou, o que já foi pago e o que ainda está esperando o Pix.

**Pontos de atenção:**

- "Última tentativa" é a de maior `id` ou `created_at`, não um status escolhido no front.
- Pedido `pending_payment` continua visível, com atalho para a tela do Pix (Ticket 16).
- Lista vazia explica que as compras aparecem ali e leva ao catálogo.
- Ordenar da compra mais recente para a mais antiga.

**DoD:**

- O cliente vê só os próprios pedidos, com valor e status corretos.
- Um pedido pendente leva de volta ao Pix.
- Outro usuário, mesmo digitando o endereço, não vê essa lista misturada.

---

## Ticket 21 — [BIBLIOTECA] - Detalhar a transação

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (detalhes de cada transação).
**Menções na documentação:** `Desafio Técnico`, seção 4 (exibição do status e consulta de pagamentos).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 20.

**Descrição:** Página `/minha-conta/compras/{order}` com os itens (título e preço congelados), total, linha do tempo das tentativas de pagamento (status, data, id do gateway) e, se pago, o caminho para ler cada livro. 404 para pedido de outra pessoa.

**Explicação:** É o comprovante da compra. Dá para ver cada livro, quanto custou naquele dia e o que aconteceu com cada tentativa de Pix.

**Pontos de atenção:**

- Mostrar o preço do item do pedido, não o preço atual da prateleira.
- Não exibir `raw_request` nem `raw_response`. Isso é auditoria técnica, fica no admin se fizer falta.
- Se o livro for despublicado depois da compra, o comprador continua vendo o título e o acesso. A vitrine é que esconde.

**DoD:**

- O dono vê itens, total e todas as tentativas.
- O preço permanece o da compra mesmo depois de o produto mudar.
- Acesso de outro usuário responde 404.

---

## Ticket 22 — [BIBLIOTECA] - Ler o livro na plataforma

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (acessar os livros na plataforma, sem download manual).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência) e seção 7 (experiência do usuário).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 10, Ticket 19.

**Descrição:** Página `/biblioteca` com os livros distintos que o usuário possui em pedidos `paid`. Página `/biblioteca/{slug}` renderiza o `content` completo, com título e autor, só se existir item daquele produto num pedido pago do usuário. Sem botão de download. A prévia pública do Ticket 10 continua cortada.

**Explicação:** Comprar coloca o livro na estante da pessoa. Ela abre e lê ali mesmo. Quem não pagou vê só a prévia na loja.

**Pontos de atenção:**

- A autorização é uma query, não uma flag no front: pedido do usuário, status `paid`, item com aquele `product_id`.
- Comprar duas vezes o mesmo livro mostra uma estante só.
- Conteúdo renderizado como texto ou Markdown sanitizado. Se for Markdown, escapar HTML cru para não executar script vindo do cadastro.
- Livro pago permanece legível mesmo se for despublicado.
- Não há tabela `library_access`. Se a query ficar pesada, aí sim vale materializar. Com o volume deste desafio, não materializar.

**DoD:**

- Depois do pagamento confirmado, o livro aparece na biblioteca e abre o texto inteiro.
- Visitante e cliente que não comprou recebem 403 nessa rota.
- O mesmo livro comprado duas vezes aparece uma vez.
- Um script colocado no conteúdo do produto não executa na página de leitura.
