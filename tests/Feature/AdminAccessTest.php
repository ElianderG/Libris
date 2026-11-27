<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;

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
    $repository = Env::getRepository();
    $originalEmail = $repository->get('ADMIN_EMAIL');
    $originalPassword = $repository->get('ADMIN_PASSWORD');

    $restore = function () use ($repository, $originalEmail, $originalPassword): void {
        is_string($originalEmail)
            ? $repository->set('ADMIN_EMAIL', $originalEmail)
            : $repository->clear('ADMIN_EMAIL');

        is_string($originalPassword)
            ? $repository->set('ADMIN_PASSWORD', $originalPassword)
            : $repository->clear('ADMIN_PASSWORD');
    };

    try {
        $repository->set('ADMIN_EMAIL', 'admin@libris.test');
        $repository->clear('ADMIN_PASSWORD');

        $this->seed();

        expect(User::query()->where('is_admin', true)->exists())->toBeFalse();

        $repository->set('ADMIN_PASSWORD', 'senha-segura');

        $this->seed();

        $admin = User::query()->where('email', 'admin@libris.test')->first();

        expect($admin)->not->toBeNull()
            ->and($admin->is_admin)->toBeTrue()
            ->and($admin->affiliate_code)->not->toBeEmpty();
    } finally {
        $restore();
    }
});
