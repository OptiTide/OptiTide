<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;
use App\Models\Service;
use App\Support\Money;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();
        $currency = config('company.currency', 'AUD');
        $engagements = $clientId ? ClientService::forClient($clientId) : [];

        $active = 0;
        $monthly = 0; // recurring spend normalised to a monthly figure
        foreach ($engagements as $engagement) {
            if ($engagement['status'] === 'active') {
                $active++;
                if ($engagement['billing_type'] === 'recurring') {
                    $months = Service::intervalMonths($engagement['interval']);
                    $monthly += (int) round((int) $engagement['price_cents'] / max(1, $months));
                }
            }
        }

        return $this->view('client.services', [
            'title'        => 'My Services',
            'engagements'  => $engagements,
            'active'       => $active,
            'monthly'      => new Money($monthly, $currency),
        ]);
    }
}
