# Autorização — Gate, Policy e middleware

Mapa do que existe hoje. No código estão o gate `access-admin`, os middlewares, e as policies de livro, pedido, categoria, usuário, avatar e chave da API.

O livro no código é o model `Product`. Não existe `User::books()`. Quem pagou é um item de pedido com status `paid`.

## Os três mecanismos

| Mecanismo | Pergunta que responde | Onde mora |
|---|---|---|
| Middleware | Quem é a pessoa, e ela pode entrar nesta área? | Rota |
| Gate | Esta pessoa tem uma ability que não é um registro? | `AppServiceProvider` |
| Policy | Esta pessoa pode fazer isto com este registro? | `app/Policies`, chamada com `$this->authorize()` |

Middleware não olha o `{order}` nem o `{slug}`. Policy não autentica a chave da API nem o webhook. Gate não substitui a policy do livro: ability sem model vira gate; ability com model vira método da policy, inclusive `create` e `viewAny`, que não recebem o registro.

## O gate `access-admin`

```33:33:app/Providers/AppServiceProvider.php
        Gate::define('access-admin', fn (User $user): bool => $user->is_admin === true);
```

Não há `Gate::before`. Administrador não herda ability de cliente. Ler um livro, ver um pedido na loja e revogar uma chave passam pela policy. O painel entra pelo gate `access-admin`, e cada ação do painel repete a regra na policy.

## Quem acessa o quê

Papéis reais: visitante, cliente (`is_admin` falso) e administrador. Não há papel de afiliado: todo usuário tem `affiliate_code`. "Dono" é `user_id` do registro, ou um pedido `paid` que contém o produto.

| Recurso | Visitante | Cliente | Administrador |
|---|---|---|---|
| Vitrine, prévia do livro, sacola | Sim | Sim | Sim, como cliente |
| Checkout e criação do pedido | Não | O próprio | O próprio, sem ver pedido alheio |
| Tela do Pix, nova tentativa, compras | Não | Só o próprio pedido | Só o próprio pedido nas rotas de cliente |
| Ler o livro (`/biblioteca/{slug}`) | Não | Só se pagou | Só se ele mesmo pagou |
| Foto de perfil (trocar e apagar) | Não | Só a própria | Só a própria |
| Arquivo `/avatars/{id}` | Sim | Sim | Sim |
| Indicar (`/minha-conta/afiliado`) | Não | Só as próprias comissões | Só as próprias, nesta rota |
| Painel `/admin` | Não | Não | Sim |
| Categorias e livros no painel | Não | Não | Sim |
| Qualquer pedido no painel, inclusive baixar o Pix | Não | Não | Sim |
| Promover ou apagar outra conta | Não | Não | Sim, nunca a própria |
| Chave da API (Ticket 34) | Não | A própria | A própria, sem poder extra |
| `GET /api/v1/me/...` (Ticket 35) | Não | Dados da chave | Dados da chave, não da loja |
| Webhook do gateway | Sem usuário; vale a assinatura | — | — |

A vitrine não usa policy. O visitante não é um `User`, e o corte da prévia já acontece na query do livro publicado.

## Gate

Uma ability só.

| Ability | Regra | Quem consome |
|---|---|---|
| `access-admin` | `$user->is_admin === true` | O middleware `admin` passa a chamar `$request->user()?->can('access-admin')` em vez de ler a coluna direto |

Não criar `manage-books`, `manage-orders` nem `manage-users`. Isso é método de policy.

## Policies

Cada método abaixo é a única regra daquela ability. Controller e `FormRequest::authorize()` chamam a ability; não repetem o `if`.

### `ProductPolicy`

Leitura do conteúdo comprado e escrita no painel. São abilities diferentes de propósito.

| Ability | Assinatura | Regra |
|---|---|---|
| `read` | `(User $user, Product $product)` | Existe item desse produto num pedido `paid` desse usuário |
| `create` | `(User $user)` | `$user->is_admin` |
| `update` | `(User $user, Product $product)` | `$user->is_admin` |
| `delete` | `(User $user, Product $product)` | `$user->is_admin` |
| `unpublish` | `(User $user, Product $product)` | `$user->is_admin` |

`read` é o caso do exemplo. A relação pronta no model é `orders()`, não `books()`:

```php
public function read(User $user, Product $product): bool
{
    return $user->orders()
        ->where('status', Order::STATUS_PAID)
        ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
        ->exists();
}
```

Na leitura:

```php
$this->authorize('read', $product);
```

Hoje essa query está em `LibraryController::show`. O 403 de quem não pagou e o 404 de slug inexistente continuam: primeiro acha o produto, depois autoriza.

`delete` não olha se o livro já foi vendido. Livro com item de pedido continua recusado no controller, com a mensagem atual. Isso é regra de negócio, não de acesso.

`BookRequest::authorize()` deixa de comparar `is_admin` e usa `create` ou `update` conforme a rota.

### `OrderPolicy`

A rota do cliente e a rota do painel não compartilham ability. Admin vê todo pedido em `/admin/pedidos/{order}` e continua recebendo 404 em `/minha-conta/compras/{order}` de outra pessoa.

| Ability | Regra | Onde |
|---|---|---|
| `view` | `$order->user_id === $user->id` | API, comprovante e página do Pix, com 404 |
| `viewAny` | usuário autenticado | Só documenta a lista. A query de `/minha-conta/compras` segue com `where user_id` |
| `review` | `$user->is_admin` | `Admin\OrderController::show` |
| `settle` | `$user->is_admin` | `Admin\OrderController::settle` |

`view` negada responde **404**, como hoje, para não confirmar que o pedido existe. `$this->authorize()` responde 403. Nas rotas de cliente:

```php
abort_unless($request->user()?->can('view', $order), 404);
```

`review` e `settle` podem usar `$this->authorize()`, que responde 403. O pagamento que não pertence ao pedido continua 404 no controller: é integridade da URL, não papel.

Cobrar de novo (`ChargeOrder`) segue no service. A policy só garante que quem chama é o dono.

### `CategoryPolicy`

| Ability | Regra |
|---|---|
| `create`, `update`, `delete` | `$user->is_admin` |

`CategoryRequest::authorize()` chama a mesma ability. Apagar categoria com livros vinculados continua no controller.

### `UserPolicy`

| Ability | Regra |
|---|---|
| `promote` | admin, e o alvo não é ele mesmo |
| `delete` | admin, e o alvo não é ele mesmo |

Conta com pedido ou comissão continua recusada no `UserController`, com a frase que já aparece na tela. A policy não devolve 403 no lugar dessa frase.

O perfil (`name`, e-mail, senha) não entra aqui. A rota já é `auth`, e o controller só altera o usuário da sessão. Não há `{user}` de outra pessoa.

### `UserAvatarPolicy`

| Ability | Regra |
|---|---|
| `update`, `delete` | `$avatar->user_id === $user->id` |

Negada responde 404, no mesmo padrão do pedido. `GET /avatars/{id}` fica público: a foto é a URL que o navegador pede. A policy não fecha esse arquivo.

### `ApiKeyPolicy`

| Ability | Regra |
|---|---|
| `revoke` | a chave pertence ao usuário da sessão e ainda não tem `revoked_at` |

Gerar a chave não recebe um model: a rota `auth` basta. Não há ability "ver a chave completa de novo".

No Ticket 35, o middleware da chave descobre o usuário. `GET /api/v1/me/compras/{order}` reutiliza `OrderPolicy::view` com 404. A chave não ganha `review` nem `settle`.

## O que permanece middleware

| Middleware | Função |
|---|---|
| `auth` | Existe sessão. Checkout, compras, afiliado, perfil, chave da API |
| `guest` | Login com Google |
| `verified` | Biblioteca, dashboard, senha, exclusão da conta |
| `admin` | Porta do `/admin`, passando a usar o gate `access-admin` |
| `throttle` | Avatar, senha, webhook, e a API: 60 por minuto por chave |
| `RequirePassword` | Tela de segurança |

`/biblioteca/{slug}` está em `auth` e `verified`, igual à lista da biblioteca. Quem não pagou recebe 403 da `ProductPolicy::read`. Visitante vai para o login.

Webhook, sacola e catálogo não ganham policy. Não há usuário dono do webhook, e a sacola é sessão.

## O que não vira policy

- Filtro de listagem (`where user_id` em compras, comissões e biblioteca). A policy do `show` cobre o registro aberto pela URL.
- Status do Pix, CPF da Pagsmile, pedido repetido, preço congelado. Isso fica no service.
- Assinatura do webhook.
- Esconder o link do painel no Vue. O Ticket 06 já trata isso como insuficiente. O Inertia não precisa receber uma matriz de `can`.

## Ordem que entrou no código

1. O gate `access-admin` substituiu o `Gate::before`. O middleware `admin` chama esse gate.
2. `ProductPolicy::read` na leitura da biblioteca.
3. `OrderPolicy::view` nas rotas de cliente, com 404.
4. `review`, `settle`, categoria, usuário e avatar nas rotas do painel e do perfil.
5. `ApiKeyPolicy` e `OrderPolicy::view` na API da conta.
