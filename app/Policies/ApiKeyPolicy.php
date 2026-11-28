<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $apiKey->user_id === $user->id && $apiKey->revoked_at === null;
    }
}
