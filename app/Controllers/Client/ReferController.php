<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\User;
use App\Services\Referrals\CommissionService;
use App\Services\Referrals\ReferralService;
use App\Support\Features;

class ReferController extends Controller
{
    public function index(Request $request): Response
    {
        // Handing a client a referral link that no longer earns them anything is
        // worse than not offering one.
        if (! Features::enabled('affiliate')) {
            $this->abort(404, 'The referral program is not available.');
        }

        $user = Auth::user();
        $code = ReferralService::ensureCode($user);
        $currency = config('company.currency', 'AUD');

        $referrals = Referral::forReferrer($user['id']);
        $referredNames = [];
        foreach ($referrals as $r) {
            $referred = User::find($r['referred_id']);
            $client = $referred && $referred['client_id'] ? Client::find($referred['client_id']) : null;
            $referredNames[$r['id']] = $client['business_name'] ?? ($referred['name'] ?? 'Referred user');
        }

        return $this->view('client.refer.index', [
            'title'         => 'Refer & Earn',
            'code'          => $code,
            'link'          => url('r/' . $code),
            'ratePercent'   => rtrim(rtrim(number_format(config('affiliate.commission_bps', 1000) / 100, 2), '0'), '.'),
            'referrals'     => $referrals,
            'referredNames' => $referredNames,
            'commissions'   => Commission::forReferrer($user['id']),
            'summary'       => (new CommissionService())->summary($user['id'], $currency),
        ]);
    }
}
