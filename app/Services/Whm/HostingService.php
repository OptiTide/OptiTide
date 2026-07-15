<?php

namespace App\Services\Whm;

use App\Models\HostingAccount;

final class HostingService
{
    /**
     * Pull accounts from WHM and upsert them by username. Existing rows keep
     * their client_id linkage. Returns the number synced, or null if WHM is not
     * configured (so the caller can show a "connect it" message).
     */
    public function sync(): ?int
    {
        $client = WhmClientFactory::make();
        if (! $client->available()) {
            return null;
        }

        $count = 0;
        foreach ($client->listAccounts() as $a) {
            if (($a['user'] ?? '') === '') {
                continue;
            }

            $fields = [
                'domain'        => $a['domain'] ?? '',
                'username'      => $a['user'],
                'plan'          => $a['plan'] ?? null,
                'status'        => $a['status'] ?? 'active',
                'ip_address'    => $a['ip'] ?? null,
                'disk_used_mb'  => $a['disk_used_mb'] ?? null,
                'disk_limit_mb' => $a['disk_limit_mb'] ?? null,
                'server'        => $a['server'] ?? null,
                'synced_at'     => now(),
            ];

            $existing = HostingAccount::firstWhere('username', $a['user']);
            if ($existing) {
                HostingAccount::updateById($existing['id'], $fields);
            } else {
                HostingAccount::create($fields + ['client_id' => null]);
            }
            $count++;
        }

        return $count;
    }
}
