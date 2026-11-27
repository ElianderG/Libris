# Épico 11 — API da conta

**Objetivo:** o usuário entra na conta, gera uma chave nas configurações e, com essa chave, consulta só os dados comerciais da própria conta.
**Prioridade:** obrigatória. Cobre o item "desenvolvimento de uma API" e o diferencial "documentação da API".
**Quando:** depois que existirem pedidos pagos. O bloco de vendas fica completo quando o épico 08 existir; sem ele, a resposta de vendas vem zerada.

A criação do Pix continua no checkout, no servidor. A chave não paga, não cria pedido e não é usada pelas telas. Ela serve para um sistema de fora puxar os dados do dono dela.

Isto não é a API do Juntos Se Faz. O Ticket 14 é o Libris chamando o gateway.

---

## Ticket 34 — [CONTA] - Gerar a chave nas configurações

**Menções na transcrição:** Não há transcrição. Origem: decisão de produto nesta conversa — usuário autenticado gera uma chave e, a partir dela, acessa a própria conta.
**Menções na documentação:** `Desafio Técnico`, seção 4 (desenvolvimento de uma API e armazenamento seguro das credenciais) e seção 6 (autenticação e boas práticas de segurança).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 05.

**Descrição:** Página `/minha-conta/configuracoes`, só com sessão. Nela o usuário gera uma chave, vê o valor completo uma única vez e, depois, só um prefixo mascarado. Pode revogar. Gerar de novo revoga a anterior na hora: cada conta tem uma chave ativa.

Tabela `api_keys`: `user_id`, `prefix`, `key_hash`, `last_used_at` nulo, `revoked_at` nulo, timestamps. A chave em claro tem o formato `libris_` mais 40 caracteres aleatórios. No banco fica só o hash SHA-256 e o prefixo (os primeiros 12 caracteres), para a tela mostrar `libris_ab12cd…`.

**Explicação:** É o mesmo gesto de criar uma senha de aplicativo. A pessoa liga o acesso externo na própria conta e guarda a chave no sistema que vai consultar os dados. Se a chave vazar, ela volta nas configurações e invalida a antiga.

**Pontos de atenção:**

- A chave completa não volta em listagem, log, sessão flash permanente nem HTML depois do primeiro response. O flash de "copiar agora" é o único momento.
- Não guardar a chave em claro, nem criptografada de forma reversível. Perdeu, gera outra.
- A página de configurações exige login. A chave ainda não chama a API; isso é o Ticket 35.
- Revogar preenche `revoked_at`. Não apagar a linha, para saber que existiu.
- Não existe chave de administrador com poder extra. Admin que gerar chave vê os dados da conta dele, não os da loja inteira.

**DoD:**

- Usuário logado gera uma chave, vê o valor uma vez e, ao recarregar, vê só o prefixo.
- Gerar outra chave faz a primeira deixar de ser a ativa.
- Revogar deixa a conta sem chave ativa.
- O banco não contém o texto completo da chave.
- Visitante não abre a página.

---

## Ticket 35 — [API] - Consultar compras e vendas da própria conta

**Menções na transcrição:** Não há transcrição. Origem: decisão de produto nesta conversa — com a chave, o usuário puxa os dados da própria conta, em especial os de venda.
**Menções na documentação:** `Desafio Técnico`, seção 4 (API, consulta de pagamentos, validação e tratamento de erros) e seção 6 (histórico de transações e documentação da API).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 19, Ticket 34. O conteúdo de `vendas` depende do Ticket 26; sem esse ticket a lista vem vazia e o restante da API funciona.

**Descrição:** Rotas somente leitura em `routes/api.php`. Autenticação exclusiva por `Authorization: Bearer libris_…`. O middleware calcula o hash, acha a chave ativa e carrega o usuário. Sessão do navegador não autentica essas rotas. Cada resposta filtra por esse `user_id`. Atualizar `last_used_at`.

| Método | Caminho | Devolve |
|---|---|---|
| `GET` | `/api/v1/me` | Nome, e-mail, código de afiliado e os totais de compras e vendas |
| `GET` | `/api/v1/me/compras` | Pedidos do usuário, com itens e status de cada tentativa de pagamento |
| `GET` | `/api/v1/me/compras/{order}` | Um pedido, se for do dono da chave |
| `GET` | `/api/v1/me/vendas` | Cliques, pedidos atribuídos a ele e comissões confirmadas |

Compra é o que ele pagou. Venda é o que outra pessoa pagou a partir do link dele. Os dois blocos existem porque os dois são dados da conta; o nome "venda" fica no segundo.

Valores em centavos. Paginação de 20 itens. Sem chave, chave errada ou revogada: 401 `{ "message": "Chave inválida." }`. Pedido de outra pessoa ou inexistente: 404. Nenhum endpoint aceita corpo.

**Explicação:** Com a chave na mão, um sistema externo pergunta "o que esta conta comprou e o que ela vendeu". A resposta só fala daquela pessoa. Não dá para criar cobrança, ler livro nem olhar a conta do vizinho.

**Pontos de atenção:**

- A query parte sempre do usuário da chave. Não aceitar `user_id` na URL nem na query string.
- Não devolver conteúdo do livro, capa em arquivo, `raw_request`, `raw_response` nem qualquer segredo do gateway.
- `vendas` só inclui pedido atribuído cujo pagamento está `paid`, mais a comissão daquele pedido. Pedido pendente de terceiro não aparece como venda.
- Throttle de 60 requisições por minuto por chave.
- A chave não vai para o JavaScript da loja. O botão "Atualizar status" continua sendo a página do Ticket 16.
- Erro de autenticação é sempre a mesma frase. Não dizer se o prefixo existe.

**DoD:**

- curl com a chave ativa recebe as compras daquela conta, inclusive o status do Pix.
- curl com a mesma chave em `/vendas` recebe só indicações e comissões dela.
- Pedido de outro usuário responde 404.
- Chave revogada responde 401 e não atualiza `last_used_at`.
- Sem o épico 08, `/vendas` responde listas vazias e totais zero, com HTTP 200.
- A resposta não contém o texto do livro nem o payload cru do gateway.

---

## Ticket 36 — [DOCUMENTAÇÃO] - Documentar a chave e os dados devolvidos

**Menções na transcrição:** Não há transcrição. Origem: decisão de produto nesta conversa.
**Menções na documentação:** `Desafio Técnico`, seção 6 (documentação da API) e seção 10 (README com as funcionalidades e a integração).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 35.

**Descrição:** `docs/api.md` descreve como gerar a chave nas configurações e como chamar os quatro endpoints. Para cada um: método, caminho, cabeçalho, exemplo de sucesso, 401 e 404. Um curl de exemplo usa `Bearer SEU_TOKEN`. O texto diz que a API lê a conta e não cria pagamento, e aponta o checkout como o lugar em que o Pix é criado. O README (Ticket 33) aponta para esse arquivo.

**Explicação:** É o manual para quem recebeu a chave. A pessoa avaliadora segue o passo a passo, gera a chave na tela e repete a consulta no terminal, sem abrir o controller.

**Pontos de atenção:**

- O documento descreve o que o Ticket 35 faz de fato. Se o bloco de vendas estiver vazio por falta do épico 08, o texto diz isso.
- Não publicar uma chave gerada de verdade.
- Não documentar rota de escrita. Ela não existe.

**DoD:**

- `docs/api.md` cobre a geração da chave e os quatro endpoints.
- Seguindo o documento, dá para gerar a chave na interface e ler `/api/v1/me` no curl.
- O README aponta para `docs/api.md`.
