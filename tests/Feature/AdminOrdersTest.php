<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ordersAdmin(): User
{
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

function placeOrder(User $buyer, string $status, int $cents = 3790): Order
{
    $order = new Order;
    $order->user_id = $buyer->id;
    $order->status = $status;
    $order->total_cents = $cents;
    $order->save();

    $item = new OrderItem;
    $item->order_id = $order->id;
    $item->product_id = Product::factory()->create()->id;
    $item->title = 'Notas da tarde';
    $item->unit_price_cents = $cents;
    $item->quantity = 1;
    $item->save();

    return $order;
}

it('filtra pedidos pendentes e abre as tentativas sem marcar pago', function () {
    $admin = ordersAdmin();
    $buyer = User::factory()->create(['name' => 'Leitora']);
    $pending = placeOrder($buyer, Order::STATUS_PENDING_PAYMENT);
    placeOrder($buyer, Order::STATUS_PAID, 2500);

    $first = new Payment;
    $first->order_id = $pending->id;
    $first->amount_cents = 3790;
    $first->status = Payment::STATUS_FAILED;
    $first->method = Payment::METHOD_PIX;
    $first->gateway_id = 'pay_antiga';
    $first->raw_response = ['id' => 'pay_antiga'];
    $first->save();

    $latest = new Payment;
    $latest->order_id = $pending->id;
    $latest->amount_cents = 3790;
    $latest->status = Payment::STATUS_PENDING;
    $latest->method = Payment::METHOD_PIX;
    $latest->gateway_id = 'pay_nova';
    $latest->raw_request = ['amount' => 37.9];
    $latest->raw_response = ['id' => 'pay_nova'];
    $latest->save();

    $this->actingAs($admin)
        ->get(route('admin.orders', ['status' => Order::STATUS_PENDING_PAYMENT]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $pending->id)
            ->where('orders.data.0.buyer', 'Leitora')
            ->where('orders.data.0.status', Order::STATUS_PENDING_PAYMENT)
            ->missing('orders.data.0.mark_paid_url'));

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $pending))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/Show')
            ->has('order.payments', 2)
            ->where('order.payments.0.gateway_id', 'pay_nova')
            ->where('order.payments.0.label', 'Pendente')
            ->where('order.payments.1.gateway_id', 'pay_antiga')
            ->where('order.items.0.title', 'Notas da tarde')
            ->where('order.payments.0.raw', fn (string $raw): bool => str_contains($raw, 'pay_nova')
                && str_contains($raw, '"request"')
                && str_contains($raw, '"response"')));
});

it('pagina pedidos e usuários', function () {
    $admin = ordersAdmin();
    $buyer = User::factory()->create();

    foreach (range(1, 16) as $ignored) {
        placeOrder($buyer, Order::STATUS_PAID, 1000);
    }

    User::factory()->count(15)->create();

    $this->actingAs($admin)
        ->get(route('admin.orders', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.current_page', 2));

    $this->actingAs($admin)
        ->get(route('admin.users', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.current_page', 2)
            ->missing('users.data.0.password')
            ->missing('users.data.0.remember_token')
            ->missing('users.data.0.two_factor_secret'));
});

it('promove outro usuário e recusa a própria conta', function () {
    $admin = ordersAdmin();
    $customer = User::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->post(route('admin.users.update', $customer))
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success', 'Administrador promovido.');

    expect($customer->fresh()->is_admin)->toBeTrue();

    $this->actingAs($customer->fresh())
        ->get(route('admin.home'))
        ->assertOk();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->post(route('admin.users.update', $admin), ['is_admin' => false])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('error', 'Você não pode alterar o próprio acesso.');

    expect($admin->fresh()->is_admin)->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.update', $customer))
        ->assertSessionHas('success', 'Acesso ao painel removido.');

    expect($customer->fresh()->is_admin)->toBeFalse();

    $this->actingAs($customer->fresh())
        ->get(route('admin.home'))
        ->assertForbidden();
});

it('lista só os pedidos da conta escolhida', function () {
    $admin = ordersAdmin();
    $ana = User::factory()->create(['name' => 'Ana']);
    $bia = User::factory()->create(['name' => 'Bia']);
    placeOrder($ana, Order::STATUS_PAID, 2500);
    placeOrder($bia, Order::STATUS_PENDING_PAYMENT);

    $this->actingAs($admin)
        ->get(route('admin.orders', ['user' => $ana->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.buyer', 'Ana')
            ->where('buyer.name', 'Ana'));
});

it('apaga conta sem histórico e recusa a própria e quem já comprou', function () {
    $admin = ordersAdmin();
    $empty = User::factory()->create(['name' => 'Sem compra']);
    $buyer = User::factory()->create(['name' => 'Compradora']);
    $affiliate = User::factory()->create(['name' => 'Afiliada']);
    $order = placeOrder($buyer, Order::STATUS_PAID);
    $order->affiliate_id = $affiliate->id;
    $order->save();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($rows): bool => collect($rows)->contains(
                fn (array $row): bool => $row['name'] === 'Sem compra' && $row['can_delete'] === true && $row['orders_count'] === 0,
            ) && collect($rows)->contains(
                fn (array $row): bool => $row['name'] === 'Compradora' && $row['can_delete'] === false && $row['orders_count'] === 1,
            ))
            ->missing('users.data.0.password'));

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->delete(route('admin.users.destroy', $empty))
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success', 'Conta apagada.');

    expect(User::query()->whereKey($empty->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHas('error', 'Você não pode apagar a própria conta.');

    expect($admin->fresh())->not->toBeNull();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->delete(route('admin.users.destroy', $buyer))
        ->assertSessionHas('error', 'Essa conta tem pedidos ou comissões e não pode ser apagada.');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $affiliate))
        ->assertSessionHas('error', 'Essa conta tem pedidos ou comissões e não pode ser apagada.');

    expect(User::query()->whereKey($buyer->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($affiliate->id)->exists())->toBeTrue()
        ->and(Order::query()->whereKey($order->id)->exists())->toBeTrue();
});

it('recusa pedidos e usuários para um cliente', function () {
    $customer = User::factory()->create();
    $order = placeOrder($customer, Order::STATUS_PENDING_PAYMENT);

    $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.users.update', $customer))->assertForbidden();
    $this->actingAs($customer)->delete(route('admin.users.destroy', $customer))->assertForbidden();

    expect($customer->fresh()->is_admin)->toBeFalse();
});
