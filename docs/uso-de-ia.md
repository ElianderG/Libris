# Uso de inteligência artificial

Registro vivo do que foi feito com auxílio de IA. A seção correspondente do `README.md` deve dizer o mesmo, quando o README existir.

O autor do projeto precisa revisar cada item e conseguir explicar a decisão na entrevista. Os registros abaixo estão marcados como `Revisado pelo autor`. Item novo entra como `Pendente de revisão do autor` até essa revisão.

## 2026-11-27 — Planejamento

- Ferramenta: Cursor, Grok 4.7
- Etapa: definição do produto e quebra em épicos, antes do Ticket 01
- Feito com auxílio: `docs/epicos/`.
- Finalidade: organizar o Libris (Laravel, Inertia, Vue, MySQL, Juntos Se Faz em sandbox) para caber no edital sem perder o registro do que a IA produziu
- Revisão: Revisado pelo autor. Nenhum código da aplicação foi gerado nesta etapa

## 2026-11-27 — Yarn e Docker

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 01 e Ticket 02, ainda no planejamento
- Feito com auxílio: ajuste em `docs/epicos/01-fundacao-e-ambiente.md`, na decisão 14 do índice e na regra de documentação
- Finalidade: fixar Yarn como gerenciador do front e manter o Docker Compose, que é diferencial do edital
- Revisão: Revisado pelo autor

## 2026-11-27 — Ticket 01, criação do projeto

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 01
- Feito com auxílio: projeto criado com `laravel new libris --vue --pest --database=mysql --no-interaction` e movido para a raiz. Ajustes depois do instalador: nome Libris, locale `pt_BR`, fuso `America/Sao_Paulo`, scripts do Composer no Yarn, pastas `app/Services/Payments` e `app/Policies`, `README.md` e `.nvmrc`
- Finalidade: iniciar o Libris com o starter oficial de Vue e Inertia
- Revisão: Revisado pelo autor. O `pest --drift` do instalador falhou e o plugin temporário foi removido. O instalador gravou SQLite mesmo com `--database=mysql`

## 2026-11-27 — Status dos tickets

- Ferramenta: Cursor, Grok 4.7
- Etapa: planejamento
- Feito com auxílio: campo **Status** em todos os tickets de `docs/epicos/`
- Finalidade: distinguir o que já foi feito do que ainda está pendente. O Ticket 01 ficou `Concluído`; os demais, `Pendente`
- Revisão: Revisado pelo autor

## 2026-11-27 — Ticket 02, Docker Compose

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 02
- Feito com auxílio: `Dockerfile`, `docker/entrypoint.sh`, `compose.yaml`, `.dockerignore` e a seção de execução do `README.md`
- Finalidade: subir PHP, MySQL 8 e o front compilado com Yarn num comando, sem gravar senha no Compose
- Revisão: Revisado pelo autor. `docker compose up` respondeu a home em `http://localhost:8080` e, depois de reiniciar, a migration acusou que o schema continuava no volume

## 2026-11-27 — Ticket 03, credenciais

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 03
- Feito com auxílio: entrada `juntos_se_faz` em `config/services.php`, `config/libris.php` para comissão e cookie, e as chaves vazias no `.env.example`
- Finalidade: o token do gateway ficar só no `.env`, com o repositório mostrando os nomes das variáveis
- Revisão: Revisado pelo autor. `config('services.juntos_se_faz')` leu URL, token e segredo vazios; nenhum token real foi gravado

## 2026-11-27 — Ticket 04, layout da loja

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 04
- Feito com auxílio: `StoreLayout`, cabeçalho, rodapé, aviso de flash, home, catálogo e sacola vazios, cores em `resources/css/app.css`
- Finalidade: a aplicação passar a parecer uma livraria e reaproveitar a mesma moldura nas páginas públicas e autenticadas
- Revisão: Revisado pelo autor. A home renderizada trouxe a marca Libris, o menu e o estado vazio; os testes de flash passaram

## 2026-11-27 — Acabamento visual da loja

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 04, refinamento visual
- Feito com auxílio: cabeçalho, rodapé, home, catálogo e sacola, com a fonte Newsreader e a paleta de papel e vinho
- Finalidade: a vitrine deixar de parecer um esqueleto e ganhar cara de livraria
- Revisão: Revisado pelo autor. A home renderizada mostrou o título, as capas reservadas e o botão do catálogo

## 2026-11-27 — Fotos da livraria

- Ferramenta: Cursor, Grok 4.7
- Etapa: preparação da página de apresentação, ainda sem montar a página
- Feito com auxílio: download e compressão das fotos em `public/images/libris/`
- Finalidade: ter imagens WebP, leves e com licença Unsplash, para a landing page
- Revisão: Revisado pelo autor. As fotos foram conferidas uma a uma; as que mostravam títulos comerciais em destaque foram descartadas

## 2026-11-27 — Modo claro e modo escuro

- Ferramenta: Cursor, Grok 4.7
- Etapa: visual da loja
- Feito com auxílio: botão de tema no cabeçalho e a ligação com o cookie que o Laravel já usava
- Finalidade: o visitante alternar entre o papel claro e o marrom escuro que já estava definido
- Revisão: Revisado pelo autor. O teste confirma que o cookie `dark` coloca a classe no HTML e o cookie `light` não coloca

## 2026-11-27 — MySQL publicado na porta 3308

- Ferramenta: Cursor, Grok 4.7
- Etapa: execução local com o MySQL do Docker
- Feito com auxílio: `docker-compose.yaml` e a seção Docker do `README.md`
- Finalidade: o `php artisan` no computador alcançar o MySQL do Compose em `127.0.0.1:3308`, sem mudar a porta 3306 usada dentro da rede do Compose. A 3307 já pertence ao MySQL de outro projeto
- Revisão: Revisado pelo autor

## 2026-11-27 — Ticket 05, cadastro

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 05
- Feito com auxílio: código de afiliado no usuário, mensagens de validação em português e textos das telas de entrar e criar conta
- Finalidade: a conta nascer com um código único e o formulário recusar e-mail repetido e senha curta em português
- Revisão: Revisado pelo autor. O teste de cadastro passou, inclusive a mensagem de senha curta

## 2026-11-27 — Ticket 06, administrador

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 06
- Feito com auxílio: coluna `is_admin`, middleware `admin`, redirecionamento depois do login, seeder condicional e páginas iniciais de biblioteca e painel
- Finalidade: cliente e administrador caírem em casas diferentes, sem o cliente conseguir se promover
- Revisão: Revisado pelo autor. Os testes cobrem 403 do cliente, entrada do admin, redirecionamento e perfil sem `is_admin`

## 2026-11-27 — Textos em português

- Ferramenta: Cursor, Grok 4.7
- Etapa: interface e mensagens
- Feito com auxílio: tradução das telas de conta, segurança, autenticação, avisos de validação e páginas de erro HTTP
- Finalidade: o visitante não encontrar texto em inglês na loja
- Revisão: Revisado pelo autor

## 2026-11-27 — Ticket 07, categorias e livros

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 07
- Feito com auxílio: migrations de `categories` e `products`, models, `ProductRules`, factories, pasta pública `covers` e `tests/Feature/CatalogSchemaTest.php`
- Finalidade: a prateleira existir no banco, com preço em centavos, slug gerado no servidor e capa restrita a um caminho relativo
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CatalogSchemaTest` passou (7 testes). No MySQL local, as migrations subiram; um livro de prova se relacionou com a categoria; apagar a categoria com livro foi recusado; os índices de `products` ficaram em `slug`, `category_id`, `is_published` e `is_featured`. O registro de prova foi apagado em seguida

## 2026-11-27 — Ticket 08, livros de exemplo

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 08
- Feito com auxílio: `CatalogSeeder`, a chamada no `DatabaseSeeder` e `tests/Feature/CatalogSeederTest.php`
- Finalidade: o `db:seed` deixar 3 categorias e 9 livros originais, oito publicados e um oculto, sem duplicar slug na segunda execução
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CatalogSeederTest` passou. No MySQL local, o seed rodou duas vezes e permaneceu em 3 categorias, 8 publicados, 4 em destaque, 1 oculto e o preço `3790`

## 2026-11-27 — Menu da conta

- Ferramenta: Cursor, Grok 4.7
- Etapa: cabeçalho e rodapé da loja
- Feito com auxílio: `StoreAccountMenu.vue`, `StoreHeader.vue` e `StoreFooter.vue`
- Finalidade: quem está logado abre painel, biblioteca, configurações e sair pelo avatar, e o rodapé deixa de oferecer Entrar
- Revisão: Revisado pelo autor

## 2026-11-27 — Hover do cabeçalho

- Ferramenta: Cursor, Grok 4.7
- Etapa: cabeçalho da loja
- Feito com auxílio: `StoreAccountMenu.vue`, `StoreHeader.vue` e `ThemeToggle.vue`
- Finalidade: o item Sair ficar vermelho ao passar o mouse, e os botões do cabeçalho terem um realce leve
- Revisão: Revisado pelo autor

## 2026-11-27 — Foto do perfil

- Ferramenta: Cursor, Grok 4.7
- Etapa: configurações do perfil
- Feito com auxílio: fotos da conta em `user_avatars`, `ProfileAvatarController`, `AvatarImage`, `ProfilePhotos.vue` e o teste de upload
- Finalidade: a pessoa enviar uma foto, cortar e aproximar, guardar até 8, ver a imagem, escolher a padrão e apagar
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=ProfileAvatarTest` passou (6 testes). A página de perfil autenticada mostrou o botão de enviar foto

## 2026-11-27 — Foto no PHP sem GD

- Ferramenta: Cursor, Grok 4.7
- Etapa: configurações do perfil
- Feito com auxílio: `AvatarImage`
- Finalidade: o envio da foto funcionar no PHP 8.6, que não tem a extensão GD, usando o ffmpeg para cortar e gerar o JPEG
- Revisão: Revisado pelo autor. Um JPEG de 640×400 virou 400×400 no PHP 8.6. Os testes de avatar no PHP 8.3 continuaram passando

## 2026-11-27 — Ticket 09, vitrine

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 09
- Feito com auxílio: `Money`, a listagem pública em `Product`, home, catálogo e `BookCard`
- Finalidade: a home mostrar só destaques publicados e o catálogo filtrar por categoria, texto e preço, sem enviar o texto do livro
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CatalogListingTest` passou. A home no ar mostrou *A janela do terceiro andar* a `R$ 37,90` e escondeu o livro não publicado. `/catalogo?categoria=ensaios&q=mesa` mostrou só *Notas sobre mesas vazias* a `R$ 9,90`

## 2026-11-27 — Ticket 10, página do livro

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 10
- Feito com auxílio: rota `books.show`, `BookController`, `readingPreview` em `Product`, `Book.vue` e o link no `BookCard`
- Finalidade: abrir o livro publicado pela slug, com capa ou fallback, e enviar só os dois primeiros parágrafos
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=BookPageTest` passou. O livro do seed abre a `R$ 37,90`; a resposta não traz o terceiro parágrafo; slug inválida e o livro oculto respondem 404

## 2026-11-27 — Ticket 11, sacola

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 11
- Feito com auxílio: `Cart`, `CartController`, a página da sacola e o botão em `Book.vue`
- Finalidade: guardar livros publicados na sessão, sem repetir título, e mostrar o total com o preço atual do banco
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CartTest` passou. Dois livros somam `R$ 62,90`; o mesmo título não duplica; o não publicado não entra; mudar o preço no banco muda o total

## 2026-11-27 — Ticket 12, pedido

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 12
- Feito com auxílio: migrations de `orders` e `order_items`, models e `PlaceOrder`
- Finalidade: gravar a sacola autenticada como pedido `pending_payment`, com título e preço copiados, e esvaziar a sessão só depois do commit
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=PlaceOrderTest` passou. O total ficou `6290` depois de o livro mudar para `1000`; sacola vazia e falha no meio não deixaram pedido

## 2026-11-27 — Ticket 13, checkout

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 13
- Feito com auxílio: página de checkout, volta do login para a URL pretendida e a trava de pedido repetido em `PlaceOrder`
- Finalidade: quem tem conta e sacola rever o total, confirmar nome e e-mail e gravar um único pedido de teste
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CheckoutTest` passou. O visitante voltou ao checkout com o mesmo livro; o segundo envio não criou outro pedido; nome vazio não gravou pedido

## 2026-11-27 — Ticket 14, cliente do gateway

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 14
- Feito com auxílio: `JuntosSeFazClient`, `GatewayException` e `GatewayPayment`
- Finalidade: concentrar a chamada HTTP do Juntos Se Faz, com timeout, status traduzido e o valor em reais num método só, sem vazar o token
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=JuntosSeFazClientTest` passou. Criação, consulta, timeout, HTTP 422 e status desconhecido foram cobertos com `Http::fake`; o token não apareceu na exceção nem no log

## 2026-11-27 — Cliente alinhado à documentação do gateway

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 14
- Feito com auxílio: `JuntosSeFazClient`, `GatewayPayment` e `.env.example`
- Finalidade: seguir a referência oficial em `https://www.juntosefaz.com.br/gateway/docs`: base `/gateway/api/v1`, `POST /payments` com `amount` numérico em reais, `reference` e `pix_code`, e status `approved`/`declined` traduzidos
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=JuntosSeFazClientTest` passou de novo, agora contra `POST /payments`, `amount` 49.9 e status `approved` traduzido para `paid`

## 2026-11-27 — Ticket 15, cobrança Pix

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 15
- Feito com auxílio: migration `payments`, `ChargeOrder` e a página do pedido depois do checkout
- Finalidade: gravar a tentativa de Pix com o total do pedido, cancelar a pendência anterior e marcar `failed` se o gateway não responder
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=ChargeOrderTest` passou. Sucesso preenche `gateway_id`; falha mantém o pedido pendente; a segunda chamada abre outra linha; pedido alheio ou pago é recusado

## 2026-11-27 — Ticket 16, status do Pix

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 16
- Feito com auxílio: `RefreshPayment`, a rota `/pedidos/{order}/pagamento` e `CheckoutPayment.vue`
- Finalidade: consultar o gateway ao abrir e no botão, mostrar o status em português e manter o último status se a rede falhar
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=PaymentStatusTest` passou. A consulta paga não fecha o pedido; o botão consulta de novo e o redirect seguinte não consulta; outra conta recebe 404; timeout mantém o status pendente

## 2026-11-27 — Ticket 17, nova tentativa de Pix

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 17
- Feito com auxílio: `CheckoutController`, `CheckoutPayment.vue` e `PaymentRetryTest`
- Finalidade: explicar Pix recusado, encerrado ou expirado, gerar outra cobrança no mesmo pedido e listar as tentativas sem apagar as antigas
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=PaymentRetryTest` passou. O Pix recusado abre outra linha e o histórico marca a nova como ativa; pedido pago não mostra o botão e o POST não cria pagamento; encerrado e expirado mostram a frase correspondente

## 2026-11-27 — Sacola e comprar agora

- Ferramenta: Cursor, Grok 4.7
- Etapa: página do livro
- Feito com auxílio: `CartController` e `Book.vue`
- Finalidade: **Adicionar à sacola** incluir o livro e permanecer na página; **Comprar agora** incluir o livro e abrir o checkout
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=CartTest` passou. Adicionar volta para a página do livro; comprar agora redireciona ao checkout com o livro na sacola

## 2026-11-27 — Épico 12, Pagsmile

- Ferramenta: Cursor, Grok 4.7
- Etapa: planejamento do épico 12
- Feito com auxílio: `docs/epicos/12-pagsmile.md` e a ordem no índice
- Finalidade: descrever a segunda cobrança Pix, no sandbox da Pagsmile, reusando o checkout e a confirmação que já existem
- Revisão: Revisado pelo autor. Nenhum código da aplicação foi gerado nesta etapa

## 2026-11-27 — Voltar depois de sair

- Ferramenta: Cursor, Grok 4.7
- Etapa: autenticação
- Feito com auxílio: histórico criptografado do Inertia, `LogoutResponse` e `PreventCachingAuthenticatedResponses`
- Finalidade: o botão voltar, depois de sair, não reabrir uma página da conta; visitante sem sessão cai no login
- Revisão: Revisado pelo autor. `php8.3 artisan test --filter=AuthenticationTest` passou. A biblioteca autenticada veio com `Cache-Control: no-store, private`; depois de sair, a home pediu para limpar o histórico e biblioteca, checkout, configurações e painel redirecionaram ao login

## 2026-11-27 — Login com Google

- Ferramenta: Cursor, Grok 4.7
- Etapa: autenticação
- Feito com auxílio: `GoogleAuthController`, coluna `google_id`, botão nas telas de entrar e criar conta
- Finalidade: entrar com a conta do Google, criar a conta se o e-mail for novo e reaproveitar a conta se o e-mail já existir, sem tornar a pessoa administradora
- Revisão: Pendente de revisão do autor. `php8.3 artisan test --filter=GoogleLoginTest` passou. Conta nova fica verificada e sem `is_admin`; e-mail existente não duplica; e-mail não confirmado pelo Google não entra; conta com segundo fator para no desafio

## 2026-11-27 — Tela de entrar

- Ferramenta: Cursor, Grok 4.7
- Etapa: autenticação
- Feito com auxílio: `Login.vue`, `AuthSimpleLayout.vue` e `GoogleLoginLink.vue`
- Finalidade: a entrada deixar de parecer o formulário padrão e seguir a moldura da livraria, com o Google separado do e-mail
- Revisão: Pendente de revisão do autor. `yarn types:check` passou

## 2026-11-27 — Ticket 18, sem webhook

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 18
- Feito com auxílio: seção do gateway no `README.md`
- Finalidade: registrar que o sandbox do Juntos Se Faz não avisa a loja, em vez de inventar uma rota de webhook
- Revisão: Pendente de revisão do autor. Nenhum código da aplicação foi gerado. A referência oficial trata `POST /webhooks` como recurso simulado, sem chamada de volta nem assinatura

## 2026-11-27 — Ticket 19, confirmar o pedido

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 19
- Feito com auxílio: `ConfirmPayment`, o evento `PaymentConfirmed` e a consulta em `RefreshPayment`
- Finalidade: a consulta paga fechar pedido e pagamento uma única vez, sem marcar pago se o valor do gateway divergir
- Revisão: Pendente de revisão do autor. `php8.3 artisan test --filter=ConfirmPaymentTest` passou. A segunda chamada conserva `paid_at` e dispara o evento uma vez; valor divergente deixa os dois pendentes; falha terminal muda só o pagamento

## 2026-11-27 — Ticket 20, histórico de compras

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 20
- Feito com auxílio: `PurchaseController`, `Purchases.vue` e o item Compras no menu da conta
- Finalidade: a pessoa logada ver só os próprios pedidos, do mais novo para o mais antigo, e voltar ao Pix quando o pedido ainda espera pagamento
- Revisão: Pendente de revisão do autor. `php8.3 artisan test --filter=PurchaseHistoryTest` passou. Cada conta viu só os próprios pedidos; o pendente apontou para o Pix; lista vazia abriu a página; visitante foi ao login

## 2026-11-27 — Ticket 21, comprovante da compra

- Ferramenta: Cursor, Grok 4.7
- Etapa: Ticket 21
- Feito com auxílio: `PurchaseController::show` e `Purchase.vue`
- Finalidade: o dono ver o preço congelado, todas as tentativas de Pix e, se o pedido estiver pago, o caminho para ler o livro
- Revisão: Pendente de revisão do autor. `php8.3 artisan test --filter=PurchaseDetailTest` passou. O preço ficou em `R$ 37,90` depois de o produto mudar; as duas tentativas apareceram; o JSON cru não veio na resposta; outra conta recebeu 404; livro despublicado manteve o caminho `/biblioteca/{slug}`

