<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('recusa o painel para um cliente', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.home'))
        ->assertForbidden();
});

it('abre o painel para o administrador', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk();
});

it('envia o cliente para a biblioteca e o administrador para o painel', function () {
    $customer = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('library'));

    auth()->logout();

    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.home'));
});

it('não deixa o perfil promover o cliente', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->patch(route('profile.update'), [
            'name' => 'Cliente',
            'email' => $customer->email,
            'is_admin' => true,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($customer->fresh()->is_admin)->toBeFalse();
});

it('cria o administrador só quando o e-mail e a senha existem', function () {
    putenv('ADMIN_EMAIL=admin@libris.test');
    putenv('ADMIN_PASSWORD');

    $this->seed();

    expect(User::query()->where('is_admin', true)->exists())->toBeFalse();

    putenv('ADMIN_PASSWORD=senha-segura');

    $this->seed();

    $admin = User::query()->where('email', 'admin@libris.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and($admin->affiliate_code)->not->toBeEmpty();

    putenv('ADMIN_EMAIL');
    putenv('ADMIN_PASSWORD');
});
