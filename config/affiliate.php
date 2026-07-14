<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission rate
    |--------------------------------------------------------------------------
    |
    | The affiliate commission on a referred client's first paid order, in
    | basis points (1000 = 10%). Stored on each commission row as
    | `rate_basis_points` so historical commissions keep the rate they were
    | earned at even if this default later changes.
    |
    */

    'commission_basis_points' => (int) env('AFFILIATE_COMMISSION_BPS', 1000),

];
