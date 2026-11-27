# Épico 03 — Catálogo da loja

**Objetivo:** a loja pública mostra livros reais de exemplo, com categoria, capa, autor e preço.
**Prioridade:** obrigatória.
**Antes de:** carrinho e checkout.

---

## Ticket 07 — [LOJA PÚBLICA] - Modelar categorias e livros

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (Products e Categories).
**Menções na documentação:** `Desafio Técnico`, seção 4 (persistência em banco de dados e validação).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 01.

**Descrição:** Migrations, models e relações.

`categories`: `name`, `slug` único.

`products`: `category_id`, `title`, `slug` único, `author`, `description`, `cover_path` nulo, `price_cents` inteiro positivo, `content` texto longo, `is_featured`, `is_published`, timestamps.

Relação: categoria tem muitos produtos; produto pertence a uma categoria. Slug gerado a partir do título, único.

**Explicação:** Aqui a prateleira ganha forma no banco. Cada livro tem dono de categoria, preço e o texto que o comprador vai ler depois. O que ainda não está publicado não aparece para o público.

**Pontos de atenção:**

- Preço em centavos. R$ 49,90 vira `4990`. Nada de `decimal` ou `float` para dinheiro.
- `content` é o material de leitura. Pode ser texto simples ou Markdown. Não é um arquivo para download.
- Capa fica em `storage` público (`storage/app/public/covers`), servida por link. Sem capa, a tela usa um bloco com o título.
- Índices em `slug`, `category_id`, `is_published` e `is_featured`.
- Apagar categoria que ainda tem livro é proibido. A regra completa de cadastro fica no Ticket 28; o banco já impede o órfão com chave estrangeira `restrict`.

**DoD:**

- As duas migrations sobem e descem.
- Um produto de teste no tinker se relaciona com a categoria.
- Preço zero ou negativo é recusado na regra do model (cast e validação reutilizável, a mesma que o admin usará).

---

## Ticket 08 — [LOJA PÚBLICA] - Carregar livros de exemplo

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (catálogo de livros digitais).
**Menções na documentação:** `Desafio Técnico`, seção 11 (aplicação simples e funcional) e seção 10 (execução local).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 07.

**Descrição:** Seeder idempotente com 3 categorias e pelo menos 8 livros publicados, 4 deles em destaque. Cada livro tem autor, descrição curta, preço em centavos e um conteúdo original de algumas páginas em texto (conto, ensaio ou capítulo inventado). Duas capas podem ser imagens simples geradas no projeto (cor sólida com o título) ou ausentes, para exercitar o fallback.

**Explicação:** O avaliador abre a loja e já encontra livros para comprar. Não precisa cadastrar nada antes de testar o Pix.

**Pontos de atenção:**

- Textos originais e curtos. Não copiar livro, artigo ou capa de terceiros.
- Rodar o seeder duas vezes não duplica slug.
- Incluir um livro não publicado, para provar que a vitrine o esconde.
- Preços variados, inclusive um valor com centavos (por exemplo R$ 37,90), para o Pix não ser testado só com número redondo.

**DoD:**

- `db:seed` deixa categorias e livros no banco.
- O livro não publicado existe no banco e não será listado pelo Ticket 09.
- Nenhum texto seed é obra de terceiro.

---

## Ticket 09 — [LOJA PÚBLICA] - Montar a vitrine e os filtros

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (home, catálogo, categorias e filtros).
**Menções na documentação:** `Desafio Técnico`, seção 4 (interface funcional e responsiva).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 04, Ticket 08.

**Descrição:** Página inicial com os livros em destaque e um recorte do catálogo. Página de catálogo com todos os publicados, filtro por categoria, busca por título ou autor e ordenação por mais recente e menor preço. Só entram produtos `is_published`.

**Explicação:** É a porta da livraria. A pessoa chega, vê o que está em evidência e consegue achar um livro pelo tema, pelo nome ou pelo autor, no computador e no celular.

**Pontos de atenção:**

- Busca e filtro acontecem no servidor (query string), para a URL poder ser compartilhada: `/catalogo?categoria=ensaios&q=mar`.
- Paginar a partir de 12 itens. Com 8 seeds a página única está correta; a paginação já fica pronta.
- Estado vazio com frase clara quando o filtro não acha nada.
- Preço formatado em reais (`R$ 37,90`) num único helper, usado em toda a aplicação.
- Não expor `content` nesta listagem. A leitura é só de quem comprou.

**DoD:**

- Home mostra só destaques publicados.
- Catálogo filtra por categoria e por texto, e esconde o não publicado.
- A URL reflete o filtro e continua válida ao recarregar.
- Em tela estreita os cards empilham sem corte de preço ou título.

---

## Ticket 10 — [LOJA PÚBLICA] - Abrir a página do livro

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (página individual: descrição, capa, autor e preço).
**Menções na documentação:** `Desafio Técnico`, seção 4 (formulário para iniciar uma transação entra no épico seguinte; a página do produto é o ponto de partida).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 09.

**Descrição:** Página `/livros/{slug}` com capa, título, autor, categoria, preço, descrição e botão "Adicionar à biblioteca" (liga no carrinho no Ticket 11; até lá o botão pode estar presente e desabilitado, ou já apontar para a rota que o Ticket 11 cria). Trecho inicial do conteúdo, no máximo os primeiros parágrafos, marcado como prévia. Livro inexistente ou não publicado responde 404.

**Explicação:** É a ficha do livro, o lugar em que a pessoa decide comprar. Ela lê a sinopse e um pedaço do texto. O resto só abre depois do pagamento.

**Pontos de atenção:**

- A prévia é cortada no servidor. O JSON da página não leva o `content` inteiro para quem não comprou.
- Se a pessoa já comprou e o pedido está pago, o botão principal vira "Ler na biblioteca" (a rota de leitura é o Ticket 22; aqui basta o destino previsto).
- Slug é a URL. Id numérico não aparece no endereço público.

**DoD:**

- Os livros do seed abrem pela slug com capa ou fallback, autor e preço.
- O fonte da página não contém o texto completo de um livro não comprado.
- Slug inválida e livro não publicado respondem 404.
