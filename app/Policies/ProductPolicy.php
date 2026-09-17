<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.create_update');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('catalog.create_update');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.create_update');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('catalog.create_update');
    }

    public function publish(User $user, Product $product): bool
    {
        return $user->can('catalog.publish_version');
    }
}
