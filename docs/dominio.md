# Domínio da loja

Mapa do que o código grava. Quem pode ver cada registro está em `docs/autorizacao.md`. A conta, o e-mail verificado e as telas de configuração estão em `docs/conta.md`.

## Dinheiro

Preço, total do pedido, valor da tentativa de Pix e comissão ficam em centavos inteiros (`price_cents`, `total_cents`, `amount_cents`). A tela pede reais (`37,90` ou `37.90`) e a conversão acontece na borda: formulário do livro, chamada do gateway e texto exibido. O POST do checkout ignora um preço enviado pelo navegador. O total sai dos preços atuais do banco. No pedido, o preço do item fica congelado.

## Sacola

A sacola vive na sessão. Não há tabela de carrinho. Só entra livro publicado, sem repetir o título. Esvaziar ou remover um livro vale na sacola e no checkout, antes do Pix.

## Pedido e pagamento

São registros diferentes. O pedido é a compra. O pagamento é uma tentativa de liquidar esse pedido. Um pedido pode ter várias tentativas. Só uma fica `pending` por vez: ao gerar outro Pix, a pendente anterior passa a `canceled`.

O pedido guarda o gateway escolhido no checkout: `juntos_se_faz` ou `pagsmile`. **Gerar novo Pix** repete esse gateway. O CPF da Pagsmile vai só na chamada e não fica no pedido.

### Status do pedido

| Status | Significado |
|---|---|
| `pending_payment` | Aguardando pagamento. Nasce assim. |
| `paid` | Pago. Só a confirmação do Pix grava este status, uma vez. |

O pedido não tem `canceled` nem `expired`. Recusar, cancelar ou expirar a tentativa deixa o pedido em `pending_payment`.

### Status do pagamento

| Status | Significado |
|---|---|
| `pending` | Cobrança criada e ainda em aberto. |
| `paid` | Gateway, aviso ou ação do painel confirmou, e o valor bate com a tentativa. |
| `failed` | A criação da cobrança falhou, ou o sandbox recusou. |
| `canceled` | A tentativa foi encerrada. Também é o status da pendente substituída por um Pix novo. |
| `expired` | O Pix expirou. |

`failed`, `canceled` e `expired` não fecham o pedido. A página oferece **Gerar novo Pix** enquanto o pedido segue `pending_payment`.

A confirmação (consulta, aviso da Pagsmile ou **Aprovar** no painel) só marca pedido e pagamento como `paid` quando o valor corresponde ao da tentativa. Valor ausente na resposta da ação do painel também confirma. A segunda confirmação não altera `paid_at`. O aviso da Pagsmile só confirma depois que a consulta repete o mesmo valor.

## Comissão

Não há linha `pending`. A comissão nasce `confirmed` no momento em que o Pix confirma um pedido que tem afiliado, e só uma por pedido. O valor é o percentual de `AFFILIATE_COMMISSION_PERCENT` sobre `total_cents`, arredondado para baixo em centavos. Não há saque nem tela para mudar o percentual: o número continua o da configuração.

Sem afiliado no pedido, ou sem o Pix confirmar, a comissão não é criada.

## Biblioteca

Não há tabela de biblioteca. O livro está liberado quando existe item daquele produto num pedido `paid` daquela conta. Despublicar o livro tira ele da vitrine e mantém a leitura de quem pagou.

## O que a API devolve

`docs/api.md` é a API da Libris: a chave lê a própria conta. O Juntos Se Faz e a Pagsmile são os gateways que o checkout chama. A API não cria Pix.
