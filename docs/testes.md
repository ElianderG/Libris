# Testes

A suíte é Pest. Ela cobre o fluxo do dinheiro, da biblioteca, da conta e do painel sem token real e sem internet.

## Como rodar

A suíte inteira:

```bash
php artisan test
```

Um arquivo ou um nome:

```bash
php artisan test --filter=AccountApiTest
php artisan test --filter='CheckoutTest|CartTest'
```

`phpunit.xml` força o ambiente de teste: SQLite em memória, fila síncrona, e-mail em memória, cache e sessão em array. O `tests/TestCase.php` bloqueia HTTP que o teste não declarou (`Http::preventStrayRequests`) e não sobe o Vite. Chamada de gateway nos testes é resposta falsa. Credencial do `.env` não entra na suíte.

O front, à parte:

```bash
yarn types:check
```

`composer test` faz mais do que a suíte: limpa a config, roda o Pint em modo de checagem, o PHPStan e só então o `php artisan test`.

## O que a suíte segura

- Catálogo, página do livro e sacola, com o total lido do banco.
- Pedido com preço congelado. Preço enviado no POST do checkout não entra.
- Pix nos dois gateways: criação, consulta, nova tentativa, aviso da Pagsmile e a confirmação única.
- Comissão uma vez, arredondada para baixo, sem comissão na autoindicação.
- Biblioteca só para quem pagou. Pedido alheio não abre.
- Chave da API: hash, revogação e leitura só da própria conta.
- Painel: acesso, catálogo, pedidos, usuários e afiliados.
- Login com Google sem chamar o Google de verdade.

Não há teste que fale com o sandbox. Um token verdadeiro no `.env` não muda isso.
