# Épico 05 — Pagamentos

**Objetivo:** cada pedido consegue uma cobrança Pix no sandbox e a pessoa acompanha o status na tela.
**Prioridade:** obrigatória. Junto com o catálogo, é o núcleo da avaliação (25% integração + parte de funcionalidade).
**Antes de:** webhook, biblioteca e comissão.

Leia a documentação oficial do Juntos Se Faz antes de fixar URL, nomes de campos e unidade do valor. O material público mostra `POST /api/v1/payments` com `amount`, `currency: BRL` e `method: pix`, resposta com `id`, `status: pending`, `environment: staging` e `simulated: true`, autenticação Bearer. Confirme isso na documentação logada. O código deve seguir a documentação, não este parágrafo.

---

## Ticket 14 — [PAGAMENTO] - Criar o cliente do gateway

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (Payments como tentativa de liquidação).
**Menções na documentação:** `Desafio Técnico`, seção 3 (gateway Juntos Se Faz, sandbox) e seção 4 (integração, tratamento de erros e exceções).

**Prioridade:** obrigatória.
**Depende de:** Ticket 03.

**Descrição:** Classe `JuntosSeFazClient` usando o HTTP client do Laravel. Métodos previstos: criar pagamento e consultar pagamento por id externo. Token e base URL vêm da config. Timeout curto (10s). Qualquer resposta 4xx/5xx, corpo inválido ou timeout vira exceção de domínio (`GatewayException`) com a mensagem segura para log e outra, genérica, para a tela. Logar status HTTP e id da operação, sem o token e sem o corpo se ele puder ecoar credencial.

**Explicação:** É o único telefone que fala com o gateway. O resto do sistema pede "cobra este valor" ou "como está esta cobrança" e não precisa saber de cabeçalho, URL ou JSON.

**Pontos de atenção:**

- Nenhum controller usa `Http::` direto contra o gateway.
- Header `Authorization: Bearer` montado no cliente. O token não aparece em exceção, log ou página de erro do Laravel (`APP_DEBUG` não pode vazar isso: não colocar o token na mensagem).
- Mapear status externos para os status internos (`pending`, `paid`, `failed`, `canceled`, `expired`) numa função só. Status desconhecido vira `pending` e gera log de alerta, sem quebrar a tela.
- Converter centavos para a unidade da API neste cliente, num método com nome explícito, assim que a documentação confirmar se o campo `amount` é reais ou centavos.
- Ambiente: recusar subir se a base URL estiver vazia no momento da chamada, com erro claro no log.

**DoD:**

- Teste com `Http::fake` cobre criação com sucesso, consulta com sucesso, timeout e HTTP 422.
- O token não aparece na exceção nem no log desses testes.
- Status desconhecido não lança erro para o usuário.

---

## Ticket 15 — [PAGAMENTO] - Cobrar o pedido no Pix

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (checkout Pix) e seção 3 (um pedido, várias tentativas).
**Menções na documentação:** `Desafio Técnico`, seção 4 (criação de pagamentos, persistência e validação).

**Prioridade:** obrigatória.
**Depende de:** Ticket 13, Ticket 14.

**Descrição:** Migration `payments`: `order_id`, `gateway_id` nulo, `amount_cents`, `status`, `method` (`pix`), `raw_request` e `raw_response` em JSON, `paid_at` nulo, timestamps. Serviço que recebe um pedido `pending_payment` do usuário logado, cancela tentativas `pending` anteriores daquele pedido, grava a nova linha `pending`, chama o cliente e preenche `gateway_id` e o corpo da resposta. Se a chamada falha, a linha fica `failed` e o pedido continua `pending_payment`.

**Explicação:** Apertar "Pagar" cria uma tentativa de Pix ligada àquele pedido. Se a operadora não responder, a compra não some: a pessoa pode tentar de novo, e a tentativa antiga fica marcada como encerrada para não existirem dois Pix pendentes do mesmo pedido.

**Pontos de atenção:**

- `amount_cents` do pagamento é o `total_cents` do pedido. Ignorar qualquer valor vindo do browser.
- Só o dono do pedido cria pagamento para ele. Admin não paga por essa rota.
- Pedido já `paid` não gera outra cobrança.
- Gravar o request sem segredos. Gravar a response para auditoria de sandbox.
- Enviar ao gateway um identificador nosso (`order_id` ou id do payment) no campo de referência/metadata que a documentação oferecer, para o webhook achar a linha certa.
- Chave de idempotência: se a documentação tiver cabeçalho próprio, usar o id do `payments`. Não criar duas cobranças por causa de retry automático do HTTP client (`retry` desligado nesta chamada).

**DoD:**

- Pedido pendente gera um `payments` com `gateway_id` preenchido em caso de sucesso no sandbox.
- Falha do gateway deixa o pagamento `failed`, o pedido `pending_payment` e uma mensagem na tela.
- Segunda tentativa cancela a pendência anterior e abre outra linha.
- Pedido de outro usuário, ou já pago, é recusado.

---

## Ticket 16 — [PAGAMENTO] - Mostrar o status e consultar o gateway

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (status dos pagamentos e detalhes da transação).
**Menções na documentação:** `Desafio Técnico`, seção 4 (exibição do status, consulta de pagamentos, feedback visual e tratamento de erros).

**Prioridade:** obrigatória.
**Depende de:** Ticket 15.

**Descrição:** Página `/pedidos/{order}/pagamento` do dono do pedido. Mostra status em português, valor, data e o identificador do gateway. Enquanto estiver `pending`, a página consulta o gateway ao abrir e oferece o botão "Atualizar status". Esse botão é um POST da própria página e chama o serviço de consulta. Não usa a chave de API do cliente: a chave serve para puxar dados de fora (Épico 11), e não deve aparecer no navegador. Se a documentação devolver código Pix, copia-e-cola ou QR, exibir. Se o sandbox não devolver dado de Pix e apenas `status`, a tela explica que a cobrança é simulada e mostra o status cru traduzido. Quando a consulta retorna pago, atualizar `payments` e seguir a rotina de confirmação do Ticket 19.

**Explicação:** Depois de pedir o Pix, a pessoa não fica no escuro. A tela diz se ainda está esperando, se já entrou ou se falhou, e ela pode pedir uma atualização sem recarregar no chute.

**Pontos de atenção:**

- Consultar de verdade o gateway. Não marcar como pago só porque a tela abriu.
- Dono apenas. Outro usuário recebe 404, para não revelar que o pedido existe.
- Não fazer polling agressivo. Uma consulta ao abrir e uma por clique bastam. Se for automático, intervalo de 5 segundos com limite de tentativas na página.
- Traduzir status: pendente, pago, recusado, cancelado, expirado. Cor e texto diferentes para cada um.
- QR e copia-e-cola vêm da resposta já gravada. Não gerar um Pix falso se o gateway não mandou.

**DoD:**

- A página mostra o status certo depois de uma consulta bem-sucedida.
- O botão atualizar dispara nova consulta e muda a tela se o gateway mudou o status.
- Usuário que não é o dono não vê a página.
- Erro de rede na consulta mostra aviso e mantém o último status conhecido.

---

## Ticket 17 — [PAGAMENTO] - Tratar erro e nova tentativa na interface

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (várias tentativas sem inconsistência).
**Menções na documentação:** `Desafio Técnico`, seção 4 (tratamento de erros e feedback visual) e seção 7 (segurança e validação, 10%).

**Prioridade:** obrigatória.
**Depende de:** Ticket 16.

**Descrição:** Na página do pagamento, se o status for `failed`, `canceled` ou `expired`, mostrar o que aconteceu em linguagem comum e um botão "Gerar novo Pix". Esse botão reutiliza o serviço do Ticket 15 no mesmo pedido. Listar as tentativas daquele pedido (data, status, id do gateway) em ordem decrescente, deixando claro qual está ativa.

**Explicação:** Quando o Pix não vai, a pessoa entende o motivo e consegue pedir outro sem refazer a compra do zero. Ela também vê que as tentativas antigas ficaram para trás.

**Pontos de atenção:**

- A tentativa nova só nasce se o pedido ainda está `pending_payment`.
- Não apagar tentativas antigas. Elas são o histórico.
- Mensagem de falha não repete JSON do gateway. Uma frase fixa por status, com o detalhe técnico só no log.
- O botão de nova tentativa também desabilita durante o envio.

**DoD:**

- Um pagamento `failed` exibe a explicação e gera outra tentativa no mesmo pedido.
- As duas linhas aparecem no histórico da página, uma encerrada e outra ativa.
- Pedido já pago não mostra "Gerar novo Pix".
