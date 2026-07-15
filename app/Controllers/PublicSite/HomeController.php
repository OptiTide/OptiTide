<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        // The marketing site is public — visible to everyone. The nav adapts
        // (Client Login vs Dashboard) based on auth state inside the view.
        return $this->view('public.home');
    }
}
