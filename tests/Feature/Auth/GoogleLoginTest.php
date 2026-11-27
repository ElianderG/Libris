<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.google.client_id' => 'client-id',
        'services.google.client_secret' => 'client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
});

function fakeGoogle(array $overrides = []): void
{
    Socialite::fake('google', GoogleUser::fake(array_merge([
        'id' => 'google-1',
        'name' => 'Lena Vilar',
        'email' => 'lena@example.com',
        'email_verified' => true,
    ], $overrides)));
}

it('cria a conta a partir do google e não promove administrador', function () {
    fakeGoogle();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('library'));

    $user = User::query()->where('email', 'lena@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-1')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->affiliate_code)->not->toBeEmpty();

    $this->assertAuthenticatedAs($user);
});

it('entra na conta que já usa o mesmo e-mail', function () {
    $existing = User::factory()->unverified()->create([
        'email' => 'lena@example.com',
    ]);

    fakeGoogle();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('library'));

    expect(User::query()->count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-1')
        ->and($existing->fresh()->email_verified_at)->not->toBeNull()
        ->and($existing->fresh()->is_admin)->toBeFalse();
});

it('manda o administrador para o painel', function () {
    $admin = User::factory()->create([
        'email' => 'lena@example.com',
    ]);
    $admin->forceFill(['is_admin' => true])->save();

    fakeGoogle();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('admin.home'));

    expect($admin->fresh()->is_admin)->toBeTrue();
});

it('recusa e-mail que o google não confirmou', function () {
    fakeGoogle(['email_verified' => false]);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

it('pede o segundo fator antes de entrar', function () {
    $user = User::factory()->withTwoFactor()->create([
        'email' => 'lena@example.com',
    ]);

    fakeGoogle();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
    expect(session('login.id'))->toBe($user->id);
});

it('mostra o botão só quando o google está configurado', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('googleLogin', true));

    config([
        'services.google.client_id' => '',
        'services.google.client_secret' => '',
    ]);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('googleLogin', false));
});
