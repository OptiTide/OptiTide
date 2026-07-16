<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Currency;
use App\Support\Features;

class CurrencyController extends Controller
{
    /** Switch the display currency and return to where the visitor was. */
    public function set(Request $request): Response
    {
        if (! Features::enabled('currency_switcher')) {
            $this->abort(404, 'Page not found.');
        }

        Currency::set((string) $request->input('c', $request->query('c', '')));

        // Only ever redirect to a local path (never an attacker-supplied host).
        $return = (string) $request->input('return', $request->query('return', '/'));
        if ($return === '' || $return[0] !== '/' || str_starts_with($return, '//')) {
            $return = '/';
        }

        return $this->redirect($return);
    }
}
