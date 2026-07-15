<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Audit\AuditLog;

class LogoutController extends Controller
{
    public function logout(Request $request): Response
    {
        $actor = Auth::user();
        Auth::logout();
        AuditLog::record('auth.logout', 'user', $actor['id'] ?? null, [], $actor);
        Session::flash('status', 'You have been signed out.');

        return $this->redirect(route('login'));
    }
}
