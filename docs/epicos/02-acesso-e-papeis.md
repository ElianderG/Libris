# Épico 02 — Acesso e papéis

**Objetivo:** cliente e administrador entram com segurança, e cada um cai na área certa.
**Prioridade:** alta. O edital lista autenticação como diferencial; a biblioteca e o admin dependem dela.
**Antes de:** checkout, biblioteca e painel.

---

## Ticket 05 — [CONTA] - Cadastro, entrada e saída

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (área do cliente).
**Menções na documentação:** `Desafio Técnico`, seção 6 (autenticação de usuários).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 01, Ticket 04.

**Descrição:** Instalar o fluxo de autenticação do starter (Breeze ou o equivalente atual do Laravel para Inertia Vue): cadastro, login, logout, edição de perfil e troca de senha. Campos visíveis: nome, e-mail e senha. No cadastro, gerar `affiliate_code` único (o uso do código é o Épico 08; aqui só nasce o código).

**Explicação:** A pessoa cria uma conta para comprar e, depois, achar os livros dela. Sem conta, a loja até mostra os títulos, mas não tem como saber de quem é a biblioteca.

**Pontos de atenção:**

- Senha com hash do Laravel. Nunca texto puro.
- Validação no servidor, mesmo que o Vue também avise: nome obrigatório, e-mail válido e único, senha com confirmação e tamanho mínimo do Laravel.
- Mensagens de validação em português.
- Não construir recuperação de senha por e-mail se isso exigir serviço de e-mail externo. O fluxo de "esqueci a senha" do starter pode ficar desligado se depender de SMTP.
- O código de afiliado é curto, legível e único. Sugestão: 8 caracteres sem caracteres ambíguos (`0/O`, `1/I`). Gerar no model ao criar o usuário, com nova tentativa se colidir.

**DoD:**

- Uma pessoa nova se cadastra, entra, edita o nome e sai.
- E-mail repetido e senha curta são recusados com mensagem na tela.
- Cada usuário novo tem `affiliate_code` único no banco.
- Visitante que abre uma rota autenticada vai para o login.

---

## Ticket 06 — [CONTA] - Separar cliente e administrador

**Menções na transcrição:** Não há transcrição. Origem: proposta de produto, seção 1 (área do cliente e painel administrativo).
**Menções na documentação:** `Desafio Técnico`, seção 6 (autenticação) e seção 4 (validação dos dados).

**Status:** Concluído
**Prioridade:** alta.
**Depende de:** Ticket 05.

**Descrição:** Adicionar `users.is_admin` (falso por padrão). Middleware `admin` protege o prefixo `/admin`. Depois do login, administrador vai para `/admin` e cliente vai para a biblioteca. Seed de um administrador local documentado no `.env.example` (`ADMIN_EMAIL` e `ADMIN_PASSWORD`), criado por seeder apenas quando essas variáveis existem.

**Explicação:** As duas pessoas que usam o sistema não veem a mesma casa. Quem compra vê os próprios livros. Quem cuida da loja vê o painel. A senha do administrador de desenvolvimento não fica escrita no código.

**Pontos de atenção:**

- Esconder o link do painel não é proteção. A rota `/admin` recusa quem não é admin com 403.
- Cliente não pode se promover. Não existe campo `is_admin` em formulário de perfil.
- O seeder não cria admin se a senha do ambiente estiver vazia, para o exemplo do repositório não nascer com senha conhecida.
- Gate simples: `Gate::before` liberando admin, mais policies nos tickets de recurso. Sem Spatie ou pacote parecido.

**DoD:**

- Com as variáveis preenchidas, `db:seed` cria um admin e esse usuário entra no `/admin`.
- Um cliente que digita `/admin` recebe 403.
- O perfil do cliente não altera `is_admin`.
- `.env.example` documenta `ADMIN_EMAIL` e `ADMIN_PASSWORD` vazios.
