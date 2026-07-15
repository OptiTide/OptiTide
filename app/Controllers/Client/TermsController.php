<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class TermsController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('client.accept-terms', ['title' => 'Accept Our Terms']);
    }

    public function accept(Request $request): Response
    {
        $this->validate($request, ['accept_terms' => 'required'], ['accept_terms' => 'Terms acceptance']);

        User::updateById(Auth::id(), ['terms_accepted_at' => now()]);
        Session::flash('success', 'Thanks — you can now use your portal.');

        return $this->redirect(route('portal.dashboard'));
    }
}
