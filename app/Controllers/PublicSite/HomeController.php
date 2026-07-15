<?php

namespace App\Controllers\PublicSite;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ServiceCategory;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        if (Auth::check()) {
            return $this->redirect(Auth::isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
        }

        return $this->view('public.home', [
            'title'      => config('app.name') . ' — Digital agency billing',
            'categories' => ServiceCategory::ordered(),
        ]);
    }
}
