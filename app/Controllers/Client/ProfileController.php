<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\User;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.profile', [
            'title'  => 'Profile',
            'user'   => Auth::user(),
            'client' => $clientId ? Client::find($clientId) : null,
        ]);
    }

    public function update(Request $request): Response
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'name'         => 'required|max:120',
            'email'        => 'required|email|unique:users,email,' . $user['id'],
            'contact_name' => 'nullable|max:120',
            'phone'        => 'nullable|max:40',
            'password'     => 'nullable|min:8|confirmed',
        ]);

        // Require the current password to change the password.
        if (! empty($data['password'])) {
            if (! password_verify((string) $request->input('current_password'), (string) $user['password_hash'])) {
                Session::flash('error', 'Your current password is incorrect.');

                return $this->back();
            }
        }

        $update = ['name' => $data['name'], 'email' => strtolower($data['email'])];
        if (! empty($data['password'])) {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        User::updateById($user['id'], $update);

        if (Auth::clientId()) {
            Client::updateById(Auth::clientId(), [
                'contact_name' => $data['contact_name'] ?? null,
                'phone'        => $data['phone'] ?? null,
            ]);
        }

        Session::flash('success', 'Profile updated.');

        return $this->redirect(route('portal.profile.edit'));
    }
}
