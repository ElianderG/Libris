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

Copie `.env.example` para `.env`. O arquivo lista a aplicação, o banco, o Docker, o Juntos Se Faz e o afiliado. `JUNTOS_SE_FAZ_BASE_URL`, `JUNTOS_SE_FAZ_TOKEN` e `JUNTOS_SE_FAZ_WEBHOOK_SECRET` ficam vazios até a documentação oficial do gateway. A comissão de exemplo é `10` e o cookie do afiliado dura `7` dias. Nenhum segredo entra no repositório.

## Gateway

Juntos Se Faz, em sandbox. A integração ainda não foi implementada.

## Funcionalidades

Nesta etapa a loja tem a moldura da Libris te liberant: cabeçalho, busca visual, catálogo e sacola ainda vazios, conta e avisos de sucesso e erro. O cadastro e o login do starter continuam dentro dessa moldura. Checkout, biblioteca e API da conta ainda não existem.

## Uso de inteligência artificial

O registro detalhado está em `docs/uso-de-ia.md`.

- Ferramenta: Cursor, com o modelo Grok 4.7.
- Etapas: planejamento dos épicos e criação deste projeto pelo comando oficial `laravel new libris --vue --pest --database=mysql --no-interaction`.
- O que teve auxílio: a estrutura de épicos, a regra de documentação e os ajustes depois do instalador (nome Libris, locale `pt_BR`, fuso `America/Sao_Paulo`, scripts do Composer apontando para o Yarn).
- Finalidade: começar o Libris com o starter oficial, sem montar o Inertia à mão.
- Também houve auxílio no Docker Compose, nas credenciais do gateway e no layout da loja (cabeçalho Libris, páginas vazias de catálogo e sacola, avisos que somem).
- Revisão: revisado pelo autor.
- Nesta sessão, o MySQL do Compose passou a ser publicado em `127.0.0.1:3308`. A porta interna do serviço continua 3306. Revisão: pendente de revisão do autor.

## Aplicação hospedada

Não há deploy.
