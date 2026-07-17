<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\HostingAccount;
use App\Services\Audit\AuditLog;
use App\Services\Whm\HostingService;
use App\Services\Whm\WhmClientFactory;

class HostingController extends Controller
{
    public function index(Request $request): Response
    {
        $whm = WhmClientFactory::make();

        $packages = [];
        if ($whm->available()) {
            try {
                $packages = $whm->listPackages();
            } catch (\Throwable $e) {
                // The page must render even when WHM is down; provisioning forms
                // just lose their package dropdowns.
            }
        }

        return $this->view('admin.hosting.index', [
            'title'        => 'Hosting',
            'accounts'     => HostingAccount::query()->orderBy('domain')->get(),
            'connected'    => $whm->available(),
            'packages'     => $packages,
            'server'       => config('whm.server_label'),
            'host'         => config('whm.host'),
            'clients'      => Client::query()->orderBy('business_name')->get(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
    }

    /** Provision a new cPanel account — the WHMCS "Create" module function. */
    public function create(Request $request): Response
    {
        $data = $this->validate($request, [
            'username'      => 'required|max:16',
            'domain'        => 'required|max:180',
            'plan'          => 'required|max:120',
            'contact_email' => 'nullable|email|max:180',
            'client_id'     => 'nullable|exists:clients,id',
        ], ['contact_email' => 'Contact email']);

        // cPanel usernames: lowercase alphanumeric, must not start with a digit.
        $username = strtolower(trim($data['username']));
        if (! preg_match('/^[a-z][a-z0-9]{0,15}$/', $username)) {
            Session::flash('error', 'Usernames must be lowercase letters/numbers, start with a letter, and be at most 16 characters.');

            return $this->redirect(route('admin.hosting.index'));
        }

        if (HostingAccount::firstWhere('username', $username)) {
            Session::flash('error', "An account named '{$username}' already exists.");

            return $this->redirect(route('admin.hosting.index'));
        }

        $whm = WhmClientFactory::make();
        $client = $data['client_id'] ? Client::find($data['client_id']) : null;
        $email = $data['contact_email'] ?: ($client['email'] ?? '');

        // WHM first, row second: a local row for an account the server refused
        // would be pure fiction. lastError carries WHM's actual reason.
        if (! $whm->available() || ! $whm->createAccount($username, trim($data['domain']), $data['plan'], (string) $email)) {
            Session::flash('error', 'Could not create the account: ' . ($whm->lastError() ?: 'unknown WHM error.'));

            return $this->redirect(route('admin.hosting.index'));
        }

        $account = HostingAccount::create([
            'client_id' => $client['id'] ?? null,
            'domain'    => trim($data['domain']),
            'username'  => $username,
            'plan'      => $data['plan'],
            'status'    => 'active',
            'server'    => config('whm.server_label'),
            'synced_at' => now(),
        ]);

        AuditLog::record('hosting.created', 'hosting_account', $account['id'], ['domain' => $account['domain'], 'plan' => $data['plan']]);
        Session::flash('success', $account['domain'] . ' provisioned on ' . config('whm.server_label') . '.');

        return $this->redirect(route('admin.hosting.index'));
    }

    /** Move an account to a different package — the WHMCS "Change Package" function. */
    public function changePackage(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);
        $plan = trim((string) $request->input('plan', ''));

        if ($plan === '') {
            Session::flash('error', 'Choose a package first.');

            return $this->redirect(route('admin.hosting.index'));
        }

        $whm = WhmClientFactory::make();
        if (! $whm->available() || ! $whm->changePackage((string) $account['username'], $plan)) {
            Session::flash('error', 'Package change failed: ' . ($whm->lastError() ?: 'unknown WHM error.'));

            return $this->redirect(route('admin.hosting.index'));
        }

        HostingAccount::updateById($id, ['plan' => $plan]);
        AuditLog::record('hosting.package_changed', 'hosting_account', $id, ['domain' => $account['domain'], 'to' => $plan]);
        Session::flash('success', $account['domain'] . ' moved to ' . $plan . '.');

        return $this->redirect(route('admin.hosting.index'));
    }

    /** Set a new cPanel password — the WHMCS "Change Password" function. */
    public function changePassword(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);
        $password = (string) $request->input('password', '');

        if (strlen($password) < 12) {
            Session::flash('error', 'Use at least 12 characters for a cPanel password.');

            return $this->redirect(route('admin.hosting.index'));
        }

        $whm = WhmClientFactory::make();
        if (! $whm->available() || ! $whm->changePassword((string) $account['username'], $password)) {
            Session::flash('error', 'Password change failed: ' . ($whm->lastError() ?: 'unknown WHM error.'));

            return $this->redirect(route('admin.hosting.index'));
        }

        // Deliberately NOT logging the password anywhere, including the audit meta.
        AuditLog::record('hosting.password_changed', 'hosting_account', $id, ['domain' => $account['domain']]);
        Session::flash('success', 'cPanel password updated for ' . $account['domain'] . '.');

        return $this->redirect(route('admin.hosting.index'));
    }

    /** Permanently remove an account — the WHMCS "Terminate" function. */
    public function terminate(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);

        // Typed confirmation, same idea as db:reset: this deletes a live website
        // and its mail. A misclick must not be enough.
        if (trim((string) $request->input('confirm_domain', '')) !== (string) $account['domain']) {
            Session::flash('error', 'Type the domain exactly to confirm termination — nothing was changed.');

            return $this->redirect(route('admin.hosting.index'));
        }

        $whm = WhmClientFactory::make();
        if (! $whm->available() || ! $whm->terminateAccount((string) $account['username'])) {
            Session::flash('error', 'Termination failed: ' . ($whm->lastError() ?: 'unknown WHM error.'));

            return $this->redirect(route('admin.hosting.index'));
        }

        // Keep the row as history rather than deleting it — an account that
        // existed and was destroyed is exactly what an audit needs to show.
        HostingAccount::updateById($id, ['status' => 'terminated']);
        AuditLog::record('hosting.terminated', 'hosting_account', $id, ['domain' => $account['domain']]);
        Session::flash('success', $account['domain'] . ' terminated on the server.');

        return $this->redirect(route('admin.hosting.index'));
    }

    public function sync(Request $request): Response
    {
        try {
            $n = (new HostingService())->sync();
            if ($n === null) {
                Session::flash('error', 'WHM is not connected. Add WHM_HOST, WHM_USERNAME and WHM_API_TOKEN to your .env to enable syncing.');
            } else {
                Session::flash('success', "Synced {$n} account(s) from WHM.");
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'WHM sync failed: ' . $e->getMessage());
        }

        return $this->redirect(route('admin.hosting.index'));
    }

    public function assign(Request $request, string $id): Response
    {
        HostingAccount::findOrFail($id);
        HostingAccount::updateById($id, [
            'client_id' => $request->input('client_id') ? (int) $request->input('client_id') : null,
        ]);
        Session::flash('success', 'Account assignment updated.');

        return $this->redirect(route('admin.hosting.index'));
    }

    public function suspend(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);
        $whm = WhmClientFactory::make();
        if ($whm->available()) {
            $whm->suspendAccount((string) $account['username'], trim((string) $request->input('reason', 'Suspended by administrator')));
        }
        HostingAccount::updateById($id, ['status' => 'suspended']);
        AuditLog::record('hosting.suspended', 'hosting_account', $id, ['domain' => $account['domain'] ?? null]);
        Session::flash('success', $account['domain'] . ' suspended' . ($whm->available() ? ' on the server.' : ' (recorded locally — WHM not connected).'));

        return $this->redirect(route('admin.hosting.index'));
    }

    public function unsuspend(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);
        $whm = WhmClientFactory::make();
        if ($whm->available()) {
            $whm->unsuspendAccount((string) $account['username']);
        }
        HostingAccount::updateById($id, ['status' => 'active']);
        AuditLog::record('hosting.reactivated', 'hosting_account', $id, ['domain' => $account['domain'] ?? null]);
        Session::flash('success', $account['domain'] . ' reactivated.');

        return $this->redirect(route('admin.hosting.index'));
    }
}
