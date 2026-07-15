<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\HostingAccount;
use App\Services\Whm\WhmClientFactory;

/**
 * Clients view + manage their own hosting. The "Open cPanel" action mints a
 * one-time WHM login URL (when WHM is connected) and redirects — scoped strictly
 * to the client's own account.
 */
class HostingController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.hosting.index', [
            'title'     => 'Hosting & Apps',
            'accounts'  => $clientId ? HostingAccount::forClient($clientId) : [],
            'apps'      => $clientId ? \App\Models\ClientApp::forClient($clientId) : [],
            'connected' => WhmClientFactory::make()->available(),
        ]);
    }

    public function login(Request $request, string $id): Response
    {
        $account = HostingAccount::findOrFail($id);

        // IDOR guard — the account must belong to the signed-in client.
        if ((string) $account['client_id'] !== (string) Auth::clientId()) {
            $this->abort(404, 'Hosting account not found.');
        }

        $url = WhmClientFactory::make()->createCpanelSession((string) $account['username']);
        if (! $url) {
            $this->flash('error', 'One-click cPanel login isn\'t available right now — please contact support and we\'ll help you in.');

            return $this->redirectRoute('portal.hosting');
        }

        return $this->redirect($url);
    }
}
