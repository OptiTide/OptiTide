<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class LogoutController extends Controller
{
    public function logout(Request $request): Response
    {
        Auth::logout();
        Session::flash('status', 'You have been signed out.');

        return $this->redirect(route('login'));
    }
}
