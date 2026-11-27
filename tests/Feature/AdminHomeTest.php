<?php

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mostra zero quando a loja ainda não vendeu', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Home')
            ->where('stats.published_books', 0)
            ->where('stats.paid_orders', 0)
            ->where('stats.sold_total', 'R$ 0,00')
            ->where('stats.confirmed_commissions', 0)
            ->has('charts.sales.labels', 14)
            ->where('charts.sales.values', array_fill(0, 14, 0))
            ->where('charts.orders.values', [0, 0])
            ->where('charts.books.labels', [])
            ->has('recent', 0));
});

it('soma só pedidos pagos e comissões confirmadas', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $buyer = User::factory()->create();

    $published = Product::factory()->create();
    $published->is_published = true;
    $published->save();
    Product::factory()->create();

    $first = new Order;
    $first->user_id = $buyer->id;
    $first->status = Order::STATUS_PAID;
    $first->total_cents = 3790;
    $first->save();

    $second = new Order;
    $second->user_id = $buyer->id;
    $second->status = Order::STATUS_PAID;
    $second->total_cents = 2500;
    $second->save();

    $pending = new Order;
    $pending->user_id = $buyer->id;
    $pending->status = Order::STATUS_PENDING_PAYMENT;
    $pending->total_cents = 10000;
    $pending->save();

    $payment = new Payment;
    $payment->order_id = $first->id;
    $payment->amount_cents = $first->total_cents;
    $payment->status = Payment::STATUS_PAID;
    $payment->method = Payment::METHOD_PIX;
    $payment->save();

    $confirmed = new Commission;
    $confirmed->affiliate_user_id = $admin->id;
    $confirmed->order_id = $first->id;
    $confirmed->payment_id = $payment->id;
    $confirmed->base_cents = 3790;
    $confirmed->percent = 10;
    $confirmed->amount_cents = 379;
    $confirmed->status = Commission::STATUS_CONFIRMED;
    $confirmed->save();

    $otherPayment = new Payment;
    $otherPayment->order_id = $second->id;
    $otherPayment->amount_cents = $second->total_cents;
    $otherPayment->status = Payment::STATUS_PAID;
    $otherPayment->method = Payment::METHOD_PIX;
    $otherPayment->save();

    $open = new Commission;
    $open->affiliate_user_id = $admin->id;
    $open->order_id = $second->id;
    $open->payment_id = $otherPayment->id;
    $open->base_cents = 2500;
    $open->percent = 10;
    $open->amount_cents = 250;
    $open->status = 'pending';
    $open->save();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.published_books', 1)
            ->where('stats.paid_orders', 2)
            ->where('stats.sold_total', 'R$ 62,90')
            ->where('stats.confirmed_commissions', 1)
            ->has('recent', 2)
            ->where('recent.0.id', $second->id)
            ->where('recent.0.total', 'R$ 25,00')
            ->where('recent.1.id', $first->id)
            ->where('recent.1.buyer', $buyer->name));
});

it('monta os gráficos com vendas recentes, status e categorias', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $buyer = User::factory()->create();

    $published = Product::factory()->create();
    $published->is_published = true;
    $published->save();
    $published->load('category');
    Product::factory()->create();

    $today = new Order;
    $today->user_id = $buyer->id;
    $today->status = Order::STATUS_PAID;
    $today->total_cents = 3790;
    $today->save();

    $pending = new Order;
    $pending->user_id = $buyer->id;
    $pending->status = Order::STATUS_PENDING_PAYMENT;
    $pending->total_cents = 10000;
    $pending->save();

    $old = new Order;
    $old->user_id = $buyer->id;
    $old->status = Order::STATUS_PAID;
    $old->total_cents = 5000;
    $old->created_at = now()->subDays(40);
    $old->save();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('charts.sales.values.13', 3790)
            ->where('charts.sales.values.0', 0)
            ->where('charts.orders.values', [2, 1])
            ->where('charts.books.labels', [$published->category->name])
            ->where('charts.books.values', [1]));
});

it('recusa as páginas do menu para um cliente', function (string $name) {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route($name))
        ->assertForbidden();
})->with([
    'admin.home',
    'admin.books',
    'admin.categories',
    'admin.orders',
    'admin.users',
    'admin.affiliates',
]);

it('abre o menu do painel para o administrador', function (string $name, string $component) {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)
        ->get(route($name))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['admin.books', 'admin/books/Index'],
    ['admin.categories', 'admin/categories/Index'],
    ['admin.orders', 'admin/orders/Index'],
    ['admin.users', 'admin/users/Index'],
    ['admin.affiliates', 'admin/affiliates/Index'],
]);
