<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Service;
use App\Models\ServiceCategory;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        // The marketing site is public. The nav adapts (Login vs Dashboard).
        return $this->view('public.home', [
            'packages' => $this->packages(),
        ]);
    }

    /** Active service packages grouped by service line, for the pricing section. */
    protected function packages(): array
    {
        $services = Service::active();
        $groups = [];

        foreach (ServiceCategory::ordered() as $line) {
            $plans = array_values(array_filter(
                $services,
                fn ($s) => (string) $s['category_id'] === (string) $line['id']
            ));
            usort($plans, fn ($a, $b) => (int) $a['price_cents'] <=> (int) $b['price_cents']);
            if ($plans !== []) {
                $groups[] = ['line' => $line, 'plans' => $plans];
            }
        }

        return $groups;
    }
}
