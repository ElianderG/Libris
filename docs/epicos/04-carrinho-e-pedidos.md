# Épico 04 — Carrinho e pedidos

**Objetivo:** a pessoa separa os livros e gera um pedido. O pedido ainda não é cobrança.
**Prioridade:** obrigatória.
**Antes de:** qualquer chamada ao gateway.

---

## Ticket 11 — [CHECKOUT] - Montar o carrinho da sessão

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (compra de produtos digitais).
**Menções na documentação:** `Desafio Técnico`, seção 4 (interface para iniciar uma transação e feedback visual).

**Status:** Concluído
**Prioridade:** obrigatória.
**Depende de:** Ticket 10.

**Descrição:** Carrinho na sessão: adicionar livro publicado, remover, listar. Quantidade de produto digital é sempre 1. Adicionar de novo o mesmo livro não duplica a linha. Página `/carrinho` com os itens, o total e o botão para seguir ao checkout. O botão da página do livro passa a funcionar.

**Explicação:** O carrinho é a sacola. A pessoa pode juntar mais de um livro antes de pagar. Como o produto é digital, não faz sentido levar duas cópias do mesmo título.

**Pontos de atenção:**

- Revalidar no servidor que o produto existe e está publicado. O id que vem do navegador não é confiável.
- Total calculado no servidor a partir do preço atual do banco, não de um valor enviado pelo browser.
- Carrinho vazio tem estado próprio, com link de volta ao catálogo.
- Visitante pode encher o carrinho sem conta. A conta será exigida ao entrar no checkout.

**DoD:**

- Dá para adicionar dois livros diferentes, ver o total correto e remover um.
- O mesmo livro não aparece duas vezes.
- Livro não publicado não entra, mesmo com POST manual.
- O total exibido bate com a soma dos `price_cents`.

---

## Ticket 12 — [CHECKOUT] - Gravar pedido e itens

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (Orders separados de Payments; OrderItems).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência, validação e criação de pagamentos — o pagamento em si é o épico 05).

**Status:** Concluído
**Prioridade:** obrigatória.
**Depende de:** Ticket 05, Ticket 11.

**Descrição:** Migrations e models.

`orders`: `user_id`, `status`, `total_cents`, `affiliate_id` nulo, `affiliate_code` nulo (preenchidos no Épico 08), timestamps.

`order_items`: `order_id`, `product_id`, `title`, `unit_price_cents`, `quantity`.

Serviço `PlaceOrder` que, dentro de uma transação de banco, cria o pedido `pending_payment` e copia título e preço de cada item. Esvazia o carrinho só depois do commit. Não chama o gateway.

**Explicação:** O pedido é o "quero comprar isto". Ele fica registrado mesmo que o Pix falhe em seguida. O preço fica fotografado no item: se o administrador mudar o valor amanhã, a compra antiga continua com o preço daquele dia.

**Pontos de atenção:**

- Copiar `title` e `unit_price_cents`. Não depender do preço vivo do produto para cobrança e histórico.
- `quantity` gravada como 1. A soma dos itens é o `total_cents`. Se não bater, a transação desfaz.
- Pedido sem item é inválido.
- Usuário só cria pedido para si. `user_id` vem da sessão, nunca do formulário.
- Status inicial único: `pending_payment`.

**DoD:**

- Confirmar o carrinho autenticado gera um pedido e itens com preço congelado.
- O carrinho fica vazio depois do sucesso.
- Falha no meio do processo não deixa pedido pela metade.
- Alterar o preço do produto depois não muda o `total_cents` do pedido já criado.

---

## Ticket 13 — [CHECKOUT] - Fechar a tela de checkout

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (checkout com Pix).
**Menções na documentação:** `Desafio Técnico`, seção 4 (formulário para iniciar a transação, validação, tratamento de erros e feedback visual).

**Status:** Concluído
**Prioridade:** obrigatória.
**Depende de:** Ticket 06, Ticket 12.

**Descrição:** Página de checkout, acessível só com conta e carrinho não vazio. Mostra os livros, o total e o pagador (nome e e-mail da conta, editáveis só como confirmação visual; a cobrança usa o usuário logado). Botão "Pagar com Pix" dispara o `PlaceOrder` e segue para a criação do pagamento (rota do Ticket 15). Se o visitante não estiver logado, vai ao login e volta ao checkout com o carrinho intacto.

**Explicação:** É o balcão. A pessoa revê o que está levando e confirma que o Pix sai no nome da conta dela. Se ainda não entrou, o sistema pede o login e não perde a sacola.

**Pontos de atenção:**

- Não pedir CPF, endereço ou cartão. O edital pede um pagamento; dados a mais aumentam superfície sem ajudar o Pix de sandbox.
- Impedir duplo clique: o botão desabilita enquanto a requisição viaja, e o servidor recusa um segundo pedido igual em janela curta (mesmos itens e mesmo usuário com outro `pending_payment` criado há poucos segundos).
- Erro de validação volta para o checkout com a mensagem ao lado do que faltou. Carrinho preservado se o pedido não nasceu.
- Texto explícito de que é um pagamento de teste, sem dinheiro real.

**DoD:**

- Visitante com carrinho é levado ao login e, depois de entrar, encontra os mesmos livros.
- Cliente vê o resumo certo e cria um pedido `pending_payment`.
- Dois cliques rápidos não geram dois pedidos.
- A tela deixa claro que o ambiente é de teste.
