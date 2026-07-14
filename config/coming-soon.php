<?php

return [
    // ON by default (pre-launch): all public pages redirect to /coming-soon.
    // Set COMING_SOON=false in the environment to run the full site (e.g. at
    // launch, or on local dev).
    'enabled' => env('COMING_SOON', true),

    // Preview bypass: visit /?preview=<secret> once to drop a cookie and see the
    // real site while the gate is on. Staff (admin/va) always bypass.
    'secret' => env('COMING_SOON_SECRET'),
];
