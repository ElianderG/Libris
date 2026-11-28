# Libris te liberant

O Libris te liberant é uma Livraria de livros e materiais digitais. O cliente escolhe um título, paga via Pix e realiza a leitura diretamente na plataforma. O gateway padrão é o Juntos Se Faz, integrado em ambiente sandbox. A Pagsmile também está disponível como segunda opção no mesmo checkout, igualmente em sandbox.

## Objetivo

Vender livros digitais, registrar as compras dos clientes e liberar o acesso à leitura na própria plataforma.

No domínio da aplicação, **pedido** e **pagamento** são conceitos distintos: o pedido representa a intenção de compra, enquanto o pagamento representa uma tentativa de liquidá-lo.

## Tecnologias

- PHP 8.3 ou superior;
- Laravel 13, Inertia e Vue 3;
- Vite;
- Yarn;
- MySQL 8.4 com Docker Compose. Sem Docker, o ambiente local utiliza SQLite, conforme configurado no `.env.example`;
- Pest.

A suíte de testes pode ser executada com `php artisan test`. Os cenários cobertos estão descritos em `docs/testes.md`.

## Execução local

O Node.js deve estar na versão 22.18 ou superior. Nesta máquina, a versão 22.17 não é compatível com a versão do Vite utilizada pelo starter. O arquivo `.nvmrc` define a versão esperada.

Execute os comandos abaixo na raiz do projeto:

```bash
nvm use
composer install
yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

A aplicação utiliza dois processos, que devem ser iniciados em terminais separados.

No primeiro terminal, inicie o Vite:

```bash
yarn dev
```

No segundo, inicie o servidor Laravel:

```bash
php artisan serve
```

A aplicação ficará disponível em `http://localhost:8000`.

A página inicial apresenta os livros publicados carregados pelo seed: são 3 categorias e 9 livros originais, sendo que um deles permanece oculto da vitrine. Como o `.env.example` utiliza SQLite, essa forma de execução não exige MySQL.

## Docker Compose

Antes de iniciar os containers, preencha `DB_PASSWORD` e `DB_ROOT_PASSWORD` no arquivo `.env`. Esses valores são credenciais e não devem ser enviados ao repositório.

Na raiz do projeto, execute:

```bash
docker compose up --build
```

Quando a aplicação estiver em execução, abra outro terminal e carregue os dados iniciais:

```bash
docker compose exec app php artisan db:seed
```

A aplicação ficará disponível em `http://localhost:8080`, com o mesmo catálogo carregado pelo seed.

O Docker Compose inicia os containers do PHP e do MySQL 8. O front-end é compilado com Yarn durante a construção da imagem, e o container da aplicação executa as migrations automaticamente.

Na rede interna do Compose, a aplicação acessa o banco pelo nome do serviço `mysql`, na porta `3306`. No computador host, o mesmo banco fica disponível em `127.0.0.1:3308`, permitindo que comandos `php artisan` executados localmente utilizem as configurações do `.env`. Os dados do banco são persistidos no volume `mysql-data`.

## Variáveis de ambiente

Copie o `.env.example` para `.env` e configure os valores necessários. O arquivo de exemplo reúne as variáveis da aplicação, do banco de dados, do Docker, do Google, dos gateways Juntos Se Faz e Pagsmile, do programa de afiliados e do administrador local.

Nenhum segredo ou credencial real deve ser incluído no repositório.

- `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` ficam vazios no exemplo. `GOOGLE_REDIRECT_URI` aponta para `/auth/google/callback`.
- `JUNTOS_SE_FAZ_BASE_URL` aponta para o ambiente sandbox documentado. `JUNTOS_SE_FAZ_TOKEN` e `JUNTOS_SE_FAZ_WEBHOOK_SECRET` ficam vazios no exemplo.
- `PAGSMILE_BASE_URL` aponta para `https://gateway-test.pagsmile.com`. `PAGSMILE_APP_ID` e `PAGSMILE_SECURITY_KEY` ficam vazios no exemplo.
- A comissão padrão de exemplo é `10`, e o cookie de afiliado tem validade de `7` dias.
- `ADMIN_EMAIL` e `ADMIN_PASSWORD` ficam vazios no exemplo. Se forem preenchidos no `.env` antes da execução do seed, uma conta de administrador será criada. Se permanecerem vazios, o seed carregará somente o catálogo.
- Em ambiente publicado, `APP_DEBUG` deve permanecer como `false`. Em ambiente local, pode ser definido como `true`.

## Gateways de pagamento

### Juntos Se Faz

O checkout utiliza o Juntos Se Faz como gateway padrão, em ambiente sandbox. A URL-base da API é `https://www.juntosefaz.com.br/gateway/api/v1`.

A criação da cobrança é feita por meio de `POST /payments`, enviando o valor total do pedido em reais. Enquanto a cobrança estiver pendente, a página do pedido consulta seu estado com `GET /payments/{id}`.

O sandbox retorna o identificador solicitado, mas não persiste a cobrança. Por isso, as consultas continuam retornando `pending`. O ambiente também não envia notificações à loja: o recurso `/webhooks`, descrito na documentação, é simulado e não realiza chamadas de retorno nem utiliza assinatura.

Para permitir a demonstração do fluxo, o administrador pode aprovar, recusar, cancelar ou expirar uma tentativa pendente em `/admin/pedidos/{pedido}`. Cada ação chama, respectivamente, `POST /payments/{id}/approve`, `POST /payments/{id}/decline`, `POST /payments/{id}/cancel` ou `POST /payments/{id}/expire`. A loja aplica o resultado retornado pelo sandbox.

A variável `JUNTOS_SE_FAZ_WEBHOOK_SECRET` permanece no arquivo de exemplo, mas não é utilizada. O token do gateway deve permanecer exclusivamente no `.env`.

### Pagsmile

A Pagsmile é a segunda opção de gateway no mesmo checkout e também utiliza ambiente sandbox, em `https://gateway-test.pagsmile.com`.

Durante o checkout, o cliente escolhe em qual gateway deseja gerar o Pix. O Juntos Se Faz vem selecionado por padrão. Para utilizar a Pagsmile, é necessário informar um CPF com 11 dígitos. Esse documento é enviado somente na chamada ao gateway e não é armazenado no pedido.

A criação do pagamento utiliza `POST /trade/pay`, e a consulta utiliza `POST /trade/query`. A autenticação é feita por Basic Auth, com `app_id` e `security_key`. O valor é enviado como texto, em reais, no campo `order_amount`.

Enquanto a tentativa estiver pendente, o acesso à página do pagamento ou o acionamento de **Atualizar status** realiza uma consulta à Pagsmile. O status `PROCESSING` não é tratado como confirmação do pagamento. Se a consulta falhar, o último status conhecido é preservado.

As notificações da Pagsmile são recebidas em `POST {APP_URL}/webhooks/pagsmile`. Essa rota não exige sessão nem token CSRF e possui limite de 30 requisições por minuto.

O cabeçalho `Pagsmile-Signature` contém os campos `t` e `v2`. O valor de `v2` corresponde ao HMAC-SHA256 do corpo original da requisição, calculado com a `security_key` e comparado em tempo constante. O horário informado em `t` deve estar dentro de uma janela de 5 minutos.

Uma assinatura inválida resulta em resposta HTTP `401`, sem gravação de dados. Uma notificação de pagamento aprovado só confirma o pedido depois que uma consulta ao gateway confirma o mesmo valor da tentativa. A resposta aceita pela rota de webhook é o texto `success`. Notificações repetidas não alteram `paid_at`, e o corpo da notificação não é incluído nos logs.

A opção **Gerar novo Pix** mantém o gateway registrado no pedido. As variáveis `PAGSMILE_APP_ID` e `PAGSMILE_SECURITY_KEY` devem permanecer exclusivamente no `.env`.

## Pedido, pagamento e comissão

Pedido e pagamento são registros distintos. Um pedido é criado com o status `pending_payment` e só passa para `paid` quando o pagamento via Pix é confirmado. Essa transição ocorre uma única vez.

Recusar, cancelar ou expirar uma tentativa não altera o status do pedido: ele continua aguardando pagamento, e a página disponibiliza a opção **Gerar novo Pix**.

Cada tentativa de pagamento pode assumir um dos seguintes status:

- `pending`;
- `paid`;
- `failed`;
- `canceled`;
- `expired`.

Somente uma tentativa pode permanecer pendente por pedido.

Quando um pagamento confirma um pedido associado a um afiliado, é criada uma comissão com status `confirmed`. Cada pedido pode gerar no máximo uma comissão. O valor corresponde ao percentual definido em `AFFILIATE_COMMISSION_PERCENT`, aplicado ao total do pedido e arredondado para baixo em centavos.

O sistema não oferece funcionalidade de saque nem uma tela para alterar o percentual da comissão. O mapeamento completo das entidades e regras de domínio está em `docs/dominio.md`.

## API do Libris

A API própria da loja está disponível em `/api/v1` e é documentada em `docs/api.md`.

Com uma chave gerada na opção **Chave da API**, um sistema externo pode consultar informações da própria conta: seus dados, suas compras e as vendas realizadas por indicação. Essas rotas não utilizam a sessão do navegador como mecanismo de autenticação.

A API do Libris não cria pagamentos nem gera Pix.

O Juntos Se Faz e a Pagsmile não fazem parte dessa API própria: são serviços externos de gateway consumidos pelo checkout para criar e consultar cobranças. O funcionamento interno da chave de acesso está descrito em `docs/api-interna.md`.

## Funcionalidades

### Loja e catálogo

A loja utiliza a identidade visual da **Libris te liberant** e conta com uma estrutura composta por:

- Cabeçalho com as opções Catálogo e Meus Livros, além da busca;
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

### Sacola de compras e checkout

A sacola é armazenada na sessão do usuário e permite adicionar livros publicados sem duplicar títulos. Tanto na sacola quanto no checkout, é possível remover um livro ou esvaziar a sacola.

O valor total é calculado com base nos preços atuais registrados no banco de dados. Um preço enviado no POST do checkout não é utilizado para compor o pedido.

Na página de cada livro, estão disponíveis duas ações:

- **Adicionar à sacola:** adiciona o livro à sacola e mantém o usuário na página atual.
- **Comprar agora:** adiciona o livro à sacola e abre o checkout, onde cada livro pode ser removido antes da geração do Pix.

Ao confirmar a sacola:

- Usuários autenticados são direcionados ao checkout.
- Visitantes são redirecionados para o login e, após a autenticação, retornam ao checkout com os mesmos livros na sacola.

### Afiliados

O sistema de afiliados permite que usuários autenticados compartilhem links personalizados dos livros.

Na página de cada livro:

- Usuários autenticados podem copiar o link de indicação, que contém seu código no parâmetro `?ref=`.
- Visitantes recebem um convite para criar uma conta.

Em `/minha-conta/afiliado`, o usuário autenticado pode consultar seu código de indicação, a descrição da comissão e as comissões já confirmadas.

**Rastreamento das indicações**

- Ao acessar um link com um `?ref=` válido, o sistema registra o clique e armazena o código no cookie `libris_ref`, com validade de 7 dias.
- Códigos inexistentes ou pertencentes ao próprio usuário não geram registros de clique.
- No momento da criação do pedido, o sistema atribui o afiliado correspondente ao cookie, desde que o código pertença a outra pessoa e ainda esteja dentro do prazo de validade.
- Caso não exista um cookie válido, o afiliado permanece nulo.
- Cliques expirados, autoindicações e códigos enviados diretamente pelo formulário do pedido não são considerados.
- Quando o Pix confirma um pedido associado a um afiliado, é criada a comissão descrita na seção [Pedido, pagamento e comissão](#pedido-pagamento-e-comissão). O sistema não oferece saque.

### Autenticação e conta do usuário

O cadastro e o login originais do starter foram mantidos e integrados à identidade visual da loja.

Quando o login com Google está configurado, ambas as páginas também oferecem a opção **Continuar com Google**.

O fluxo de autenticação contempla as seguintes regras:

- Novas contas não recebem permissões administrativas automaticamente.
- Se o e-mail utilizado no login com Google já estiver cadastrado, o acesso é realizado na conta existente.
- Ao encerrar a sessão, o navegador deixa de manter o acesso às páginas protegidas da conta.
- Sem autenticação, as páginas de checkout, biblioteca, configurações e painel redirecionam o visitante para o login.

Usuários autenticados acessam a conta por meio de um avatar no cabeçalho, que disponibiliza os seguintes atalhos:

- Painel, exclusivo para administradores;
- Biblioteca;
- Compras;
- Indicar;
- Chave da API;
- Configurações;
- Sair.

**Configurações de perfil**

Na página de configurações, o usuário pode enviar fotografias, ajustar o enquadramento e escolher qual imagem será utilizada como foto de perfil. A página solicita `/avatars/{id}` no mesmo endereço do site, e a aplicação retorna a imagem em formato JPEG.

**Chave da conta**

Em `/minha-conta/configuracoes`, o usuário autenticado pode gerar uma chave no formato `libris_` seguido de 40 caracteres.

O valor completo é exibido uma única vez. Nas visualizações seguintes, a página mostra apenas os 12 primeiros caracteres, seguidos de reticências.

Ao gerar uma nova chave ou revogar a atual, o sistema preenche `revoked_at` na chave anterior, mas mantém seu registro no banco. O banco armazena o hash SHA-256 e o prefixo, nunca o texto completo da chave.

Cada conta pode ter uma chave ativa. A chave de uma conta administradora não concede acesso geral à loja.

Com o cabeçalho `Authorization: Bearer` e essa chave, um sistema externo pode consultar somente os dados da conta correspondente por meio das seguintes rotas:

- `GET /api/v1/me`;
- `GET /api/v1/me/compras`;
- `GET /api/v1/me/compras/{order}`;
- `GET /api/v1/me/vendas`.

Os valores monetários são retornados em centavos. **Compra** representa aquilo que a própria conta pagou. **Venda** representa um pedido pago por outra pessoa a partir do link de indicação da conta, com a respectiva comissão confirmada.

As respostas para situações de acesso inválido são:

- Chave ausente, incorreta ou revogada: HTTP `401`, com a mensagem `Chave inválida.`;
- Pedido pertencente a outra pessoa: HTTP `404`;
- Método HTTP não permitido: HTTP `405`.

O limite é de 60 requisições por minuto por chave. A requisição seguinte ao limite recebe HTTP `429`.

O passo a passo de utilização está em `docs/api.md`.

### Pagamentos via Pix

Ao selecionar **Pagar com Pix**, o cliente escolhe entre Juntos Se Faz e Pagsmile. O sistema cria um pedido com status `pending_payment` e registra uma tentativa de cobrança no gateway escolhido.

A Pagsmile exige um CPF com 11 dígitos. Se a resposta do gateway incluir a imagem do QR Code, a página de pagamento a apresenta junto com o código Pix copia e cola.

O fluxo de pagamento contempla os seguintes cenários:

- **Falha na criação da cobrança:** a tentativa recebe o status `failed`, mas o pedido permanece aguardando pagamento.
- **Consulta de pagamento:** em `/pedidos/{order}/pagamento`, o proprietário do pedido pode visualizar o status em português, o valor e a data. Quando a resposta contém um código Pix utilizável, a página também apresenta o copia e cola.
- **Atualização do status:** o usuário pode solicitar uma nova consulta ao gateway. Se a consulta falhar, o último status conhecido é preservado.
- **Confirmação do pagamento:** quando a consulta, a notificação da Pagsmile ou a ação do painel retorna uma confirmação e o valor corresponde ao do pedido, o pagamento e o pedido passam para `paid`, uma única vez. A notificação da Pagsmile só confirma o pagamento depois da consulta ao gateway. Se a resposta da ação do painel não informar o valor, a confirmação ainda assim é realizada.
- **Ação no painel:** na tentativa pendente mais recente, o administrador pode aprovar, recusar, cancelar ou expirar o pagamento. A aprovação conclui o pedido; as outras três ações mantêm o pedido aguardando pagamento.

**Geração de um novo Pix**

Quando a última tentativa de pagamento é recusada, encerrada ou expira, e o pedido ainda está aguardando pagamento, a página apresenta uma explicação sobre o ocorrido e disponibiliza a opção **Gerar novo Pix**.

A nova tentativa utiliza o mesmo gateway registrado no pedido. Na Pagsmile, o CPF é solicitado novamente e não fica armazenado.

O histórico de cobranças fica disponível na página do pedido, com as tentativas organizadas da mais recente para a mais antiga.

### Histórico de compras

Em `/minha-conta/compras`, usuários autenticados podem consultar exclusivamente seus próprios pedidos, organizados do mais recente para o mais antigo.

Cada pedido possui uma página de comprovante que apresenta:

- Preço registrado no momento da compra;
- Histórico das tentativas de pagamento via Pix.

O comprovante também direciona o usuário de acordo com a situação do pedido:

- **Pagamento pendente:** permite retornar à página de cobrança.
- **Pagamento confirmado:** disponibiliza o acesso ao livro correspondente em `/biblioteca/{slug}`.

### Biblioteca digital

A biblioteca reúne os livros adquiridos pelo usuário.

A aba **Meus Livros**, no cabeçalho da loja, abre `/biblioteca`. Visitantes são redirecionados para o login e, após a autenticação, retornam à estante.

Em `/biblioteca`, os pedidos com pagamento confirmado são organizados sem duplicação de títulos.

Cada livro possui uma página individual em `/biblioteca/{slug}`, que disponibiliza seu conteúdo integral exclusivamente aos usuários que realizaram a compra.

O acesso permanece disponível mesmo que o livro deixe de ser publicado ou seja removido da vitrine da loja.

### Dados iniciais

O comando `php artisan db:seed` carrega os dados iniciais necessários para demonstrar o funcionamento da loja:

- 3 categorias;
- 9 livros originais;
- Preços armazenados em centavos;
- 2 capas de livros armazenadas em `public/covers`.

As capas são disponibilizadas publicamente por meio da rota `/covers/{arquivo}`.

Um dos livros cadastrados permanece oculto e, portanto, não aparece na vitrine da loja.

### Painel administrativo

Em `/admin`, o administrador acessa um painel sem o cabeçalho da vitrine. O logo Libris, no topo, abre a página inicial da loja, a mesma utilizada pelo site.

As áreas do painel ficam organizadas em um menu à esquerda:

- Início;
- Livros;
- Categorias;
- Pedidos;
- Usuários;
- Afiliados.

O item **Início** desse menu continua apontando para `/admin`.

Em dispositivos móveis, a tela mostra somente o menu. A área selecionada é aberta ao toque, e o botão **Voltar** retorna à lista.

A página inicial apresenta quatro indicadores, mesmo quando seus valores são zero:

- Livros publicados;
- Pedidos pagos;
- Total vendido;
- Comissões confirmadas.

O total vendido considera somente pedidos com status `paid` e utiliza a mesma formatação monetária da loja.

O link para o painel no menu da conta aparece somente para usuários com `is_admin`. Clientes autenticados que tentam acessar essas rotas recebem HTTP `403`.

**Categorias e livros**

Em `/admin/categorias`, o administrador pode cadastrar e editar o nome das categorias. Uma categoria não pode ser excluída enquanto possuir livros associados.

Em `/admin/livros`, a listagem permite buscar livros pelo título. O formulário de cadastro e edição solicita:

- Categoria;
- Título;
- Autor;
- Descrição;
- Preço em reais, aceitando formatos como `37,90` ou `37.90`;
- Conteúdo;
- Destaque;
- Status de publicação;
- Capa opcional de até 2 MB.

As capas ficam armazenadas em `public/covers`. Ao substituir uma capa, o arquivo anterior é apagado. O preço é convertido para centavos antes de ser armazenado no banco.

Alterar o título não modifica o slug do livro. Um livro que já esteja associado a algum pedido não pode ser apagado: a interface oferece a opção de despublicá-lo. Depois de despublicado, ele deixa de aparecer na vitrine, mas continua disponível na biblioteca de quem realizou a compra.

**Visão geral e afiliados**

A página inicial do painel apresenta atalhos para livros, categorias, pedidos, usuários e afiliados.

Abaixo dos atalhos, são exibidos os quatro indicadores e os gráficos do Chart.js:

- Vendas pagas nos últimos 14 dias;
- Pedidos por status;
- Livros publicados por categoria.

Pedidos antigos não entram no gráfico de vendas, e livros não publicados não entram no gráfico por categoria.

Na sequência, a página inicial lista os cinco pedidos pagos mais recentes.

Em `/admin/afiliados`, cada linha representa um usuário que já teve ao menos um clique ou uma comissão. A listagem apresenta o código de indicação, a quantidade de cliques, os pedidos pagos atribuídos e a soma das comissões confirmadas. Usuários que nunca realizaram indicações não aparecem.

O painel não oferece saque nem alteração do percentual de comissão. O percentual utilizado continua sendo o definido na configuração.

**Pedidos e usuários**

Em `/admin/pedidos`, o administrador pode filtrar a listagem por status e abrir os detalhes de cada pedido, incluindo seus itens e pagamentos. São apresentados o status e o valor do pedido.

Na tentativa pendente mais recente, o administrador dispõe das ações **Aprovar**, **Recusar**, **Cancelar** e **Expirar**, todas com confirmação. Cada ação é enviada ao sandbox, e a resposta recebida atualiza o pedido.

Esses botões não ficam disponíveis para tentativas antigas, pedidos já pagos ou clientes.

A listagem também permite abrir somente os pedidos de uma conta específica, a partir do menu de usuários.

Em `/admin/usuarios`, cada conta possui um menu flutuante com as opções:

- Ver pedidos;
- Tornar administrador ou remover a permissão de administrador;
- Apagar a conta.

As ações de promover um usuário, remover seu acesso administrativo ou apagar sua conta exigem confirmação. A própria conta do administrador não pode ser alterada por essa tela.

Contas que possuam pedidos ou comissões não podem ser apagadas, preservando o histórico da loja.

As listagens de pedidos e usuários são paginadas. Senhas e tokens não são exibidos no painel.

## Uso de inteligência artificial

O desenvolvimento deste repositório contou com o auxílio de ferramentas de inteligência artificial. O registro detalhado de cada etapa, incluindo os arquivos envolvidos e a forma de revisão, está em `docs/uso-de-ia.md`.

- **Ferramenta:** Cursor, utilizando o modelo Grok 4.7.
- **Etapas:** planejamento dos épicos; configuração do ambiente, conta, catálogo, sacola e checkout; integração com o Juntos Se Faz, confirmação de pagamentos e biblioteca; afiliados e painel administrativo; integração com a Pagsmile; chave e API da conta; testes, segurança e elaboração deste README.
- **Itens desenvolvidos com auxílio:** projeto criado a partir do starter oficial `laravel new libris --vue --pest --database=mysql`; Docker Compose; telas da loja e do painel; clientes de integração com os dois gateways; API `/api/v1`; suíte de testes; e documentação em `docs/`.
- **Finalidade:** desenvolver a livraria proposta no desafio a partir do starter oficial, incluindo pagamentos via Pix em sandbox, biblioteca digital, afiliados sem funcionalidade de saque e API para consulta dos dados da própria conta, além de manter o registro do conteúdo produzido com auxílio de IA.
- **Revisão:** os itens anteriores registrados em `docs/uso-de-ia.md` estão marcados como revisados pelo autor. Quando aplicável, cada item indica o comando de teste executado com sucesso, como `php artisan test` e `yarn types:check`. Este README ainda está pendente de revisão pelo autor.

**Etapas realizadas, na ordem do desenvolvimento:**

- Planejamento dos épicos em `docs/epicos/`, antes do início da implementação.
- Tickets 01 a 04: starter Laravel com Inertia e Vue, Docker Compose, credenciais mantidas somente no `.env` e layout da loja.
- Tickets 05 e 06: cadastro, administrador e login com Google.
- Tickets 07 a 10: categorias, livros, vitrine e página individual do livro.
- Tickets 11 a 13: sacola armazenada na sessão e criação do pedido com preço congelado, ainda sem chamada ao gateway.
- Tickets 14 a 19: cliente do Juntos Se Faz, criação e consulta de cobranças, nova tentativa e confirmação única. O sandbox desse gateway não envia webhooks.
- Tickets 20 a 22: histórico de compras, comprovante e leitura na biblioteca.
- Tickets 23 a 26: link de indicação, registro de cliques, atribuição de afiliados e comissão sem saque.
- Tickets 27 a 30: painel administrativo de catálogo, pedidos, usuários e afiliados.
- Tickets 37 a 39: integração da Pagsmile ao mesmo checkout, com consulta e notificação assinada.
- Tickets 34 a 36: chave da conta e documentação em `docs/api.md`.
- Tickets 31 e 32: suíte de testes do fluxo financeiro e policies de acesso. A suíte completa passou com 217 testes.
- Ticket 33: elaboração deste README.

## Aplicação hospedada

[https://libris.elianderg.tech/](https://libris.elianderg.tech/)
