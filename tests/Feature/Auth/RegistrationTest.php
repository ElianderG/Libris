<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('library', absolute: false));

        $code = User::query()->where('email', 'test@example.com')->value('affiliate_code');

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{8}$/', (string) $code);
    }

    public function test_duplicate_email_and_short_password_are_rejected_in_portuguese()
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([
            'name' => 'O campo nome é obrigatório.',
            'email' => 'Este e-mail já está em uso.',
            'password' => 'O campo senha precisa ter pelo menos 8 caracteres.',
        ]);
    }
}
