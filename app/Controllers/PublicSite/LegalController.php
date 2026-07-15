<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class LegalController extends Controller
{
    public function terms(Request $request): Response
    {
        return $this->view('public.legal.terms', ['title' => 'Terms of Service']);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('public.legal.privacy', ['title' => 'Privacy Policy']);
    }

    public function refund(Request $request): Response
    {
        return $this->view('public.legal.refund', ['title' => 'Refund & Cancellation Policy']);
    }
}
