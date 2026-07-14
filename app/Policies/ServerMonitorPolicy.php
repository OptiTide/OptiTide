<?php

namespace App\Policies;

use App\Models\ServerMonitor;
use App\Models\User;

/**
 * Server monitors + infrastructure are admin-only (the ServerMonitor model is
 * not exposed in the client panel, so a restrictive policy is safe). VAs never
 * see infra.
 */
class ServerMonitorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ServerMonitor $monitor): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ServerMonitor $monitor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ServerMonitor $monitor): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
