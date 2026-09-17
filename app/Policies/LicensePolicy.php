<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('licenses.view_keys');
    }

    public function view(User $user, License $license): bool
    {
        return $user->can('licenses.view_keys');
    }

    public function update(User $user, License $license): bool
    {
        return $user->can('licenses.reset_revoke');
    }
}
