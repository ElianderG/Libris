<?php

use App\Models\AffiliateClick;
use App\Models\ApiKey;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('recusa sessão, chave ausente, chave errada e chave revogada', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);
    $key = ApiKey::query()->first();
    $key->revoked_at = now();
    $key->save();

    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Chave inválida.']);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Chave inválida.']);

    $this->withToken('libris_chave_que_nao_existe')
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Chave inválida.']);

    $this->getJson('/api/v1/me?token='.$plain)
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Chave inválida.']);

    $this->withToken($plain)
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Chave inválida.']);

    expect($key->fresh()->last_used_at)->toBeNull();
});

it('devolve a conta e os totais do que foi pago', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);
    $other = User::factory()->create();

    accountApiOrder($user, Order::STATUS_PAID, Payment::STATUS_PAID, 3790);
    accountApiOrder($user, Order::STATUS_PENDING_PAYMENT, Payment::STATUS_PENDING, 1000);
    accountApiOrder($other, Order::STATUS_PAID, Payment::STATUS_PAID, 9000);

    $this->withToken($plain)
        ->getJson('/api/v1/me?user_id='.$other->id)
        ->assertOk()
        ->assertJsonPath('name', $user->name)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('affiliate_code', $user->affiliate_code)
        ->assertJsonPath('purchases.count', 1)
        ->assertJsonPath('purchases.total_cents', 3790)
        ->assertJsonPath('sales.count', 0)
        ->assertJsonPath('sales.total_cents', 0)
        ->assertJsonMissingPath('is_admin');

    expect(ApiKey::query()->where('user_id', $user->id)->first()->last_used_at)->not->toBeNull();
});

it('lista as compras com o status do pix e esconde o livro e o payload', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);
    $product = Product::factory()->create([
        'title' => 'Janela',
        'content' => 'CONTEUDO_INTEGRAL_DO_LIVRO',
        'description' => 'SINOPSE_SECRETA',
        'cover_path' => 'covers/arquivo-secreto.webp',
    ]);

    [$order, $payment] = accountApiOrder($user, Order::STATUS_PAID, Payment::STATUS_PAID, 3790);
    $payment->raw_request = ['secret' => 'SEGREDO_DO_GATEWAY'];
    $payment->raw_response = ['copy' => 'PAYLOAD_CRU_DO_GATEWAY'];
    $payment->save();

    $item = new OrderItem;
    $item->order_id = $order->id;
    $item->product_id = $product->id;
    $item->title = 'Janela';
    $item->unit_price_cents = 3790;
    $item->quantity = 1;
    $item->save();

    foreach (range(1, 20) as $ignored) {
        accountApiOrder($user, Order::STATUS_PENDING_PAYMENT, Payment::STATUS_PENDING, 100);
    }

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras?user_id=999')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 21)
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('data.0.payments.0.status', Payment::STATUS_PENDING)
        ->assertDontSee('CONTEUDO_INTEGRAL_DO_LIVRO', false)
        ->assertDontSee('SINOPSE_SECRETA', false)
        ->assertDontSee('arquivo-secreto.webp', false)
        ->assertDontSee('SEGREDO_DO_GATEWAY', false)
        ->assertDontSee('PAYLOAD_CRU_DO_GATEWAY', false)
        ->assertDontSee('raw_response', false);

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras?page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id)
        ->assertJsonPath('data.0.items.0.title', 'Janela')
        ->assertJsonPath('data.0.items.0.unit_price_cents', 3790)
        ->assertJsonPath('data.0.payments.0.status', Payment::STATUS_PAID)
        ->assertJsonPath('data.0.total_cents', 3790);

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras/'.$order->id)
        ->assertOk()
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('payments.0.status', Payment::STATUS_PAID)
        ->assertDontSee('CONTEUDO_INTEGRAL_DO_LIVRO', false)
        ->assertDontSee('PAYLOAD_CRU_DO_GATEWAY', false);
});

it('devolve em compras só os pedidos do dono da chave', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $plain = ApiKey::issue($owner);
    [$own] = accountApiOrder($owner, Order::STATUS_PAID, Payment::STATUS_PAID, 3790);
    accountApiOrder($other, Order::STATUS_PAID, Payment::STATUS_PAID, 9000);

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $own->id)
        ->assertJsonPath('data.0.total_cents', 3790);
});

it('responde 404 para pedido de outra pessoa ou inexistente', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $plain = ApiKey::issue($other);
    [$order] = accountApiOrder($owner, Order::STATUS_PAID, Payment::STATUS_PAID, 3790);

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras/'.$order->id)
        ->assertNotFound();

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras/999999')
        ->assertNotFound();

    expect($other->can('view', $order))->toBeFalse()
        ->and($owner->can('view', $order))->toBeTrue();
});

it('não deixa a chave de administrador ler pedido alheio', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create();
    $plain = ApiKey::issue($admin);
    [$order] = accountApiOrder($customer, Order::STATUS_PAID, Payment::STATUS_PAID, 5000);

    expect($admin->can('view', $order))->toBeFalse();

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras/'.$order->id)
        ->assertNotFound();

    $this->withToken($plain)
        ->getJson('/api/v1/me/compras')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('devolve só as indicações e comissões da chave', function () {
    $affiliate = User::factory()->create();
    $otherAffiliate = User::factory()->create();
    $buyer = User::factory()->create();
    $plain = ApiKey::issue($affiliate);
    $otherPlain = ApiKey::issue($otherAffiliate);

    $product = Product::factory()->create([
        'title' => 'Livro indicado',
        'content' => 'CONTEUDO_INTEGRAL_DO_LIVRO',
        'cover_path' => 'covers/arquivo-secreto.webp',
    ]);

    $click = new AffiliateClick;
    $click->affiliate_user_id = $affiliate->id;
    $click->product_id = $product->id;
    $click->token = 'token-secreto-do-clique';
    $click->user_agent = 'Agente Secreto';
    $click->save();

    $otherClick = new AffiliateClick;
    $otherClick->affiliate_user_id = $otherAffiliate->id;
    $otherClick->product_id = $product->id;
    $otherClick->token = 'token-de-outra-pessoa';
    $otherClick->save();

    [$paid, $payment] = accountApiOrder($buyer, Order::STATUS_PAID, Payment::STATUS_PAID, 3790, $affiliate);
    $payment->raw_response = ['copy' => 'PAYLOAD_CRU_DO_GATEWAY'];
    $payment->save();

    accountApiOrder($buyer, Order::STATUS_PENDING_PAYMENT, Payment::STATUS_PENDING, 2000, $affiliate);

    $commission = new Commission;
    $commission->affiliate_user_id = $affiliate->id;
    $commission->order_id = $paid->id;
    $commission->payment_id = $payment->id;
    $commission->base_cents = 3790;
    $commission->percent = 10;
    $commission->amount_cents = 379;
    $commission->status = Commission::STATUS_CONFIRMED;
    $commission->save();

    $foreign = new Commission;
    $foreign->affiliate_user_id = $otherAffiliate->id;
    [$foreignOrder, $foreignPayment] = accountApiOrder($buyer, Order::STATUS_PAID, Payment::STATUS_PAID, 8000, $otherAffiliate);
    $foreign->order_id = $foreignOrder->id;
    $foreign->payment_id = $foreignPayment->id;
    $foreign->base_cents = 8000;
    $foreign->percent = 10;
    $foreign->amount_cents = 800;
    $foreign->status = Commission::STATUS_CONFIRMED;
    $foreign->save();

    $this->withToken($plain)
        ->getJson('/api/v1/me/vendas')
        ->assertOk()
        ->assertJsonPath('clicks.meta.total', 1)
        ->assertJsonPath('clicks.data.0.product_title', 'Livro indicado')
        ->assertJsonPath('orders.meta.total', 1)
        ->assertJsonPath('orders.data.0.id', $paid->id)
        ->assertJsonPath('orders.data.0.total_cents', 3790)
        ->assertJsonPath('orders.data.0.commission_cents', 379)
        ->assertJsonPath('commissions.meta.total', 1)
        ->assertJsonPath('commissions.data.0.amount_cents', 379)
        ->assertJsonPath('commissions.data.0.order_id', $paid->id)
        ->assertJsonPath('totals.orders_cents', 3790)
        ->assertJsonPath('totals.commissions_cents', 379)
        ->assertDontSee('token-secreto-do-clique', false)
        ->assertDontSee('token-de-outra-pessoa', false)
        ->assertDontSee('Agente Secreto', false)
        ->assertDontSee('CONTEUDO_INTEGRAL_DO_LIVRO', false)
        ->assertDontSee('arquivo-secreto.webp', false)
        ->assertDontSee('PAYLOAD_CRU_DO_GATEWAY', false)
        ->assertJsonMissing(['order_id' => $foreignOrder->id]);

    $this->withToken($plain)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('sales.count', 1)
        ->assertJsonPath('sales.total_cents', 379);

    $this->withToken($otherPlain)
        ->getJson('/api/v1/me/vendas')
        ->assertOk()
        ->assertJsonPath('orders.data.0.id', $foreignOrder->id)
        ->assertJsonPath('orders.meta.total', 1)
        ->assertJsonPath('totals.commissions_cents', 800)
        ->assertJsonMissing(['order_id' => $paid->id]);
});

it('responde vendas vazias quando a conta não indicou ninguém', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);

    $this->withToken($plain)
        ->getJson('/api/v1/me/vendas')
        ->assertOk()
        ->assertJsonPath('clicks.data', [])
        ->assertJsonPath('orders.data', [])
        ->assertJsonPath('commissions.data', [])
        ->assertJsonPath('totals.orders_cents', 0)
        ->assertJsonPath('totals.commissions_cents', 0);
});

it('limita cada chave a 60 consultas por minuto', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);

    foreach (range(1, 60) as $ignored) {
        $this->withToken($plain)->getJson('/api/v1/me')->assertOk();
    }

    $this->withToken($plain)->getJson('/api/v1/me')->assertStatus(429);
});

it('não aceita corpo nas rotas da conta', function () {
    $user = User::factory()->create();
    $plain = ApiKey::issue($user);

    $this->withToken($plain)
        ->postJson('/api/v1/me', ['name' => 'Outra'])
        ->assertStatus(405);
});

/**
 * @return array{0: Order, 1: Payment}
 */
function accountApiOrder(User $buyer, string $orderStatus, string $paymentStatus, int $cents, ?User $affiliate = null): array
{
    $order = new Order;
    $order->user_id = $buyer->id;
    $order->status = $orderStatus;
    $order->total_cents = $cents;
    $order->affiliate_id = $affiliate?->id;
    $order->affiliate_code = $affiliate?->affiliate_code;
    $order->save();

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->amount_cents = $cents;
    $payment->status = $paymentStatus;
    $payment->method = Payment::METHOD_PIX;
    $payment->save();

    return [$order, $payment];
}
