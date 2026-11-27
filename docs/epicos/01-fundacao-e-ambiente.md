# Épico 01 — Fundação e ambiente

**Objetivo:** o avaliador clona o repositório, sobe o ambiente e abre uma aplicação com cara de livraria.
**Prioridade:** obrigatória.
**Antes de:** qualquer regra de negócio.

---

## Ticket 01 — [AMBIENTE] - Criar o projeto Laravel com Inertia e Vue

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (arquitetura).
**Menções na documentação:** `Desafio Técnico`, seção 5 (liberdade tecnológica) e seção 10 (execução local).

**Status:** Concluído
**Prioridade:** obrigatória.
**Depende de:** nenhum.

**Descrição:** Gerar a aplicação Laravel com o starter oficial de Inertia e Vue 3, Vite, MySQL e a estrutura de pastas que os próximos tickets vão ocupar: páginas Vue em `resources/js/pages`, serviços de pagamento em `app/Services/Payments`, políticas em `app/Policies`. O gerenciador do front é o Yarn.

**Explicação:** É a obra bruta da loja. Ainda não vende nada. Só garante que a tecnologia combinada está instalada e que cada tipo de código já tem um lugar certo, para o projeto não virar uma pasta única de arquivos soltos.

**Pontos de atenção:**

- Usar o starter oficial do Laravel para Inertia + Vue. Não montar o Inertia na mão.
- Front-end com Yarn. Versionar `yarn.lock`. Se o instalador gerar `package-lock.json`, apagar esse arquivo e não versioná-lo. Script do Composer que sobe o Vite chama `yarn dev`.
- Nome interno da aplicação: Libris. Pode ser trocado depois num único lugar (`APP_NAME`).
- Não instalar pacote de pagamento, de permissão ou de admin pronto. O gateway será um cliente HTTP nosso.
- Fuso e locale da aplicação em `America/Sao_Paulo` e `pt_BR`.
- O repositório deste desafio não deve ser fork do repositório da empresa. Este ticket não cria o remoto.

**DoD:**

- `composer install`, `yarn install`, migração e `yarn dev` sobem sem erro.
- A rota inicial responde uma página Inertia.
- A estrutura de pastas citada existe.
- O README ainda pode estar mínimo; o texto de entrega é o Ticket 33.

---

## Ticket 02 — [AMBIENTE] - Subir aplicação e MySQL com Docker Compose

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 4 (Docker como prioridade média).
**Menções na documentação:** `Desafio Técnico`, seção 6 (Docker e Docker Compose) e seção 10 (instruções de execução local).

**Status:** Concluído
**Prioridade:** média.
**Depende de:** Ticket 01.

**Descrição:** Adicionar `compose.yaml` que sobe o PHP da aplicação, o servidor HTTP e o MySQL 8. O build da imagem instala e compila o front com Yarn. Documentar no README o comando único de subida e a alternativa sem Docker (`php artisan serve` + `yarn dev`), para quem já tem PHP na máquina.

**Explicação:** Quem for avaliar não precisa instalar PHP e MySQL na mão. Um comando sobe a livraria e o banco juntos. Quem já desenvolve em PHP local também tem um caminho curto.

**Pontos de atenção:**

- Não gastar o dia tunando imagem. Uma imagem que roda `php-fpm` + nginx, ou o servidor embutido do PHP, já vale.
- Senha do MySQL de desenvolvimento fica no `.env`, nunca hardcoded no compose com valor de produção.
- Volume nomeado para os dados do MySQL, senão cada `down` apaga os livros de exemplo.
- A porta do MySQL no host não pode ser a única forma de a aplicação conectar: dentro da rede do Compose o host do banco é o nome do serviço.

**DoD:**

- `docker compose up` deixa a home acessível no navegador e o MySQL aceitando a migration.
- Reiniciar os containers não perde o schema.
- O README descreve os dois jeitos de rodar: Compose e local.

---

## Ticket 03 — [AMBIENTE] - Guardar credenciais fora do código

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 3 (gateway).
**Menções na documentação:** `Desafio Técnico`, seção 4 (armazenamento seguro das credenciais) e seção 10 (`.env.example`, sem segredos no repositório).

**Status:** Concluído
**Prioridade:** obrigatória.
**Depende de:** Ticket 01.

**Descrição:** Criar `config/services.php` com a entrada do Juntos Se Faz e um `.env.example` completo. Variáveis: `JUNTOS_SE_FAZ_BASE_URL`, `JUNTOS_SE_FAZ_TOKEN`, `JUNTOS_SE_FAZ_WEBHOOK_SECRET`, `AFFILIATE_COMMISSION_PERCENT`, `AFFILIATE_COOKIE_DAYS`. Garantir que `.env` está no `.gitignore`.

**Explicação:** A chave do gateway é a fechadura da conta de pagamentos. Ela fica só na máquina de quem roda o projeto. O repositório mostra o nome das chaves, nunca o valor.

**Pontos de atenção:**

- Nenhum token, mesmo de sandbox, entra em commit, seed, teste ou frontend.
- O Vue não recebe a URL autenticada nem o token. Toda chamada ao gateway sai do Laravel.
- Valor padrão de comissão sugerido no example: `10`. Cookie: `7`.
- A base URL fica vazia no example, com comentário apontando a documentação oficial do gateway. Não inventar host.

**DoD:**

- `.env.example` lista todas as variáveis da aplicação, do banco, do gateway e de afiliados, sem valores secretos.
- `config('services.juntos_se_faz')` lê URL, token e segredo do webhook.
- Busca no repositório por um token real não encontra nada.

---

## Ticket 04 — [LOJA PÚBLICA] - Montar o layout base da livraria

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, abertura (identidade ligada a livros; nomes Libris, Lumi, Folium, Letria).
**Menções na documentação:** `Desafio Técnico`, seção 4 (interface funcional e responsiva) e seção 7 (criatividade e experiência do usuário, 10%).

**Status:** Pendente
**Prioridade:** obrigatória.
**Depende de:** Ticket 01.

**Descrição:** Criar o layout Inertia compartilhado: cabeçalho com marca Libris, busca visual (o filtro de verdade vem no Ticket 09), link para o catálogo, acesso à conta e ao carrinho, rodapé curto. Definir tipografia, cor e espaçamento num único lugar (CSS simples ou Tailwind, o que o starter já trouxer). Estados vazios e mensagens de flash (sucesso e erro) já entram neste layout.

**Explicação:** É a moldura da loja. Todas as telas seguintes reaproveitam o mesmo cabeçalho e o mesmo jeito de avisar se algo deu certo ou errado. A pessoa precisa entender, em um segundo, que está numa livraria, não num painel genérico.

**Pontos de atenção:**

- Responsivo de verdade: cabeçalho usável em largura de celular, sem menu que estoura a tela.
- Contraste de texto legível. Sem texto cinza-claro em fundo branco.
- Não baixar kit de UI pesado. O starter já traz ferramenta de estilo suficiente.
- Capas ainda são um espaço reservado. A imagem real do livro chega no Ticket 07.
- Textos da interface em português do Brasil.

**DoD:**

- Toda página autenticada e pública usa o mesmo layout.
- Em largura de celular e de desktop o cabeçalho não quebra.
- Uma mensagem flash de sucesso e uma de erro aparecem e somem de forma visível.
- A marca Libris está no cabeçalho e no título do navegador.
