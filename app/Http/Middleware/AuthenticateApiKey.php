<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearer($request);

        $key = $token === null ? null : ApiKey::query()
            ->where('key_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->first();

        $user = $key?->user;

        if ($key === null || $user === null) {
            return response()->json(['message' => 'Chave inválida.'], 401);
        }

        $key->last_used_at = now();
        $key->save();

        $request->attributes->set('api_key_id', $key->id);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function bearer(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || preg_match('/^Bearer\s+(\S+)\s*$/i', $header, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
