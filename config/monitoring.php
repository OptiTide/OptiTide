<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server / uptime monitoring
    |--------------------------------------------------------------------------
    |
    | Thresholds for App\Services\MonitorService. A monitor must fail this many
    | consecutive checks before it is flagged Down and a support ticket is
    | auto-opened — this debounces transient blips so a single dropped request
    | doesn't page the team. SSL certificates raise a ticket once they fall
    | within `ssl_expiry_days` of expiry.
    |
    */

    'failure_threshold' => (int) env('MONITOR_FAILURE_THRESHOLD', 2),

    'ssl_expiry_days' => (int) env('MONITOR_SSL_EXPIRY_DAYS', 14),

    // HTTP timeouts (seconds) for each uptime poll.
    'timeout' => (int) env('MONITOR_TIMEOUT', 10),

    'connect_timeout' => (int) env('MONITOR_CONNECT_TIMEOUT', 5),

];
