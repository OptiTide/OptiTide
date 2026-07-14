<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Catalog + pricing is admin-only. Products are public on the storefront (no
 * auth), so this policy only governs the admin panel resource — VAs manage
 * content/support, not the catalog. (No Gate::before superuser exists, so admin
 * is allowed explicitly.)
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
