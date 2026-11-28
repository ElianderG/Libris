<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function promote(User $user, User $target): bool
    {
        return $user->is_admin === true && $user->id !== $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->is_admin === true && $user->id !== $target->id;
    }
}
