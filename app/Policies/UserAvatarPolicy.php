<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserAvatar;

class UserAvatarPolicy
{
    public function update(User $user, UserAvatar $avatar): bool
    {
        return $avatar->user_id === $user->id;
    }

    public function delete(User $user, UserAvatar $avatar): bool
    {
        return $avatar->user_id === $user->id;
    }
}
