<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClientService;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.services', [
            'title'       => 'My Services',
            'engagements' => $clientId ? ClientService::forClient($clientId) : [],
        ]);
    }
}
