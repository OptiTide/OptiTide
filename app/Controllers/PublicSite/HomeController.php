<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Catalog;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        // The marketing site is public. The nav adapts (Login vs Dashboard).
        // Pricing comes from the real, admin-managed catalogue (Catalog).
        return $this->view('public.home', [
            'packages' => Catalog::grouped(),
            'captcha'  => \App\Support\Captcha::question(),
        ]);
    }
}
