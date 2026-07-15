<?php

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Meeting;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = Auth::clientId();

        return $this->view('client.meetings.index', [
            'title'    => 'Meetings',
            'meetings' => $clientId ? Meeting::forClient($clientId) : [],
        ]);
    }
}
