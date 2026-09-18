<?php

namespace App\Policies;

use App\Models\ProductVersion;
use App\Models\User;

class ProductVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.create_update');
    }

    public function view(User $user, ProductVersion $version): bool
    {
        return $user->can('catalog.create_update');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.create_update');
    }

    public function update(User $user, ProductVersion $version): bool
    {
        return $user->can('catalog.create_update');
    }

    public function delete(User $user, ProductVersion $version): bool
    {
        return $user->can('catalog.create_update');
    }

    public function publish(User $user, ProductVersion $version): bool
    {
        return $user->can('catalog.publish_version');
    }
}
