<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class HealthController extends Controller
{
    public function index(Request $request): Response
    {
        $db = 'ok';
        try {
            Database::instance()->selectOne('SELECT 1 AS ok');
        } catch (\Throwable $e) {
            $db = 'error';
        }

        return $this->json([
            'status'   => $db === 'ok' ? 'ok' : 'degraded',
            'database' => $db,
            'time'     => now(),
        ], $db === 'ok' ? 200 : 503);
    }
}
