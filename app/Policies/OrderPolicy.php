<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view_financials');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view_financials');
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->can('orders.issue_refund');
    }
}
