<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    public function redirect(): Response
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('error', 'O login com Google não está disponível.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): Response
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('error', 'O login com Google não está disponível.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception) {
            return redirect()->route('login')->with('error', 'Não foi possível entrar com o Google.');
        }

        if (! $googleUser instanceof GoogleUser || ! $this->emailIsVerified($googleUser)) {
            return redirect()->route('login')->with('error', 'O Google não confirmou este e-mail.');
        }

        $user = $this->accountFor($googleUser);

        if ($user === null) {
            return redirect()->route('login')->with('error', 'Este e-mail já está ligado a outra conta do Google.');
        }

        if ($user->two_factor_confirmed_at !== null) {
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => true,
            ]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return app(LoginResponse::class)->toResponse($request);
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function emailIsVerified(GoogleUser $googleUser): bool
    {
        $email = $googleUser->getEmail();
        $verified = $googleUser->user['email_verified'] ?? false;

        return is_string($email) && $email !== '' && $verified === true;
    }

    private function accountFor(GoogleUser $googleUser): ?User
    {
        $googleId = (string) $googleUser->getId();
        $email = strtolower((string) $googleUser->getEmail());

        $user = User::query()->where('google_id', $googleId)->first();

        if ($user === null) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user !== null && is_string($user->google_id) && $user->google_id !== $googleId) {
            return null;
        }

        if ($user === null) {
            $user = new User;
            $user->name = $this->nameFor($googleUser, $email);
            $user->email = $email;
            $user->password = Str::password(40);
            $user->setAttribute('email_verified_at', now());
        }

        if ($user->email_verified_at === null) {
            $user->setAttribute('email_verified_at', now());
        }

        $user->google_id = $googleId;
        $user->save();

        return $user;
    }

    private function nameFor(GoogleUser $googleUser, string $email): string
    {
        $name = trim((string) $googleUser->getName());

        if ($name === '') {
            $name = strstr($email, '@', true) ?: 'Leitor';
        }

        return mb_substr($name, 0, 255);
    }
}
