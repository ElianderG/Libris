# Épico 12 — Pagsmile

**Objetivo:** o mesmo checkout Pix também cobra no sandbox da Pagsmile. A tela, o pedido e a confirmação continuam os que já existem.
**Prioridade:** diferencial. O edital exige um gateway; os dois contam a mais (`Desafio Técnico`, seção 6).
**Antes de:** nada do caminho obrigatório. Entra depois do Ticket 19, porque o aviso da Pagsmile usa a mesma confirmação.

Leia a documentação oficial antes de fixar URL, nomes de campos e unidade do valor: [Pix no Brasil](https://docs.pagsmile.com/reference/payin/submit-a-payin/brazil/pix) e a [referência de Payin](https://docs.pagsmile.com/). O material público mostra sandbox em `https://gateway-test.pagsmile.com`, `POST /trade/pay` com `method: PIX`, `order_amount` em texto, `order_currency: BRL`, `Authorization: Basic` de `app_id:security_key`, e resposta com `code: 10000`, `trade_no`, `trade_status: PROCESSING` e `qr_code`. A consulta pública cita `/trade/query`. O aviso traz o cabeçalho `Pagsmile-Signature` (`t` e `v2` em HMAC-SHA256 do corpo cru) e espera HTTP 200 com o corpo `success`. Confirme isso na documentação. O código segue a documentação, não este parágrafo.

Não usar a URL de produção. Não gravar `app_id`, `security_key` nem o exemplo de Authorization que a documentação publica. CPF não entra em log, exceção nem `raw_request`.

---

## Ticket 37 — [PAGAMENTO] - Criar o cliente da Pagsmile

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (Payments como tentativa de liquidação).
**Menções na documentação:** `Desafio Técnico`, seção 3 (gateway Pagsmile, sandbox) e seção 6 (integração com ambos os gateways).

**Status:** Concluído
**Prioridade:** diferencial.
**Depende de:** Ticket 14.

**Descrição:** `PagsmileClient` com o HTTP client do Laravel. Métodos: criar Pix e consultar pagamento. `app_id`, `security_key` e a base URL vêm de `config/services.php`, com chaves vazias no `.env.example` (`PAGSMILE_BASE_URL`, `PAGSMILE_APP_ID`, `PAGSMILE_SECURITY_KEY`). A base de exemplo aponta para `https://gateway-test.pagsmile.com`. Timeout de 10 segundos, sem `retry`. Basic auth montado no cliente. 4xx, 5xx, corpo sem `trade_no` e `trade_status`, `code` diferente de sucesso ou timeout viram `GatewayException`, com a mesma frase genérica já usada na tela. Traduzir `trade_status` para `pending`, `paid`, `failed`, `canceled` e `expired` numa função só. Status desconhecido vira `pending` e gera log de alerta.

**Explicação:** É o segundo telefone. O checkout continua pedindo "cobra este valor"; este cliente é quem sabe falar com a Pagsmile.

**Pontos de atenção:**

- Nenhum controller usa `Http::` direto contra a Pagsmile.
- O valor sai de centavos só neste cliente, no formato que a documentação confirmar. O material público pede `order_amount` como texto.
- Recusar a chamada se a base URL, o `app_id` ou a `security_key` estiverem vazios, com erro claro no log e sem HTTP.
- Logar status HTTP e `out_trade_no` ou `trade_no`. Não logar corpo, cabeçalho Authorization nem CPF.
- `out_trade_no` é o id do `payments`, para o aviso achar a linha. Não reutilizar o id de uma tentativa anterior: a Pagsmile recusa `out_trade_no` repetido.

**DoD:**

- Teste com `Http::fake` cobre criação com sucesso, consulta com sucesso, timeout e HTTP 400.
- O `security_key` não aparece na exceção nem no log desses testes.
- Status desconhecido não lança erro para o usuário.

---

## Ticket 38 — [PAGAMENTO] - Cobrar o pedido na Pagsmile

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (checkout Pix).
**Menções na documentação:** `Desafio Técnico`, seção 4 (criação de pagamentos, persistência e validação) e seção 6 (ambos os gateways).

**Status:** Pendente
**Prioridade:** diferencial.
**Depende de:** Ticket 15, Ticket 37.

**Descrição:** O checkout ganha a escolha do gateway, com Juntos Se Faz marcado por padrão. A escolha fica em `orders.gateway` (`juntos_se_faz` ou `pagsmile`). A migration nasce pelo Artisan. `payments` ganha a mesma coluna, copiada da cobrança, para a consulta e o aviso saberem qual cliente chamar. Pedidos e pagamentos que já existem ficam `juntos_se_faz`.

`ChargeOrder` usa o cliente da escolha. Com Pagsmile, o checkout exige CPF de 11 dígitos, além do nome e do e-mail da conta. O CPF vai só no corpo enviado à Pagsmile. `raw_request` guarda método, valor, moeda e `out_trade_no`, sem o documento. No sucesso, `gateway_id` recebe `trade_no` e `raw_response` guarda `qr_code`. Se a resposta trouxer `qr_code_img` ou `qr_code_url`, a página mostra essa imagem. Não gerar QR próprio. Falha do gateway continua deixando o pagamento `failed` e o pedido `pending_payment`. **Gerar novo Pix** repete o gateway já gravado no pedido.

**Explicação:** A pessoa escolhe onde o Pix de teste nasce. O restante da compra não muda: um pedido, várias tentativas, uma pendente por vez, total lido do banco.

**Pontos de atenção:**

- Ignorar valor, nome de gateway e CPF que não pertençam à escolha. Juntos Se Faz não pede CPF.
- Só o dono cobra. Pedido que não está `pending_payment` não gera outra tentativa.
- `amount` continua sendo `orders.total_cents`. Abaixo do mínimo que a documentação fixar, a tentativa falha com a frase genérica, sem estourar na tela.
- `buyer_id` é o id do usuário. `subject` é o título do primeiro item, cortado no limite da documentação.
- `notify_url` aponta para a rota do Ticket 39, a partir de `APP_URL`.

**DoD:**

- Escolher Pagsmile grava o pedido e o pagamento com esse gateway e preenche `gateway_id` quando o sandbox responde.
- CPF ausente ou curto não chama a Pagsmile.
- O CPF não aparece em `raw_request`, log ou exceção.
- Juntos Se Faz continua cobrando como antes quando é a escolha.
- Nova tentativa no mesmo pedido não troca de gateway e não apaga a linha anterior.

---

## Ticket 39 — [PAGAMENTO] - Consultar e receber o aviso da Pagsmile

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (status do pagamento).
**Menções na documentação:** `Desafio Técnico`, seção 4 (consulta e exibição do status) e seção 6 (webhooks, se o gateway disponibilizar).

**Status:** Pendente
**Prioridade:** diferencial.
**Depende de:** Ticket 16, Ticket 19, Ticket 38.

**Descrição:** `RefreshPayment`, ao abrir a página e no botão **Atualizar status**, chama o cliente gravado na tentativa. Pagsmile usa a consulta por `out_trade_no` ou `trade_no`, como a documentação do detalhe do pagamento definir. A página segue mostrando status em português, valor, data, id externo e o copia-e-cola. Erro de rede mantém o último status.

Rota `POST /webhooks/pagsmile`, fora do CSRF e sem sessão. Validar `Pagsmile-Signature` com HMAC-SHA256 do corpo cru, usando a `security_key`, em comparação de tempo constante. Não reidratar o JSON antes de assinar. Achar o pagamento pelo `out_trade_no`. Conferir o valor com `amount_cents` antes de aceitar pago. Status novo passa por `ConfirmPayment`. Responder 200 com o corpo que a documentação pede (o material público aceita `success`). Assinatura inválida responde 401 e não grava. Aviso repetido do mesmo status responde 200 sem segundo efeito.

**Explicação:** A pessoa acompanha o Pix da Pagsmile no mesmo lugar do outro gateway. Se o sandbox avisar a loja, o pedido fecha pela mesma porta que o Juntos Se Faz, uma vez só.

**Pontos de atenção:**

- Não marcar pago porque a tela abriu ou porque `trade_status` veio `PROCESSING`.
- Não confiar só no valor do aviso. Se a consulta existir, confirmar com ela antes de pagar, como no Ticket 18.
- Logar `trade_no` e o status, sem o corpo se ele puder trazer CPF ou chave.
- Limitar a rota para não aceitar rajada anônima.
- Se a documentação não tiver aviso, o README diz isso e a consulta manual continua fechando o pedido pelo Ticket 19. Não inventar webhook.

**DoD:**

- Consulta da Pagsmile muda o status mostrado na página.
- Assinatura errada recebe 401 e não altera o banco.
- Aviso pago, com valor igual ao pedido, confirma uma vez. A repetição não muda `paid_at`.
- Valor divergente não marca como pago.
- Tentativa do Juntos Se Faz não é atualizada por essa rota.
