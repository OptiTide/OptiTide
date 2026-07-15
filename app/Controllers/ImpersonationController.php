<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\Audit\AuditLog;

/**
 * Admin "log in as" a client, to see exactly what they see. The original admin
 * id is stashed in the session so they can return with one click.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, string $id): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can do that.');

        $target = User::findOrFail($id);
        if ($target['role'] !== User::ROLE_CLIENT) {
            Session::flash('error', 'You can only log in as a client account.');

            return $this->back();
        }

        Session::put('_impersonator', Auth::id());
        AuditLog::record('user.impersonate_start', 'user', $target['id'], ['target_email' => $target['email']]);
        Auth::login($target);
        Session::flash('status', 'You are now viewing the portal as ' . $target['name'] . '.');

        return $this->redirect(route('portal.dashboard'));
    }

    public function leave(Request $request): Response
    {
        $adminId = Session::pull('_impersonator');
        if ($adminId && ($admin = User::find($adminId))) {
            Auth::login($admin);
            AuditLog::record('user.impersonate_stop', 'user', $adminId, [], $admin);
            Session::flash('success', 'Welcome back — you have returned to your admin account.');

            return $this->redirect(route('admin.users.index'));
        }

        return $this->redirect(route('portal.dashboard'));
    }
}
