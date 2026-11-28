# Conta

Quem entra, o que cada tela da conta faz, e como nasce o administrador local. O mapa de permissão está em `docs/autorizacao.md`. Pedido, Pix e comissão estão em `docs/dominio.md`. A chave da API está em `docs/api.md`.

## Entrada

O cadastro, o login, a recuperação de senha e a verificação de e-mail vêm do Fortify. Conta nova nasce com `is_admin` falso e com código de afiliado.

Depois do login, administrador vai para `/admin`. Cliente vai para `/biblioteca`. Se a sessão guardou um endereço de destino, o login volta para esse endereço.

**Continuar com Google** só aparece com `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` preenchidos. O redirect é `GOOGLE_REDIRECT_URI`. E-mail que o Google não confirma não entra. E-mail já cadastrado entra na conta existente e marca o e-mail como verificado. A conta não vira administrador por causa do Google. Se o e-mail já está ligado a outro `google_id`, o login é recusado.

## E-mail verificado

`User` implementa verificação de e-mail. No `.env` de exemplo, `MAIL_MAILER=log`: o link sai em `storage/logs/laravel.log`, não numa caixa de entrada.

Quem entrou pelo Google, ou o administrador criado pelo seeder, já nasce com o e-mail verificado. Trocar o e-mail em Perfil zera essa confirmação.

| Área | Sem login | Logado, e-mail por confirmar | E-mail verificado |
|---|---|---|---|
| Vitrine, sacola | Sim | Sim | Sim |
| Checkout, compras, Indicar, chave da API | Login | Sim | Sim |
| Biblioteca (`/biblioteca`) | Login | Pede a confirmação | Sim, se pagou o livro |
| Perfil e foto (`/settings/profile`) | Login | Sim | Sim |
| Senha, autenticação em dois fatores, passkey, aparência, apagar a conta | Login | Pede a confirmação | Sim |
| Painel `/admin` | Login | Só `is_admin` | Só `is_admin` |

A biblioteca exige as duas coisas: e-mail verificado e pedido `paid` daquele livro. Quem não pagou recebe 403.

## Menu da loja

A aba **Meus Livros** no cabeçalho da vitrine abre `/biblioteca`, a estante dos livros já pagos. No celular ela fica no menu. Quem não entrou vai para o login.

O avatar no cabeçalho da vitrine abre:

| Item | Quem vê | Endereço |
|---|---|---|
| Painel | Só `is_admin` | `/admin` |
| Minha conta | Qualquer autenticado | `/biblioteca` |
| Compras | Qualquer autenticado | `/minha-conta/compras` |
| Indicar | Qualquer autenticado | `/minha-conta/afiliado` |
| Chave da API | Qualquer autenticado | `/minha-conta/configuracoes` |
| Configurações | Qualquer autenticado | `/settings/profile` |
| Sair | Qualquer autenticado | Encerra a sessão |

`/dashboard` é a página vazia do starter. O menu da loja não aponta para ela. O painel da livraria é `/admin`.

## Duas áreas de configuração

**Chave da API** fica em `/minha-conta/configuracoes`. Gerar, ver o prefixo e revogar. O manual de uso está em `docs/api.md`. O armazenamento da chave está em `docs/api-interna.md`.

**Configurações** fica em `/settings`, com três abas:

- **Perfil** (`/settings/profile`). Nome, e-mail e fotos. A foto pede `/avatars/{id}` e a aplicação devolve o JPEG. Dá para enviar, cortar, escolher a padrão e apagar. Cada conta guarda até 8 fotos. O envio aceita 12 requisições por minuto.
- **Segurança** (`/settings/security`). Pede a senha de novo antes de abrir. Troca de senha, autenticação em dois fatores e passkey. A passkey também se anuncia em `/.well-known/passkey-endpoints`.
- **Aparência** (`/settings/appearance`). Claro, escuro ou o tema do sistema. O mesmo ajuste existe no botão da vitrine e fica no cookie `appearance`.

Apagar a conta está no perfil e exige e-mail verificado. A própria conta não se promove nem se apaga pelo painel.

## Administrador local

O `php artisan db:seed` cria o administrador só quando `ADMIN_EMAIL` e `ADMIN_PASSWORD` têm valor no `.env`. As duas chaves ficam vazias no `.env.example`. Sem elas, o seeder carrega o catálogo e não cria conta.

A conta criada recebe `is_admin`, e-mail já verificado, nome `Administrador` se ainda não existir, e um código de afiliado. Rodar o seeder de novo não duplica esse e-mail e não tira o acesso de administrador.
