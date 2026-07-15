<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Read-only view of the payment + company configuration (which lives in .env
 * for secrets safety) plus a couple of in-app editable settings. Admin only.
 */
class SettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can view settings.');

        return $this->view('admin.settings.edit', [
            'title'    => 'Settings',
            'company'  => config('company'),
            'payid'    => config('payments.gateways.payid'),
            'payoneer' => config('payments.gateways.payoneer'),
            'enabled'  => config('payments.enabled'),
            'mail'     => config('mail'),
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize(Auth::isAdmin(), 'Only administrators can change settings.');

        // Editable-in-app settings are stored in the settings table; secrets and
        // gateway credentials stay in .env and are shown read-only above.
        $data = $this->validate($request, [
            'invoice_footer' => 'nullable|max:500',
        ]);

        \App\Models\Setting::put('invoice_footer', $data['invoice_footer'] ?? '');
        Session::flash('success', 'Settings saved.');

        return $this->redirect(route('admin.settings.edit'));
    }
}
