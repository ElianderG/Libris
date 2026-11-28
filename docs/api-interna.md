# API da Conta — Documentação Interna

Documentação técnica destinada aos desenvolvedores responsáveis pela manutenção do Libris.

Este documento descreve a implementação, as regras de negócio, os mecanismos de autenticação e as restrições de segurança da API.

**Importante:** esta documentação é interna. Para consultar instruções de integração e utilização dos endpoints, consulte `docs/api.md`.

Nunca inclua chaves reais de API, tokens ou outros segredos neste documento.

---

## 1. Autenticação e gerenciamento de chaves

### Estrutura da chave

Uma chave de API é composta pelo prefixo `libris_` seguido de 40 caracteres aleatórios.

O texto completo da chave não é armazenado no banco de dados. São persistidos apenas:

- **Hash:** hash SHA-256 do texto completo da chave, utilizado para autenticação.
- **Prefixo:** os primeiros 12 caracteres da chave, utilizados para identificação visual.

A chave completa é exibida somente no momento da geração, por meio do primeiro flash da sessão. Após esse momento, não pode ser recuperada.

Na interface, a chave é apresentada parcialmente, com reticências, por exemplo: `libris_ab12c…`.

### Ciclo de vida

Cada conta pode possuir apenas uma chave ativa.

As operações de gerenciamento seguem estas regras:

- **Geração:** cria uma nova chave e revoga imediatamente a chave ativa anterior, caso exista.
- **Revogação:** preenche o campo `revoked_at` da chave correspondente.
- **Substituição:** invalida a chave anterior no mesmo processo de geração da nova.
- **Histórico:** as chaves revogadas permanecem no banco de dados. Nenhum registro é excluído durante essas operações.

A revogação é registrada por meio do campo `revoked_at`. Uma chave só pode autenticar requisições enquanto esse campo estiver nulo.

### Middleware de autenticação

Arquivo: `app/Http/Middleware/AuthenticateApiKey.php`

O middleware `AuthenticateApiKey` é responsável por autenticar as requisições da API.

Fluxo de autenticação:

1. Lê o cabeçalho HTTP `Authorization`.
2. Extrai a chave utilizando o esquema `Bearer`.
3. Calcula o hash SHA-256 do texto recebido.
4. Busca a chave correspondente no banco de dados, considerando apenas registros com `revoked_at` nulo.
5. Se a chave for válida, atualiza o campo `last_used_at` e permite a execução da requisição.
6. Caso contrário, interrompe a requisição e retorna `401 Unauthorized`.

Todas as falhas de autenticação retornam a mesma resposta:

```json
{
  "message": "Chave inválida."
}
```

A mensagem é intencionalmente genérica: não deve permitir identificar se determinado prefixo existe, se a chave foi revogada ou se o valor informado está incorreto.

O campo `last_used_at` é atualizado somente quando a autenticação é bem-sucedida.

### Isolamento da autenticação

A API utiliza exclusivamente a chave de API para autenticação.

- A sessão autenticada pelo navegador não concede acesso aos endpoints.
- Chaves enviadas pela URL ou pela query string não são aceitas.
- O cabeçalho `Authorization: Bearer` é o único mecanismo de autenticação previsto.

**Atenção ao contexto administrativo:** um administrador que gerar uma chave estará autenticando a própria conta, não terá acesso automático às contas de outros usuários.

Não há `Gate::before`. A chave não herda o acesso de administrador. Ver um pedido usa `OrderPolicy::view`. Revogar a chave usa `ApiKeyPolicy::revoke`, só para a chave da própria conta que ainda está ativa.

---

## 2. Segurança e exposição de dados

### Princípio de exposição mínima

A API deve retornar somente os dados necessários para consulta das informações da conta.

Os seguintes dados nunca devem ser incluídos nas respostas:

| Dado | Motivo |
|---|---|
| Conteúdo dos livros | Material protegido da plataforma. |
| Caminho da capa | Informação interna de armazenamento. |
| `raw_request` | Pode conter informações sensíveis enviadas ao gateway. |
| `raw_response` | Pode conter informações internas ou sensíveis retornadas pelo gateway. |
| Segredos do gateway | Credenciais de integração. |
| `user_agent` | Informação interna de rastreamento da requisição. |
| Token do clique | Identificador interno utilizado no rastreamento de indicações. |

Qualquer alteração nos recursos retornados deve preservar essa restrição.

### Isolamento por conta

Todas as consultas devem partir do `user_id` associado à chave autenticada.

O identificador da conta não deve ser recebido do consumidor da API.

Portanto:

- Parâmetros `user_id` enviados pela URL não devem alterar o escopo da consulta.
- Parâmetros `user_id` enviados pela query string devem ser ignorados.
- Nenhuma consulta pode utilizar um identificador fornecido pelo consumidor para selecionar outra conta.

O objetivo é garantir que uma chave só permita consultar os dados da conta à qual pertence.

### Autorização de pedidos

A consulta individual de pedidos utiliza a `OrderPolicy::view`.

Arquivo: `app/Policies/OrderPolicy.php`

A autorização considera a propriedade do pedido em relação à conta autenticada pela chave.

Caso o pedido não pertença à conta, a resposta deve ser `404 Not Found`, inclusive quando o identificador informado corresponde a um pedido existente de outro usuário.

Isso evita expor a existência de pedidos pertencentes a terceiros.

---

## 3. Regras de negócio dos endpoints

### `GET /api/v1/me`

Retorna os dados cadastrais da conta e os totais de compras e vendas.

Os critérios de contabilização são diferentes:

**Compras**
- Considera pedidos pertencentes à própria conta.
- Somente pedidos com status `paid` entram nos totais.
- Pedidos pendentes de pagamento via Pix não são contabilizados.

**Vendas**
- Considera comissões com status `confirmed`.
- Somente comissões confirmadas entram nos totais.

É importante não confundir pedidos existentes com compras efetivamente pagas: pedidos pendentes continuam aparecendo no histórico, mas não são contabilizados nos totais.

### `GET /api/v1/me/compras`

Lista os pedidos pertencentes à conta autenticada.

Regras:
- Ordenação do mais recente para o mais antigo.
- Paginação de 20 registros.
- Os itens são apresentados com os dados registrados no momento da compra.
- O histórico de pagamentos inclui as tentativas de Pix associadas ao pedido, respeitando os campos permitidos na resposta.

Pedidos pendentes também aparecem nessa listagem.

A consulta individual de um pedido utiliza o mesmo escopo de propriedade e as regras da `OrderPolicy::view`.

### `GET /api/v1/me/vendas`

Retorna três conjuntos de informações relacionados ao programa de afiliados:

**Cliques**
- Lista os cliques de indicação associados à conta autenticada.
- `product_title` traz o título do livro quando o clique foi na página do livro. Fora dela, o valor é nulo.

**Pedidos indicados**
- Considera pedidos pertencentes a outras pessoas.
- O pedido precisa estar atribuído à conta autenticada como indicação.
- O pedido deve possuir status `paid`.
- Deve existir um pagamento associado com status `paid`.

**Comissões**
- Lista somente comissões com status `confirmed`.

#### Cálculo dos totais

Os totais retornados são:

- `orders_cents`: soma dos valores dos pedidos pagos atribuídos à conta.
- `commissions_cents`: soma dos valores das comissões confirmadas.

Os totais representam os valores acumulados, independentemente da página atualmente consultada.

#### Paginação

O parâmetro `page` é compartilhado pelas três listas: `clicks`, `orders` e `commissions`.

Cada lista possui seus próprios metadados de paginação, mas utiliza o mesmo número de página informado na requisição.

#### Conta sem indicações

Uma conta sem registros de indicação não deve receber erro.

O endpoint deve retornar `200 OK`, com as três listas vazias e os totais zerados.

---

## 4. Limites e restrições operacionais

### Rate limiting

O limite é de 60 requisições por minuto.

A identificação do limite utiliza o identificador da chave (`api_key_id`), e não o endereço IP do consumidor.

Consequentemente:
- Cada chave possui seu próprio limite.
- Requisições de diferentes IPs utilizando a mesma chave compartilham o mesmo limite.
- Chaves diferentes possuem contadores independentes.
- Ao passar de 60 consultas no mesmo minuto, a resposta é `429 Too Many Requests`.

### Métodos e corpo das requisições

Todos os endpoints são exclusivamente de leitura.

- Não há endpoints de criação, atualização ou exclusão de recursos.
- Um método diferente de `GET` responde `405 Method Not Allowed`.
- Nenhum endpoint consome corpo de requisição.
- As operações de pagamento continuam sendo realizadas exclusivamente pelo checkout.
- A API não cria cobranças Pix nem altera pedidos.

Qualquer expansão futura deve preservar a separação entre a API de consulta e o fluxo de checkout.

---

## 5. Organização do código

Os principais arquivos envolvidos na implementação são:

| Arquivo | Responsabilidade |
|---|---|
| `app/Http/Middleware/AuthenticateApiKey.php` | Autenticação das requisições por chave de API. |
| `app/Http/Controllers/Api/AccountController.php` | Implementação dos endpoints de consulta da conta. |
| `app/Models/ApiKey.php` | Modelo e persistência das chaves de API. |
| `app/Policies/OrderPolicy.php` | Autorização de acesso aos pedidos. |
| `app/Policies/ApiKeyPolicy.php` | Autorização das operações relacionadas às chaves. |
| `routes/api.php` | Registro e configuração das rotas da API. |

### Fluxo geral de uma requisição

1. A requisição chega por uma rota registrada em `routes/api.php`.
2. O middleware `AuthenticateApiKey` valida a chave recebida.
3. Após a autenticação, a requisição é associada à conta proprietária da chave.
4. O `AccountController` executa a consulta solicitada.
5. As consultas respeitam o escopo da conta autenticada e as regras de autorização aplicáveis.
6. A resposta é serializada em JSON, contendo somente os campos permitidos.

---

## 6. Diretrizes para manutenção

Ao modificar ou adicionar funcionalidades à API, preserve os seguintes princípios:

1. **Isolamento por conta:** nenhuma consulta deve permitir acessar dados de outra conta.
2. **Exposição mínima:** não retornar informações internas, credenciais ou dados sensíveis.
3. **Autenticação independente:** não utilizar a sessão do navegador como alternativa à chave.
4. **Revogação imediata:** chaves revogadas não podem continuar autenticando requisições.
5. **Consultas somente de leitura:** não introduzir operações que alterem pedidos ou pagamentos.
6. **Consistência financeira:** manter a distinção entre pedidos pagos, pagamentos pendentes e comissões confirmadas.
7. **Respostas previsíveis:** preservar os códigos HTTP e os formatos de resposta documentados em `docs/api.md`.

Qualquer alteração no comportamento público dos endpoints também deve ser refletida na documentação de integração.