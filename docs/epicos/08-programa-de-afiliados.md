# Épico 08 — Programa de afiliados

**Objetivo:** um cliente compartilha um livro, a compra feita a partir desse link fica atribuída a ele e, quando o Pix confirma, nasce uma comissão. Sem saque e sem transferência.
**Prioridade:** média no edital, alta como diferencial da vaga. Fazer depois do fluxo de compra estar sólido.
**Antes de:** a parte de afiliados do painel.

---

## Ticket 23 — [AFILIADOS] - Publicar o link de indicação

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 2 (link exclusivo com `?ref=`).
**Menções na documentação:** `Desafio Técnico`, seção 6 (diferenciais; autenticação já usada aqui) e seção 5 (regras de negócio a critério do candidato).

**Prioridade:** média.
**Depende de:** Ticket 05, Ticket 10.

**Descrição:** Na página do livro, se houver usuário logado, mostrar o link `{url do livro}?ref={affiliate_code}` com botão de copiar. Página `/minha-conta/afiliado` repete o código e explica, em uma frase, que a comissão incide sobre compras de outras pessoas feitas por aquele link. O código em si já foi gerado no Ticket 05.

**Explicação:** Cada leitor ganha um jeito simples de indicar um livro. O link é o livro normal, com o código da pessoa no final. Ainda não calcula dinheiro; só deixa o link pronto para copiar.

**Pontos de atenção:**

- O código na URL é o `affiliate_code`, não o id do usuário.
- Visitante não vê link de indicação, vê um convite curto para criar conta se quiser indicar.
- Copiar usa a URL absoluta atual, para funcionar em local e em deploy.

**DoD:**

- Cliente logado copia um link que abre o livro certo com `?ref=` igual ao código do banco.
- Visitante não recebe código de outra pessoa por engano.
- A página da conta mostra o próprio código.

---

## Ticket 24 — [AFILIADOS] - Registrar o clique

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 2 (identificar o afiliado e registrar a origem) e seção 3 (`AffiliateClicks`).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência e validação).

**Prioridade:** média.
**Depende de:** Ticket 23.

**Descrição:** Migration `affiliate_clicks`: `affiliate_user_id`, `product_id` nulo, `token` único (o valor do cookie), timestamps. Ao abrir qualquer página da loja com `?ref=` válido, gravar o clique uma vez por token, guardar cookie `libris_ref` (httpOnly, 7 dias, valor = token) e seguir a navegação normal. `ref` desconhecido é ignorado, sem mensagem de erro. Novo `ref` válido substitui o cookie anterior (último clique).

**Explicação:** Quando alguém abre o link indicado, a loja anota, em silêncio, quem indicou. Esse bilhete dura uma semana. Se a pessoa clicar no link de outro amigo depois, vale o mais recente.

**Pontos de atenção:**

- Não gravar IP cru. Se precisar de uma pista técnica, guardar só um hash com sal da aplicação, ou não gravar nada além de user agent truncado. Para este desafio, user agent truncado em 255 caracteres basta.
- Cookie httpOnly e `SameSite=Lax`. Não acessível ao Vue.
- Código inexistente não cria clique e não apaga um cookie bom já existente.
- O próprio afiliado, se já estiver logado e o `ref` for o dele, não grava clique de autoindicação.
- Não redirecionar nem trocar a URL na cara da pessoa além de poder remover o `ref` depois de gravar, para o link compartilhado seguinte não carregar o código sem querer. Remover o parâmetro com redirect é aceitável.

**DoD:**

- Abrir um livro com `ref` válido cria um clique e um cookie de 7 dias.
- `ref` inválido não cria linha e a página do livro abre normal.
- Um segundo `ref` válido troca o cookie.
- Usuário logado não gera clique do próprio código.

---

## Ticket 25 — [AFILIADOS] - Atribuir a indicação ao pedido

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 2 (associar a indicação ao pedido).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência e validação dos dados recebidos).

**Prioridade:** média.
**Depende de:** Ticket 12, Ticket 24.

**Descrição:** No `PlaceOrder`, se o cookie apontar para um clique cujo afiliado não é o comprador, preencher `orders.affiliate_user_id` e `orders.affiliate_code` com os dados daquele momento. Se não houver cookie, ou se o afiliado for o próprio comprador, gravar nulo. A comissão ainda não é criada aqui.

**Explicação:** Na hora de fechar o pedido, a loja olha o bilhete guardado no navegador e anota, no pedido, quem indicou aquela compra. Indicar a si mesmo não conta.

**Pontos de atenção:**

- Copiar o código para o pedido. Se o afiliado mudar de código no futuro, a venda antiga continua atribuída ao código da época. Neste projeto o código não muda; a cópia ainda assim protege o histórico.
- Atribuição é definida no servidor pelo cookie. Um campo `ref` escondido no formulário, se existir, é ignorado.
- Não atribuir pedido quando o clique é mais velho que `AFFILIATE_COOKIE_DAYS`.

**DoD:**

- Compra com cookie válido de outra pessoa grava o afiliado no pedido.
- Compra sem cookie grava afiliado nulo.
- Cookie do próprio comprador grava afiliado nulo.
- O formulário não consegue forçar outro afiliado.

---

## Ticket 26 — [AFILIADOS] - Calcular a comissão quando o Pix confirma

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 2 (calcular e registrar a comissão depois da confirmação; sem saque).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência) e seção 6 (histórico de transações, aplicado também ao afiliado).

**Prioridade:** média.
**Depende de:** Ticket 19, Ticket 25.

**Descrição:** Migration `commissions`: `affiliate_user_id`, `order_id` único, `payment_id`, `base_cents`, `percent`, `amount_cents`, `status` (`confirmed`), timestamps. Listener de `PaymentConfirmed`: se o pedido tem afiliado e ainda não há comissão para aquele `order_id`, calcular `amount_cents = base_cents * percent / 100` com arredondamento para baixo (inteiro). Percentual lido da config. Na página `/minha-conta/afiliado`, listar as comissões do usuário: data, pedido, base e valor. Não implementar saldo sacável, transferência ou estorno de comissão.

**Explicação:** O dinheiro da indicação só existe depois que o Pix realmente entra. A conta é uma porcentagem fixa da compra, anotada no histórico do afiliado. Não há botão de sacar: o desafio mostra o rastreio e a conta, não um banco.

**Pontos de atenção:**

- Única comissão por pedido, garantida por índice único em `order_id`.
- Pedido sem afiliado não cria linha.
- Reentrega do webhook não duplica, porque o listener também é idempotente.
- Arredondar para baixo em centavos. R$ 10,00 com 10% são 100 centavos. R$ 37,90 com 10% são 379 centavos, não 379,5.
- A comissão não altera o valor pago pelo cliente.
- Não criar comissão em pagamento `failed`.

**DoD:**

- Pedido atribuído, ao ser confirmado, gera uma comissão com o percentual configurado.
- O afiliado vê essa linha na área dele.
- Confirmar de novo não cria segunda comissão.
- Pedido sem afiliado confirmado não gera comissão.
- Não existe fluxo de saque.
