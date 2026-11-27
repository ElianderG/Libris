# Libris

Livraria de livros e materiais digitais. O pagamento será Pix, no sandbox do Juntos Se Faz.

## Objetivo

Vender livros digitais, guardar a compra do cliente e liberar a leitura na própria plataforma.

## Tecnologias

- Laravel 13, Inertia e Vue 3
- Vite
- Yarn
- MySQL 8, previsto no projeto. A instalação oficial do starter kit deixou o SQLite ativo; a troca para MySQL entra quando houver usuário e senha do banco local
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

## Variáveis de ambiente

Copie `.env.example` para `.env`. Não há credencial de gateway ainda. Nenhum segredo entra no repositório.

## Gateway

Juntos Se Faz, em sandbox. A integração ainda não foi implementada.

## Funcionalidades

Nesta etapa existe a aplicação gerada pelo starter oficial do Laravel com Vue: página inicial Inertia, cadastro e login. Catálogo, checkout, biblioteca e API da conta ainda não existem.

## Uso de inteligência artificial

O registro detalhado está em `docs/uso-de-ia.md`.

- Ferramenta: Cursor, com o modelo Grok 4.7.
- Etapas: planejamento dos épicos e criação deste projeto pelo comando oficial `laravel new libris --vue --pest --database=mysql --no-interaction`.
- O que teve auxílio: a estrutura de épicos, a regra de documentação e os ajustes depois do instalador (nome Libris, locale `pt_BR`, fuso `America/Sao_Paulo`, scripts do Composer apontando para o Yarn).
- Finalidade: começar o Libris com o starter oficial, sem montar o Inertia à mão.
- Revisão: pendente de revisão do autor.

## Aplicação hospedada

Não há deploy.
