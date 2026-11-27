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

Copie `.env.example` para `.env`. O arquivo lista a aplicação, o banco, o Docker, o Juntos Se Faz e o afiliado. `JUNTOS_SE_FAZ_BASE_URL` aponta para o sandbox documentado. `JUNTOS_SE_FAZ_TOKEN` e `JUNTOS_SE_FAZ_WEBHOOK_SECRET` ficam vazios no exemplo. A comissão de exemplo é `10` e o cookie do afiliado dura `7` dias. Nenhum segredo entra no repositório.

## Gateway

Juntos Se Faz, em sandbox. A base é `https://www.juntosefaz.com.br/gateway/api/v1`. O checkout chama `POST /payments` com o total do pedido em reais. Enquanto a cobrança está pendente, a página do pedido consulta `GET /payments/{id}`. O sandbox devolve o id pedido e não guarda a cobrança. O token continua só no `.env`.

## Funcionalidades

Nesta etapa a loja tem a moldura da Libris te liberant: cabeçalho, busca visual, catálogo e sacola, conta e avisos de sucesso e erro. A home mostra os livros em destaque e um recorte do catálogo. O catálogo lista só os publicados, com filtro por categoria, busca e ordenação na URL. Cada livro publicado abre em `/livros/{slug}`, com a sinopse e só os dois primeiros parágrafos. A sacola fica na sessão: dá para juntar livros publicados, sem repetir o mesmo título, e o total usa o preço atual do banco. Confirmar essa sacola, com a conta autenticada, abre o checkout. Quem ainda não entrou vai ao login e volta com os mesmos livros. **Pagar com Pix** grava um pedido `pending_payment` e uma tentativa de cobrança. Se o gateway falha, o pagamento fica `failed` e o pedido continua pendente. Em `/pedidos/{order}/pagamento`, o dono vê o status em português, o valor, a data e o id do gateway, e pode atualizar a consulta. Se a consulta falha, o último status permanece. O pedido só fecha como pago no passo seguinte. Se a última tentativa foi recusada, encerrada ou expirada e o pedido ainda espera pagamento, a página explica o que houve e oferece **Gerar novo Pix**. O histórico lista as tentativas, da mais nova para a mais antiga. Quem está logado abre a conta por um avatar no cabeçalho, com painel, biblioteca, configurações e sair. Em Configurações, a pessoa envia fotos, ajusta o corte e escolhe qual fica no perfil. O cadastro e o login do starter continuam dentro dessa moldura. `php artisan db:seed` carrega 3 categorias e 9 livros originais, com preço em centavos e duas capas geradas em `storage/app/public/covers`. Um desses livros fica oculto e não entra na vitrine. A biblioteca e a API da conta ainda não existem.

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
- Ticket 09: vitrine com destaques e catálogo filtrado na URL. Revisão: pendente de revisão do autor.
- Ticket 10: página do livro em `/livros/{slug}`, com prévia cortada no servidor. Revisão: pendente de revisão do autor.
- Ticket 11: sacola na sessão, com total lido do banco. Revisão: pendente de revisão do autor.
- Ticket 12: pedido e itens com preço congelado, sem chamar o gateway. Revisão: pendente de revisão do autor.
- Ticket 13: tela de checkout, com volta ao login e um único pedido em cliques repetidos. Revisão: pendente de revisão do autor.
- Ticket 14: cliente HTTP do Juntos Se Faz. Revisão: pendente de revisão do autor.
- Ticket 15: cobrança Pix do pedido, com nova tentativa se a anterior falhar. Revisão: pendente de revisão do autor.
- Ticket 16: página do pagamento com consulta ao gateway e status em português. Revisão: pendente de revisão do autor.
- Ticket 17: nova tentativa de Pix na mesma página, com a frase do erro e o histórico. Revisão: pendente de revisão do autor.

## Aplicação hospedada

https://libris.elianderg.tech/
