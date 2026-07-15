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
    /** Australian states/territories. */
    public const STATES = ['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'ACT', 'NT'];

    public function edit(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.profile', [
            'title'  => 'Profile',
            'user'   => Auth::user(),
            'client' => $clientId ? Client::find($clientId) : null,
            'states' => self::STATES,
        ]);
    }

    public function update(Request $request): Response
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'name'             => 'required|max:120',
            'email'            => 'required|email|unique:users,email,' . $user['id'],
            'business_name'    => 'required|max:160',
            'contact_name'     => 'nullable|max:120',
            'business_email'   => 'nullable|email|max:180',
            'phone'            => 'nullable|max:40',
            'abn'              => 'nullable|max:20',
            'acn'              => 'nullable|max:20',
            'website'          => 'nullable|url|max:200',
            'address_line1'    => 'nullable|max:180',
            'address_locality' => 'nullable|max:120',
            'address_region'   => 'nullable|in:' . implode(',', self::STATES),
            'address_postcode' => 'nullable|max:4',
            'password'         => 'nullable|min:8|confirmed',
        ], [
            'name'           => 'Name',
            'email'          => 'E-Mail',
            'business_name'  => 'Business name',
            'business_email' => 'Business e-mail',
            'abn'            => 'ABN',
            'acn'            => 'ACN',
        ]);

        // Require the current password to set a new one.
        if (! empty($data['password'])) {
            if (! password_verify((string) $request->input('current_password'), (string) $user['password_hash'])) {
                Session::flash('error', 'Your current password is incorrect.');

                return $this->back();
            }
        }

        // --- Personal / login ---------------------------------------------
        $userUpdate = ['name' => $data['name'], 'email' => strtolower($data['email'])];
        if (! empty($data['password'])) {
            $userUpdate['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        User::updateById($user['id'], $userUpdate);

        // --- Business ------------------------------------------------------
        if (Auth::clientId()) {
            Client::updateById(Auth::clientId(), [
                'business_name'    => $data['business_name'],
                'contact_name'     => $data['contact_name'] ?? null,
                'email'            => $data['business_email'] ?? null,
                'phone'            => $data['phone'] ?? null,
                'abn'              => $data['abn'] ?? null,
                'acn'              => $data['acn'] ?? null,
                'website'          => $data['website'] ?? null,
                'address_line1'    => $data['address_line1'] ?? null,
                'address_locality' => $data['address_locality'] ?? null,
                'address_region'   => $data['address_region'] ?? null,
                'address_postcode' => $data['address_postcode'] ?? null,
                'address_country'  => 'Australia',
            ]);
        }

        Session::flash('success', 'Your profile has been updated.');

        return $this->redirect(route('portal.profile.edit'));
    }
}
