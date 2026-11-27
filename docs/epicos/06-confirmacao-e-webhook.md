# Épico 06 — Confirmação e webhook

**Objetivo:** quando o Pix confirma, o pedido fecha e o livro fica disponível, seja por aviso do gateway ou por consulta.
**Prioridade:** alta.
**Antes de:** biblioteca e comissão, que consomem o pedido pago.

---

## Ticket 18 — [PAGAMENTO] - Receber o webhook do gateway

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 4 (webhook com prioridade alta).
**Menções na documentação:** `Desafio Técnico`, seção 6 (integração com webhooks, se o gateway disponibilizar) e seção 4 (tratamento de erros).

**Status:** Pendente
**Prioridade:** alta.
**Depende de:** Ticket 14, Ticket 15.

**Descrição:** Rota `POST /webhooks/juntos-se-faz`, fora do CSRF, sem autenticação de sessão. Confirmar na documentação o formato do corpo, o cabeçalho de assinatura e os eventos. Validar a assinatura com `JUNTOS_SE_FAZ_WEBHOOK_SECRET`. Achar o `payments` pelo id externo ou pela referência enviada no Ticket 15. Responder 200 rápido quando o evento for aceito ou for repetido. Responder 401 se a assinatura não bater.

Se a documentação do sandbox não tiver webhook, o ticket muda de forma, não de objetivo: registrar isso no README e manter a confirmação apenas pela consulta do Ticket 16. Não inventar um webhook que o gateway não envia.

**Explicação:** O gateway pode avisar a loja sozinho quando o Pix entra, em vez de esperar a pessoa apertar "Atualizar". O aviso só é aceito se for mesmo deles. Um aviso repetido não pode cobrar ou liberar duas vezes.

**Pontos de atenção:**

- Comparar assinatura com função de tempo constante.
- Rejeitar corpo sem o identificador que liga ao nosso `payments`.
- Evento repetido do mesmo status é sucesso silencioso (200), sem segunda gravação de efeito.
- Não confiar só no valor que vem no webhook. Conferir com uma consulta ao gateway (Ticket 14) antes de marcar pago, se a documentação permitir essa consulta por id.
- Logar evento e id, sem gravar o segredo.
- Limitar a rota (throttle) para não aceitar rajada anônima.

**DoD:**

- Requisição assinada corretamente atualiza o pagamento conforme o evento.
- Assinatura errada recebe 401 e não altera o banco.
- O mesmo evento entregue duas vezes deixa um único efeito.
- Se o gateway não oferecer webhook, o README diz isso e a consulta manual continua fechando o pedido pelo Ticket 19.

---

## Ticket 19 — [PAGAMENTO] - Confirmar o pedido uma única vez

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (acesso aos produtos depois da compra) e seção 2 (comissão só após confirmação).
**Menções na documentação:** `Desafio Técnico`, seção 4 (consulta de pagamentos e persistência) e seção 6 (histórico de transações).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 16. O Ticket 18 chama esta mesma rotina.

**Descrição:** Serviço `ConfirmPayment` único, usado pela consulta e pelo webhook. Dentro de transação e com lock na linha do pagamento: se o status novo é `paid` e o pedido ainda não está `paid`, gravar `paid_at`, marcar pagamento e pedido como `paid`. Se o status novo é terminal de falha (`failed`, `canceled`, `expired`) e o pagamento ainda está `pending`, atualizar só o pagamento; o pedido permanece `pending_payment`. Disparar um evento interno `PaymentConfirmed` para a comissão (Épico 08) escutar. A biblioteca não precisa escutar evento: ela lê pedido pago.

**Explicação:** Existe um único portão que declara "esta compra está paga". Tanto o botão de atualizar quanto o aviso do gateway passam por ele. Assim o livro não libera duas vezes e uma falha de Pix não apaga o pedido.

**Pontos de atenção:**

- Idempotência: confirmar um pagamento já `paid` não altera `paid_at` nem republica o evento.
- Não marcar o pedido como pago se o valor consultado no gateway divergir do `amount_cents` local. Logar e deixar pendente.
- Falha de um listener (comissão) não pode desfazer o pagamento já commitado. O listener roda depois do commit (`afterCommit`).
- Não há estado "pago pela metade". Ou a transação inteira grava, ou nada grava.

**DoD:**

- Consulta que retorna pago muda pedido e pagamento para `paid` e preenche `paid_at`.
- Segunda confirmação não muda `paid_at` e não dispara o evento de novo.
- Valor divergente não marca como pago.
- Falha terminal atualiza o pagamento e deixa o pedido aguardando outra tentativa.
