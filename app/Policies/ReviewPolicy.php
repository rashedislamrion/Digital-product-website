<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.moderate_publish');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can('reviews.moderate_publish');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.moderate_publish');
    }
}
