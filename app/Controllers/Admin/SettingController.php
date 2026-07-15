<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Setting;

/**
 * Company + payment settings, editable online. Values are stored in the
 * settings table with dot-path keys and override .env at boot (see bootstrap).
 * Gateway API secrets still live in .env. Admin only.
 */
class SettingController extends Controller
{
    /** form field => config dot-path */
    protected const EDITABLE = [
        's_legal_name'    => 'company.legal_name',
        's_abn'           => 'company.abn',
        's_email'         => 'company.email',
        's_phone'         => 'company.phone',
        's_addr_line1'    => 'company.address.line1',
        's_addr_locality' => 'company.address.locality',
        's_addr_region'   => 'company.address.region',
        's_addr_postcode' => 'company.address.postcode',
        's_payid_type'    => 'payments.gateways.payid.type',
        's_payid_value'   => 'payments.gateways.payid.value',
        's_payid_name'    => 'payments.gateways.payid.account_name',
        's_bank_bsb'      => 'payments.gateways.payid.bsb',
        's_bank_account'  => 'payments.gateways.payid.account_number',
        's_payoneer_mode' => 'payments.gateways.payoneer.mode',
    ];

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

        $this->validate($request, [
            's_email'               => 'nullable|email|max:180',
            's_abn'                 => 'nullable|max:20',
            's_addr_postcode'       => 'nullable|max:4',
            's_payid_type'          => 'nullable|in:mobile,email,abn',
            's_payoneer_mode'       => 'nullable|in:manual,api',
            'invoice_footer'        => 'nullable|max:500',
            'default_payment_terms' => 'nullable|integer|min:0|max:120',
        ], ['s_email' => 'Company e-mail', 's_abn' => 'ABN', 'default_payment_terms' => 'Default Payment Terms']);

        foreach (self::EDITABLE as $formField => $configKey) {
            Setting::put($configKey, trim((string) $request->input($formField, '')));
        }

        Setting::put('invoice_footer', (string) $request->input('invoice_footer', ''));
        $terms = ($request->input('default_payment_terms', '') === '') ? 14 : (int) $request->input('default_payment_terms');
        Setting::put('default_payment_terms', (string) $terms);

        Session::flash('success', 'Settings saved.');

        return $this->redirect(route('admin.settings.edit'));
    }
}
