<?php

return [
    // Turn the holding page on by setting COMING_SOON=true (e.g. in Coolify).
    // When on, all public pages redirect to /coming-soon.
    'enabled' => env('COMING_SOON', false),

    // Preview bypass: visit /?preview=<secret> once to drop a cookie and see the
    // real site while the gate is on. Staff (admin/va) always bypass.
    'secret' => env('COMING_SOON_SECRET'),
];
