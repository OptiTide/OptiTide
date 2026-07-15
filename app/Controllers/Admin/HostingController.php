<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Client;
use App\Models\HostingAccount;
use App\Services\Whm\HostingService;
use App\Services\Whm\WhmClientFactory;

class HostingController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin.hosting.index', [
            'title'        => 'Hosting',
            'accounts'     => HostingAccount::query()->orderBy('domain')->get(),
            'connected'    => WhmClientFactory::make()->available(),
            'server'       => config('whm.server_label'),
            'host'         => config('whm.host'),
            'clients'      => Client::query()->orderBy('business_name')->get(),
            'client_names' => array_column(Client::all(), 'business_name', 'id'),
        ]);
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
        Session::flash('success', $account['domain'] . ' reactivated.');

        return $this->redirect(route('admin.hosting.index'));
    }
}
