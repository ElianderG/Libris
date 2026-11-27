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

