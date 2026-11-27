# Épico 10 — Qualidade e entrega

**Objetivo:** o que foi construído está coberto nos pontos que o edital cobra, e o repositório explica como rodar, como o Pix funciona e onde a IA entrou.
**Prioridade:** obrigatória na documentação; alta nos testes. Pode acompanhar os épicos anteriores, mas a revisão final é o último passo.
**Antes de:** enviar o link do repositório.

---

## Ticket 31 — [QUALIDADE] - Cobrir o fluxo com testes automatizados

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 4 (testes com prioridade alta).
**Menções na documentação:** `Desafio Técnico`, seção 6 (testes automatizados) e seção 7 (organização e qualidade do código, 20%).

**Status:** Pendente
**Prioridade:** alta.
**Depende de:** Ticket 13, Ticket 15, Ticket 19 e Ticket 35. Os casos de afiliado e o bloco de vendas da API dependem do Ticket 26.

**Descrição:** Testes de feature, com `Http::fake` no gateway e banco de teste. Conjunto mínimo:

- cadastro recusa e-mail duplicado;
- vitrine não lista livro não publicado;
- checkout sem login redireciona;
- criar pedido congela o preço;
- criar pagamento manda o total do pedido e grava `gateway_id`;
- falha HTTP do gateway deixa o pagamento `failed` e o pedido pendente;
- consulta paga confirma uma vez só;
- webhook com assinatura inválida não altera nada;
- webhook válido pago confirma e a repetição não duplica;
- biblioteca nega livro não comprado e libera o comprado;
- autoindicação não gera comissão;
- comissão é criada uma vez, com arredondamento para baixo;
- chamada sem chave responde 401;
- chave revogada responde 401;
- `GET /api/v1/me/compras` devolve só pedidos do dono da chave;
- `GET /api/v1/me/compras/{order}` de outro usuário responde 404;
- `GET /api/v1/me/vendas` não inclui comissão de outro afiliado.

**Explicação:** São provas automáticas do caminho do dinheiro e do acesso ao livro. Se alguém mexer no checkout amanhã, o teste avisa antes do avaliador perceber.

**Pontos de atenção:**

- Não chamar o sandbox de verdade na suíte. Tudo com `Http::fake`.
- Não assertar HTML inteiro. Assertar status, redirect, banco e trechos curtos de texto.
- Um teste por comportamento. Nome em português ou em inglês, mas descrevendo a regra ("não confirma pagamento com valor divergente").
- Os testes de webhook só rodam se o Ticket 18 tiver implementado webhook de fato. Se o gateway não tiver webhook, substituir pelo teste da consulta.

**DoD:**

- `php artisan test` passa localmente.
- Os casos da lista que correspondem ao que foi implementado existem e falhariam se a regra fosse removida.
- A suíte não depende de token real nem de internet.

---

## Ticket 32 — [QUALIDADE] - Revisar segurança e validação

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 5 (código organizado e seguro).
**Menções na documentação:** `Desafio Técnico`, seção 4 (validação, erros, credenciais) e seção 7 (segurança e validação, 10%).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Tickets implementados até aqui. Fazer por último, com o fluxo já montado.

**Descrição:** Passada única de revisão, corrigindo o que faltar:

- Form Request em todo POST de negócio;
- policies ou checagem de dono em pedido, pagamento, biblioteca e admin;
- mass assignment bloqueado (`$fillable` enxuto; `is_admin` fora do fillable de perfil);
- CSRF ligado nas rotas de browser e ausente só no webhook, que tem assinatura;
- throttle no login e no webhook;
- erros de gateway não vazam token, SQL ou stack para a tela;
- segredos só no `.env`.

**Explicação:** É a revisão da fechadura. Confere se cada um só mexe no que é seu, se os formulários recusam lixo e se um erro do Pix não mostra a chave da conta para o visitante.

**Pontos de atenção:**

- Não adicionar pacote de segurança genérico no lugar dessa revisão.
- Testar na mão, além da suíte: abrir pedido alheio, promover a si mesmo pelo perfil, enviar preço alterado no POST do checkout.
- `APP_DEBUG=false` no `.env.example` comentado como esperado para o ambiente mostrado ao avaliador, se houver deploy. Em local pode ficar `true`, desde que o token não esteja nas mensagens de exceção (Ticket 14).

**DoD:**

- Os três testes manuais acima falham para o atacante e passam para o dono legítimo.
- Nenhum formulário de perfil altera `is_admin`.
- Uma falha forçada do gateway mostra frase amigável, sem credencial.
- `.env` não está versionado e `.env.example` não tem segredo.

---

## Ticket 33 — [DOCUMENTAÇÃO] - Escrever o README de entrega

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 5 (documentação) e seção 6 do desafio sobre IA, que precisa ser cumprida porque este projeto está sendo construído com apoio do Cursor.
**Menções na documentação:** `Desafio Técnico`, seção 8 (uso de IA, obrigatório no README), seção 9 (repositório novo, sem fork) e seção 10 (itens obrigatórios do README e prazo).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** fluxo principal funcionando (até o Ticket 22 no mínimo). Atualizar o README de novo se afiliados e admin entrarem.

**Descrição:** `README.md` na raiz, em português, cobrindo exatamente o que o edital pede:

- nome e descrição do Libris;
- objetivo da aplicação;
- tecnologias;
- como rodar local e com Docker;
- variáveis de ambiente, apontando para `.env.example`;
- gateway escolhido: Juntos Se Faz, sandbox;
- como a integração funciona: criar cobrança, consultar, webhook (ou a ressalva de que o sandbox não oferece webhook), separação entre pedido e pagamento;
- link para `docs/api.md`, deixando claro que essa é a API do Libris e que o Juntos Se Faz é o gateway consumido por ela;
- funcionalidades principais, incluindo biblioteca e, se existirem, afiliados e admin;
- uso de IA: ferramenta (Cursor, com o modelo usado na sessão), em quais etapas, o que foi gerado com auxílio, para que serviu, e como o código foi revisado e testado pelo autor;
- link do deploy, ou a frase de que não há deploy.

Incluir um parágrafo curto do fluxo de status (pedido versus pagamento) e a decisão de comissão sem saque.

**Explicação:** Este arquivo é a porta do repositório. A pessoa avaliadora precisa rodar o projeto e entender as decisões sem abrir o chat. O edital também obriga contar, com honestidade, onde a IA ajudou. Esconder isso descumpre a regra; usar IA, por si, não tira ponto.

**Pontos de atenção:**

- Não colar credencial, print com token ou URL de painel logado.
- Não descrever webhook que não foi implementado.
- A seção de IA é específica deste repositório: etapas reais, não um texto genérico. Atualizar no fim, quando estiver claro o que o autor revisou pessoalmente.
- O autor precisa conseguir explicar na entrevista o que o README afirma. Se uma parte ficou pronta sem ele ter entendido, o texto diz o que falta revisar, ou a parte é reescrita até ficar clara.
- Repositório novo na conta do autor. Sem fork.

**DoD:**

- Um terceiro segue só o README e chega na home com o seed carregado.
- Todos os itens da seção 10 do `Desafio Técnico` aparecem no README.
- A seção de IA responde às cinco perguntas da seção 8.
- Não há segredo no arquivo.
