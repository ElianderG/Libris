# Libris te liberant

Livraria de livros e materiais digitais. O pagamento será Pix, no sandbox do Juntos Se Faz.

## Objetivo

Vender livros digitais, guardar a compra do cliente e liberar a leitura na própria plataforma.

## Tecnologias

- Laravel 13, Inertia e Vue 3
- Vite
- Yarn
- MySQL 8.4 no Docker Compose. Sem Docker, o ambiente local continua no SQLite do starter kit
- Pest

## Execução local

O Node precisa ser 22.18 ou mais novo. Nesta máquina, o 22.17 recusa o Vite do starter. O arquivo `.nvmrc` fixa a versão usada.

```bash
nvm use
composer install
yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
yarn dev
php artisan serve
```

A aplicação abre em `http://localhost:8000`. O `yarn dev` sobe o Vite. O `php artisan serve` sobe o PHP.

## Docker Compose

Preencha `DB_PASSWORD` e `DB_ROOT_PASSWORD` no `.env`. Esses valores não vão para o repositório.

```bash
docker compose up --build
```

A aplicação abre em `http://localhost:8080`. O Compose sobe o PHP e o MySQL 8. O front é compilado com Yarn dentro da imagem. Dentro da rede do Compose, a aplicação fala com o banco pelo nome do serviço, `mysql`, na porta 3306. No computador, o mesmo MySQL fica em `127.0.0.1:3308`, para o `php artisan` local usar o `.env`. Os dados ficam no volume `mysql-data`.

## Variáveis de ambiente

Copie `.env.example` para `.env`. O arquivo lista a aplicação, o banco, o Docker, o Google, o Juntos Se Faz e o afiliado. `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` ficam vazios no exemplo, exceto o redirect, que aponta para `/auth/google/callback`. `JUNTOS_SE_FAZ_BASE_URL` aponta para o sandbox documentado. `JUNTOS_SE_FAZ_TOKEN` e `JUNTOS_SE_FAZ_WEBHOOK_SECRET` ficam vazios no exemplo. A comissão de exemplo é `10` e o cookie do afiliado dura `7` dias. Nenhum segredo entra no repositório.

## Gateway

Juntos Se Faz, em sandbox. A base é `https://www.juntosefaz.com.br/gateway/api/v1`. O checkout chama `POST /payments` com o total do pedido em reais. Enquanto a cobrança está pendente, a página do pedido consulta `GET /payments/{id}`. O sandbox devolve o id pedido e não guarda a cobrança. Também não avisa a loja: o recurso `/webhooks` da documentação é simulado, sem chamada de volta nem assinatura. O status muda quando a pessoa abre `/pedidos/{order}/pagamento` ou aperta **Atualizar status**. `JUNTOS_SE_FAZ_WEBHOOK_SECRET` permanece no exemplo, sem uso. O token continua só no `.env`.

## Funcionalidades

### 1. Loja e catálogo

A loja utiliza a identidade visual da **Libris te liberant**, com uma estrutura composta por:

- Cabeçalho e busca visual;
- Catálogo de livros;
- Sacola de compras;
- Acesso à conta do usuário;
- Avisos de sucesso e erro.

A página inicial apresenta os livros em destaque e uma seleção do catálogo.

O catálogo exibe exclusivamente livros publicados e oferece:
- Filtro por categoria;
- Busca por título;
- Ordenação dos resultados, com os parâmetros armazenados na URL.

Cada livro publicado possui uma página individual em `/livros/{slug}`, que apresenta sua sinopse e os dois primeiros parágrafos do conteúdo.

### 2. Sacola de compras e checkout

A sacola é armazenada na sessão do usuário e permite adicionar livros publicados, sem duplicar títulos. O valor total é calculado com base nos preços atuais registrados no banco de dados.

Na página de cada livro, estão disponíveis duas ações:

- **Adicionar à sacola:** adiciona o livro à sacola e mantém o usuário na página atual.
- **Comprar agora:** adiciona o livro à sacola e redireciona diretamente para o checkout.

Ao confirmar a sacola:
- Usuários autenticados são direcionados ao checkout.
- Visitantes são redirecionados para o login e, após a autenticação, retornam ao checkout com os mesmos livros na sacola.

### 3. Sistema de afiliados

O sistema de afiliados permite que usuários autenticados compartilhem links personalizados dos livros.

Na página do livro:
- Usuários autenticados podem copiar o link de indicação, que contém seu código no parâmetro `?ref=`.
- Visitantes recebem um convite para criar uma conta.

Em `/minha-conta/afiliado`, o usuário autenticado pode consultar seu código de indicação e a descrição da comissão.

**Rastreamento das indicações:**

- Ao acessar um link com um `?ref=` válido, o sistema registra o clique e armazena o código no cookie `libris_ref`, com validade de 7 dias.
- Códigos inexistentes ou pertencentes ao próprio usuário não geram registros de clique.
- No momento da criação do pedido, o sistema atribui o afiliado correspondente ao cookie, desde que ele pertença a outra pessoa e ainda esteja dentro do prazo de validade.
- Caso não exista um cookie válido, o afiliado permanece nulo.
- Cliques expirados, autoindicações e códigos enviados diretamente pelo formulário do pedido não são considerados.
- Quando o Pix confirma um pedido com afiliado, nasce uma comissão única: o percentual configurado sobre o total, arredondado para baixo em centavos. Ela aparece em `/minha-conta/afiliado`. Não há saque.

### 4. Autenticação e conta do usuário

O cadastro e o login originais do starter foram mantidos e integrados à identidade visual da loja.

Quando o Google está configurado, ambas as páginas também oferecem a opção **Continuar com Google**.

O fluxo de autenticação contempla as seguintes regras:

- Novas contas não recebem permissões administrativas automaticamente.
- Se o e-mail utilizado no login com Google já estiver cadastrado, o acesso é realizado na conta existente.
- Ao encerrar a sessão, o navegador deixa de manter o acesso às páginas protegidas da conta.
- Sem autenticação, as páginas de checkout, biblioteca, configurações e painel redirecionam o visitante para o login.

Usuários autenticados acessam a conta por meio de um avatar no cabeçalho, que disponibiliza os seguintes atalhos:
- Painel;
- Biblioteca;
- Configurações;
- Sair.

**Configurações de perfil:**

Na página de configurações, o usuário pode enviar fotografias, ajustar o enquadramento e escolher qual imagem será utilizada como foto de perfil. A página pede `/avatars/{id}` no mesmo endereço do site, e a aplicação devolve o JPEG.

### 5. Pagamentos via Pix

Ao selecionar **Pagar com Pix**, o sistema cria um pedido com status `pending_payment` e registra uma tentativa de cobrança.

O fluxo de pagamento contempla os seguintes cenários:

- **Falha na criação da cobrança:** a tentativa recebe o status `failed`, mas o pedido permanece aguardando pagamento.
- **Consulta de pagamento:** em `/pedidos/{order}/pagamento`, o proprietário do pedido pode visualizar o status em português, o valor, a data e o identificador do gateway.
- **Atualização do status:** o usuário pode solicitar uma nova consulta ao gateway. Caso ela falhe, o último status conhecido é preservado.
- **Confirmação do pagamento:** quando a consulta retorna o status de pagamento confirmado e o valor recebido corresponde ao valor do pedido, ambos são atualizados para `paid`, garantindo que a confirmação ocorra uma única vez.

**Geração de um novo Pix:**

Quando a última tentativa de pagamento foi recusada, encerrada ou expirou, e o pedido ainda está aguardando pagamento, a página apresenta uma explicação sobre o ocorrido e disponibiliza a opção **Gerar novo Pix**.

O histórico de cobranças fica disponível na página do pedido, com as tentativas organizadas da mais recente para a mais antiga.

### 6. Histórico de compras

Em `/minha-conta/compras`, usuários autenticados podem consultar exclusivamente seus próprios pedidos, organizados do mais recente para o mais antigo.

Cada pedido possui uma página de comprovante que apresenta:
- Preço registrado no momento da compra;
- Histórico das tentativas de pagamento via Pix.

O comprovante também direciona o usuário conforme a situação do pedido:

- **Pagamento pendente:** permite retornar à página de cobrança.
- **Pagamento confirmado:** disponibiliza o acesso ao livro correspondente em `/biblioteca/{slug}`.

### 7. Biblioteca digital

A biblioteca reúne os livros adquiridos pelo usuário.

Em `/biblioteca`, os pedidos com pagamento confirmado são organizados em uma estante digital, sem duplicação de títulos.

Cada livro possui uma página individual em `/biblioteca/{slug}`, que disponibiliza seu conteúdo integral exclusivamente para usuários que realizaram a compra.

O acesso permanece disponível mesmo que o livro deixe de ser publicado ou removido da vitrine da loja.

### 8. Dados iniciais e seeders

O comando `php artisan db:seed` carrega os dados iniciais necessários para demonstrar o funcionamento da loja:

- 3 categorias;
- 9 livros originais;
- Preços armazenados em centavos;
- 2 capas de livros armazenadas em `public/covers`.

As capas são disponibilizadas publicamente por meio da rota `/covers/{arquivo}`.

Um dos livros cadastrados permanece oculto e, portanto, não aparece na vitrine da loja.

### 9. Pendências e limitações

- A API da conta do usuário ainda não foi implementada.

## Uso de inteligência artificial

O registro detalhado está em `docs/uso-de-ia.md`.

- Ferramenta: Cursor, com o modelo Grok 4.7.
- Etapas: planejamento dos épicos e criação deste projeto pelo comando oficial `laravel new libris --vue --pest --database=mysql --no-interaction`.
- O que teve auxílio: a estrutura de épicos, a regra de documentação e os ajustes depois do instalador (nome Libris, locale `pt_BR`, fuso `America/Sao_Paulo`, scripts do Composer apontando para o Yarn).
- Finalidade: começar o Libris com o starter oficial, sem montar o Inertia à mão.
- Também houve auxílio no Docker Compose, nas credenciais do gateway e no layout da loja (cabeçalho Libris, páginas vazias de catálogo e sacola, avisos que somem).
- Revisão: revisado pelo autor.
- Nesta sessão, o MySQL do Compose passou a ser publicado em `127.0.0.1:3308`. A porta interna do serviço continua 3306. Revisão: revisado pelo autor.
- Ticket 07: categorias e livros no banco, preço em centavos e capa só como caminho relativo. Revisão: revisado pelo autor.
- Ticket 08: seeder idempotente com categorias, livros originais e duas capas geradas. Revisão: revisado pelo autor.
- Menu da conta: avatar no cabeçalho, com painel, minha conta, configurações e sair. O rodapé deixa de mostrar Entrar para quem já está logado. Revisão: revisado pelo autor.
- Hover do menu: Sair fica vermelho, e os botões do cabeçalho ganham um realce leve. Revisão: revisado pelo autor.
- Foto do perfil: envio com corte e zoom, várias fotos guardadas, escolha da padrão e exclusão. Revisão: revisado pelo autor.
- O envio da foto também funciona no PHP 8.6, sem a extensão GD, pelo ffmpeg. Revisão: revisado pelo autor.
- Ticket 09: vitrine com destaques e catálogo filtrado na URL. Revisão: revisado pelo autor.
- Ticket 10: página do livro em `/livros/{slug}`, com prévia cortada no servidor. Revisão: revisado pelo autor.
- Ticket 11: sacola na sessão, com total lido do banco. Revisão: revisado pelo autor.
- Ticket 12: pedido e itens com preço congelado, sem chamar o gateway. Revisão: revisado pelo autor.
- Ticket 13: tela de checkout, com volta ao login e um único pedido em cliques repetidos. Revisão: revisado pelo autor.
- Ticket 14: cliente HTTP do Juntos Se Faz. Revisão: revisado pelo autor.
- Ticket 15: cobrança Pix do pedido, com nova tentativa se a anterior falhar. Revisão: revisado pelo autor.
- Ticket 16: página do pagamento com consulta ao gateway e status em português. Revisão: revisado pelo autor.
- Ticket 17: nova tentativa de Pix na mesma página, com a frase do erro e o histórico. Revisão: revisado pelo autor.
- Página do livro: **Adicionar à sacola** permanece na página e **Comprar agora** abre o checkout. Revisão: revisado pelo autor.
- Épico 12: plano da Pagsmile no mesmo checkout, ainda sem código. Revisão: revisado pelo autor.
- Sair esquece o histórico do navegador, e página autenticada não fica em cache. Revisão: revisado pelo autor.
- Login com Google: cria a conta ou entra na que já tem o mesmo e-mail, sem promover administrador. Revisão: pendente de revisão do autor.
- Tela de entrar alinhada à livraria, com o Google separado do e-mail. Revisão: pendente de revisão do autor.
- Ticket 18: o sandbox do Juntos Se Faz não envia webhook. Revisão: pendente de revisão do autor.
- Ticket 19: a consulta paga fecha o pedido uma única vez. Revisão: pendente de revisão do autor.
- Ticket 20: histórico de compras em `/minha-conta/compras`. Revisão: pendente de revisão do autor.
- Ticket 21: comprovante da compra, com preço congelado e tentativas de Pix. Revisão: pendente de revisão do autor.
- Ticket 22: leitura na biblioteca, só para quem pagou. Revisão: pendente de revisão do autor.
- Ticket 23: link de indicação no livro e código na conta. Revisão: pendente de revisão do autor.
- Ticket 24: clique do afiliado no cookie `libris_ref`. Revisão: pendente de revisão do autor.
- Ticket 25: o pedido guarda o afiliado do cookie, não o `ref` do formulário. Revisão: pendente de revisão do autor.
- Ticket 26: comissão única quando o Pix confirma, listada em Indicar. Revisão: pendente de revisão do autor.
- Capas da vitrine: os dois SVGs ficam em `public/covers` e a página pede `/covers/{arquivo}`. Revisão: pendente de revisão do autor.
- Foto do perfil: a página pede `/avatars/{id}` e a aplicação devolve o JPEG, inclusive quando o servidor não tem GD nem ffmpeg. Revisão: pendente de revisão do autor.

## Aplicação hospedada

https://libris.elianderg.tech/
