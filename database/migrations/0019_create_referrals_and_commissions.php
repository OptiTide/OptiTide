<?php

use App\Core\Blueprint;
use App\Core\Database;
use App\Core\Schema;

return new class {
    public function up(): void
    {
        $db = Database::instance();

        // Referral code on every user.
        $db->statement('ALTER TABLE users ADD COLUMN referral_code VARCHAR(20)');

        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $seen = [];
        foreach ($db->select('SELECT id FROM users') as $row) {
            do {
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= $chars[random_int(0, strlen($chars) - 1)];
                }
            } while (isset($seen[$code]));
            $seen[$code] = true;
            $db->affecting('UPDATE users SET referral_code = ? WHERE id = ?', [$code, $row['id']]);
        }

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id', 'users');
            $table->foreignId('referred_id', 'users');
            $table->timestamps();
            $table->unique('referred_id');
            $table->index('referrer_id');
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id', 'users');
            $table->foreignId('client_id', 'clients', 'set null', true);
            $table->foreignId('invoice_id', 'invoices', 'set null', true);
            $table->integer('amount_cents', false, 0);
            $table->string('currency', 3, false, 'AUD');
            $table->integer('rate_bps', false, 0);
            $table->string('status', 20, false, 'pending');
            $table->timestamps();
            $table->unique('invoice_id');
            $table->index('referrer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('referrals');
        try {
            Database::instance()->statement('ALTER TABLE users DROP COLUMN referral_code');
        } catch (\Throwable $e) {
            // older SQLite — ignore
        }
    }
};
