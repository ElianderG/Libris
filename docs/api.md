# API da Conta

Documentação de integração para desenvolvedores que desejam consultar os dados da própria conta por meio da API.

## Visão geral

A API permite consultar informações da conta associada à chave de autenticação, incluindo dados cadastrais, compras, vendas por indicação e comissões.

**A API é exclusivamente de consulta:** não cria cobranças, não altera pedidos e não permite modificar dados da conta.

Por segurança, as respostas não incluem o conteúdo dos livros nem informações internas ou sensíveis dos pagamentos.

### Autenticação

Todas as requisições devem incluir uma chave válida no cabeçalho HTTP `Authorization`, utilizando o esquema Bearer:

```http
Authorization: Bearer SEU_TOKEN
```

A autenticação é independente da sessão do navegador. Estar conectado à plataforma não dispensa o envio da chave.

Se a chave estiver ausente, for inválida ou tiver sido revogada, a API retornará HTTP `401 Unauthorized`:

```json
{
  "message": "Chave inválida."
}
```

**Importante:** mantenha sua chave em segurança. Qualquer pessoa que tenha acesso a ela poderá consultar os dados da conta correspondente.

Nos exemplos desta documentação, `SEU_TOKEN` representa sua chave de API.

### Convenções e limites

- **Valores monetários:** todos os valores são retornados em centavos. Por exemplo, `3790` representa R$ 37,90.
- **Paginação:** as listas retornam até 20 registros por página. Utilize o parâmetro `page` para navegar entre elas.
- **Corpo da requisição:** os endpoints não aceitam corpo (`request body`), pois são exclusivamente de consulta.
- **Limite de requisições:** 60 requisições por minuto, por chave de API.
- **Formato das respostas:** os dados são retornados em JSON.

---

## Gerenciamento da chave de API

### Como gerar sua chave

1. Acesse sua conta na plataforma.
2. No menu do avatar, abra **Chave da API**. O endereço é `/minha-conta/configuracoes`.
3. Clique em **Gerar chave**.
4. Copie e armazene a chave imediatamente. Por segurança, o valor completo será exibido apenas uma vez.
5. Após sair ou recarregar a página, somente o início da chave será exibido, no formato `libris_ab12c…`.

Todas as chaves geradas começam com o prefixo `libris_`.

### Revogação e substituição

- **Revogar:** invalida a chave atual e deixa a conta sem uma chave ativa.
- **Gerar outra chave:** cria uma nova chave e invalida imediatamente a anterior.

Após a revogação, qualquer requisição realizada com a chave antiga receberá uma resposta `401 Unauthorized`.

---

## Endpoints

### 1. Consultar dados da conta

`GET /api/v1/me`

Retorna os dados cadastrais da conta autenticada, o código de afiliado e os totais de compras e vendas.

Os totais consideram exclusivamente operações confirmadas:

- **Compras:** pedidos realizados pela própria conta com status `paid`.
- **Vendas:** comissões de afiliados confirmadas.

Pedidos que ainda aguardam pagamento via Pix não são contabilizados nos totais de compras.

#### Requisição

```bash
curl -H "Authorization: Bearer SEU_TOKEN" \
http://localhost:8000/api/v1/me
```

#### Resposta — `200 OK`

```json
{
  "name": "Ana",
  "email": "ana@example.com",
  "affiliate_code": "ABCD2345",
  "purchases": {
    "count": 1,
    "total_cents": 3790
  },
  "sales": {
    "count": 1,
    "total_cents": 379
  }
}
```

#### Campos retornados

| Campo | Descrição |
|---|---|
| `name` | Nome da conta. |
| `email` | E-mail cadastrado. |
| `affiliate_code` | Código de afiliado da conta. |
| `purchases.count` | Quantidade de compras pagas. |
| `purchases.total_cents` | Valor total das compras pagas, em centavos. |
| `sales.count` | Quantidade de comissões confirmadas. |
| `sales.total_cents` | Valor total das comissões confirmadas, em centavos. |

**Erros possíveis:**

- `401 Unauthorized`: chave ausente, inválida ou revogada.

---

### 2. Listar compras

`GET /api/v1/me/compras`

Retorna o histórico de pedidos realizados pela conta autenticada, ordenados do mais recente para o mais antigo.

Cada pedido inclui:
- Identificação e status do pedido.
- Valor total da compra.
- Data de criação.
- Itens adquiridos, com seus respectivos títulos, preços unitários e quantidades.
- Histórico de tentativas de pagamento via Pix, incluindo seus status e datas.

Os itens são apresentados com os dados registrados no momento da compra, preservando as informações originais do pedido.

#### Parâmetros

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `page` | Integer | Não | Número da página. Padrão: `1`. |

#### Requisição

```bash
curl -H "Authorization: Bearer SEU_TOKEN" \
"http://localhost:8000/api/v1/me/compras?page=1"
```

#### Resposta — `200 OK`

```json
{
  "data": [
    {
      "id": 10,
      "status": "paid",
      "total_cents": 3790,
      "created_at": "2026-11-28T09:00:00-03:00",
      "items": [
        {
          "title": "A janela",
          "unit_price_cents": 3790,
          "quantity": 1
        }
      ],
      "payments": [
        {
          "id": 12,
          "status": "paid",
          "amount_cents": 3790,
          "paid_at": "2026-11-28T09:05:00-03:00",
          "created_at": "2026-11-28T09:00:00-03:00"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

#### Estrutura da resposta

| Campo | Descrição |
|---|---|
| `data` | Lista de pedidos da conta. |
| `data[].id` | Identificador do pedido. |
| `data[].status` | Status atual do pedido. |
| `data[].total_cents` | Valor total do pedido, em centavos. |
| `data[].created_at` | Data e horário de criação. |
| `data[].items` | Itens registrados no pedido. |
| `data[].items[].title` | Título do produto adquirido. |
| `data[].items[].unit_price_cents` | Preço unitário no momento da compra, em centavos. |
| `data[].items[].quantity` | Quantidade adquirida. |
| `data[].payments` | Histórico de tentativas de pagamento. |
| `data[].payments[].id` | Identificador do pagamento. |
| `data[].payments[].status` | Status da tentativa de pagamento. |
| `data[].payments[].amount_cents` | Valor da tentativa de pagamento, em centavos. |
| `data[].payments[].paid_at` | Data e horário da confirmação do pagamento. Pode ser nulo quando não houver confirmação. |
| `data[].payments[].created_at` | Data e horário de criação da tentativa. |
| `meta` | Informações de paginação. |

#### Paginação

O objeto `meta` contém:

| Campo | Descrição |
|---|---|
| `current_page` | Página atual. |
| `per_page` | Quantidade máxima de registros por página. |
| `total` | Quantidade total de registros disponíveis. |
| `last_page` | Número da última página disponível. |

**Erros possíveis:**

- `401 Unauthorized`: chave ausente, inválida ou revogada.

---

### 3. Consultar uma compra específica

`GET /api/v1/me/compras/{order}`

Retorna os detalhes de um pedido específico, utilizando o mesmo formato de resposta do endpoint de listagem de compras.

A consulta é restrita aos pedidos pertencentes à conta autenticada. Portanto, não é possível consultar pedidos de outros usuários, mesmo conhecendo seus identificadores.

#### Parâmetros

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `order` | Integer | Sim | Identificador do pedido que deseja consultar. |

#### Requisição

```bash
curl -H "Authorization: Bearer SEU_TOKEN" \
http://localhost:8000/api/v1/me/compras/10
```

#### Resposta — `200 OK`

Retorna um objeto de pedido com a mesma estrutura de um registro presente em `data` no endpoint `/api/v1/me/compras`.

#### Erros possíveis

- `401 Unauthorized`: chave ausente, inválida ou revogada.
- `404 Not Found`: pedido inexistente ou pertencente a outra conta.

---

### 4. Consultar vendas e indicações

`GET /api/v1/me/vendas`

Retorna informações sobre o desempenho do programa de afiliados da conta autenticada.

A resposta é dividida em três listas:

- **Cliques (`clicks`):** registros de cliques em links de indicação.
- **Pedidos (`orders`):** pedidos pagos realizados por pessoas que acessaram os produtos por meio dos links de indicação da conta.
- **Comissões (`commissions`):** comissões confirmadas e associadas aos pedidos indicados.

Pedidos que ainda aguardam pagamento não são contabilizados como vendas ou comissões.

Se a conta ainda não tiver realizado indicações, o endpoint retornará normalmente `200 OK`, com listas vazias e totais zerados.

#### Parâmetros

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `page` | Integer | Não | Número da página. Padrão: `1`. |

O parâmetro `page` é compartilhado pelas três listas. Todas utilizarão o mesmo número de página na resposta.

#### Requisição

```bash
curl -H "Authorization: Bearer SEU_TOKEN" \
"http://localhost:8000/api/v1/me/vendas"
```

#### Resposta — `200 OK`

```json
{
  "clicks": {
    "data": [
      {
        "id": 3,
        "product_title": "A janela",
        "created_at": "2026-11-28T08:00:00-03:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 1,
      "last_page": 1
    }
  },
  "orders": {
    "data": [
      {
        "id": 10,
        "total_cents": 3790,
        "commission_cents": 379,
        "created_at": "2026-11-28T09:00:00-03:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 1,
      "last_page": 1
    }
  },
  "commissions": {
    "data": [
      {
        "id": 1,
        "order_id": 10,
        "base_cents": 3790,
        "percent": 10,
        "amount_cents": 379,
        "created_at": "2026-11-28T09:05:00-03:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 1,
      "last_page": 1
    }
  },
  "totals": {
    "orders_cents": 3790,
    "commissions_cents": 379
  }
}
```

#### Estrutura da resposta

**`clicks` — Cliques de indicação**

| Campo | Descrição |
|---|---|
| `data[].id` | Identificador do clique. |
| `data[].product_title` | Título do livro, quando o clique foi na página do livro. Fora dela, o valor é `null`. |
| `data[].created_at` | Data e horário do clique. |
| `meta` | Informações de paginação. |

**`orders` — Pedidos indicados**

| Campo | Descrição |
|---|---|
| `data[].id` | Identificador do pedido. |
| `data[].total_cents` | Valor total do pedido, em centavos. |
| `data[].commission_cents` | Valor da comissão associada ao pedido, em centavos. |
| `data[].created_at` | Data e horário de criação do pedido. |
| `meta` | Informações de paginação. |

**`commissions` — Comissões confirmadas**

| Campo | Descrição |
|---|---|
| `data[].id` | Identificador da comissão. |
| `data[].order_id` | Identificador do pedido relacionado. |
| `data[].base_cents` | Valor utilizado como base para o cálculo da comissão, em centavos. |
| `data[].percent` | Percentual aplicado à comissão. |
| `data[].amount_cents` | Valor da comissão confirmada, em centavos. |
| `data[].created_at` | Data e horário de registro da comissão. |
| `meta` | Informações de paginação. |

**`totals` — Totais acumulados**

| Campo | Descrição |
|---|---|
| `orders_cents` | Soma dos valores dos pedidos pagos atribuídos à conta, em centavos. |
| `commissions_cents` | Soma dos valores das comissões confirmadas, em centavos. |

**Erros possíveis:**

- `401 Unauthorized`: chave ausente, inválida ou revogada.

---

## Resumo dos endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/me` | Consulta os dados da conta e seus totais. |
| `GET` | `/api/v1/me/compras` | Lista os pedidos realizados pela conta. |
| `GET` | `/api/v1/me/compras/{order}` | Consulta os detalhes de um pedido específico. |
| `GET` | `/api/v1/me/vendas` | Consulta cliques, pedidos indicados e comissões. |

## Códigos HTTP

| Código | Significado | Quando ocorre |
|---|---|---|
| `200 OK` | Requisição realizada com sucesso. | Consulta concluída, inclusive quando não existem registros. |
| `401 Unauthorized` | Falha na autenticação. | Chave ausente, inválida ou revogada. |
| `404 Not Found` | Recurso não encontrado. | Pedido inexistente ou que não pertence à conta autenticada. |
| `405 Method Not Allowed` | Método não permitido. | A rota foi chamada com um método diferente de `GET`. |
| `429 Too Many Requests` | Limite de requisições excedido. | A chave passou de 60 consultas no mesmo minuto. |

---

**Observação final:** todos os endpoints desta API são somente de leitura (`read-only`). As informações retornadas estão sempre vinculadas à conta proprietária da chave utilizada na autenticação.