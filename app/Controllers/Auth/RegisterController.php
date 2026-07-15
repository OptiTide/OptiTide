<?php

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\User;
use App\Services\Mail\Mail;

class RegisterController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->view('auth.register', ['title' => 'Create an Account']);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'          => 'required|max:120',
            'business_name' => 'required|max:160',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:8|confirmed',
            'accept_terms'  => 'required',
        ], ['business_name' => 'Business name', 'accept_terms' => 'Terms acceptance']);

        $user = Database::instance()->transaction(function () use ($data) {
            $client = Client::create([
                'business_name' => $data['business_name'],
                'contact_name'  => $data['name'],
                'email'         => strtolower($data['email']),
                'status'        => Client::STATUS_ACTIVE,
            ]);

            return User::create([
                'name'          => $data['name'],
                'email'         => strtolower($data['email']),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'              => User::ROLE_CLIENT,
                'client_id'         => $client['id'],
                'status'            => 'active',
                'terms_accepted_at' => now(),
            ]);
        });

        Auth::login($user);

        Mail::to($user['email'], $user['name'])
            ->subject('Welcome to OptiTide')
            ->view('emails.welcome', ['name' => $user['name'], 'url' => url('portal')])
            ->send();

        Session::flash('success', 'Welcome to OptiTide!');

        return $this->redirect(route('portal.dashboard'));
    }
}
