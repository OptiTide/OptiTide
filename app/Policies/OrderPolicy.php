<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * The Order model is shared by BOTH panels (admin CRM + the client's read-only
 * "my orders"), so this policy must stay permissive enough for clients — the
 * client resource scopes rows by user_id in getEloquentQuery(). The one real
 * restriction is destructive: only an admin may delete an order (VAs work the
 * pipeline but can't erase the audit trail; clients never delete).
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        // Both panels list orders; row visibility is scoped by the resource.
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isStaff() || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
