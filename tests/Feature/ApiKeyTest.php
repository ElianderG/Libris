<?php

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('esconde a página de configurações de quem não entrou', function () {
    $this->get(route('account.settings'))->assertRedirect(route('login'));
    $this->post(route('account.settings.store'))->assertRedirect(route('login'));
    $this->delete(route('account.settings.destroy'))->assertRedirect(route('login'));
});

it('mostra a chave uma vez e depois só o prefixo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('account.settings.store'))
        ->assertRedirect(route('account.settings'))
        ->assertSessionHas('apiKey');

    $plain = session('apiKey');

    expect($plain)->toMatch('/^libris_[A-Za-z0-9]{40}$/');

    $this->get(route('account.settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('AccountSettings')
            ->where('flash.apiKey', $plain)
            ->where('activeKey.prefix', substr($plain, 0, 12).'…'));

    $this->get(route('account.settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('flash.apiKey', null)
            ->where('activeKey.prefix', substr($plain, 0, 12).'…'))
        ->assertDontSee($plain, false);

    $stored = ApiKey::query()->first();

    expect($stored)->not->toBeNull()
        ->and($stored->key_hash)->toBe(hash('sha256', $plain))
        ->and($stored->prefix)->toBe(substr($plain, 0, 12))
        ->and($stored->last_used_at)->toBeNull()
        ->and($stored->revoked_at)->toBeNull();

    expect(json_encode($stored->toArray()))->not->toContain($plain);
    $this->assertDatabaseMissing('api_keys', ['prefix' => $plain]);
    $this->assertDatabaseMissing('api_keys', ['key_hash' => $plain]);
});

it('revoga a chave anterior quando gera outra', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('account.settings.store'));
    $first = session('apiKey');
    $firstKey = ApiKey::query()->first();

    $this->post(route('account.settings.store'));
    $second = session('apiKey');

    expect($second)->not->toBe($first);
    expect($firstKey->fresh()->revoked_at)->not->toBeNull();
    expect(ApiKey::query()->count())->toBe(2);
    expect(ApiKey::query()->whereNull('revoked_at')->count())->toBe(1);
    expect(ApiKey::query()->whereNull('revoked_at')->value('key_hash'))->toBe(hash('sha256', $second));
});

it('deixa a conta sem chave ativa ao revogar', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('account.settings.store'));

    $this->delete(route('account.settings.destroy'))
        ->assertRedirect(route('account.settings'));

    expect(ApiKey::query()->count())->toBe(1);
    expect(ApiKey::query()->whereNull('revoked_at')->count())->toBe(0);

    $this->get(route('account.settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('activeKey', null));

    $this->delete(route('account.settings.destroy'))->assertNotFound();
});

it('não mostra nem revoga a chave de outra conta', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($owner)->post(route('account.settings.store'));
    $plain = session('apiKey');
    $key = ApiKey::query()->first();

    expect($owner->can('revoke', $key))->toBeTrue()
        ->and($other->can('revoke', $key))->toBeFalse()
        ->and($admin->can('revoke', $key))->toBeFalse();

    $this->get(route('account.settings'))->assertOk();
    auth()->logout();

    $this->actingAs($other)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeKey', null)
            ->where('flash.apiKey', null))
        ->assertDontSee($plain, false);

    $this->delete(route('account.settings.destroy'))->assertNotFound();

    expect($key->fresh()->revoked_at)->toBeNull();

    $this->actingAs($admin)->post(route('account.settings.store'));

    expect(ApiKey::query()->where('user_id', $admin->id)->whereNull('revoked_at')->count())->toBe(1);
    expect(ApiKey::query()->where('user_id', $owner->id)->whereNull('revoked_at')->count())->toBe(1);
});

it('não deixa revogar uma chave que já foi invalidada', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('account.settings.store'));

    $key = ApiKey::query()->first();
    $key->revoked_at = now();
    $key->save();

    expect($user->can('revoke', $key))->toBeFalse();
});
