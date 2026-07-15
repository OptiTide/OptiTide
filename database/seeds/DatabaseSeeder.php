<?php

use App\Models\Blog;
use App\Models\Board;
use App\Models\BoardCard;
use App\Models\BoardColumn;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\HostingAccount;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Invoices\InvoiceService;
use App\Services\Support\TicketService;

return new class {
    public function run(callable $out): void
    {
        $out('Seeding service lines…');
        $categories = $this->categories();

        $out('Seeding service catalogue…');
        $this->services($categories);

        $out('Seeding users…');
        $this->user('Michael Long', 'Hello@OptiTide.io', User::ROLE_ADMIN);
        $this->user('Support Staff', 'staff@optitide.io', User::ROLE_STAFF);

        $out('Seeding demo client + portal login…');
        $client = $this->demoClient();
        $this->user('Demo Client', 'client@example.com', User::ROLE_CLIENT, $client['id']);
        User::query()->where('email', 'client@example.com')->update(['terms_accepted_at' => now()]);

        $this->demoEngagementsAndInvoices($client);

        $out('Seeding starter blog articles…');
        $this->blogs();

        $out('Seeding project boards…');
        $this->boards($client);

        $out('Seeding helpdesk + hosting demo data…');
        $this->support($client);
        $this->hosting($client);

        $out('');
        $out('Done. Local logins (password: "password"):');
        $out('  Hello@OptiTide.io    (admin)');
        $out('  staff@optitide.io    (staff)');
        $out('  client@example.com   (client portal)');
    }

    private function categories(): array
    {
        $lines = [
            ['Web Design & Development', 'web-design'],
            ['Search Engine Optimisation (SEO)', 'seo'],
            ['Social Media Marketing (SMM)', 'smm'],
            ['Web Hosting', 'hosting'],
        ];

        $ids = [];
        foreach ($lines as $i => [$name, $slug]) {
            $existing = ServiceCategory::firstWhere('slug', $slug);
            $ids[$slug] = $existing['id'] ?? ServiceCategory::create([
                'name'       => $name,
                'slug'       => $slug,
                'sort_order' => $i,
            ])['id'];
        }

        return $ids;
    }

    private function services(array $categories): void
    {
        // Named package plans per service line (prices in cents, AUD, GST-incl).
        $catalogue = [
            // Web Design & Development — 2 plans + 1 custom (quote)
            ['web-design', 'Starter Website', 'one_off', null, 75000],
            ['web-design', 'Business Website', 'one_off', null, 150000],
            ['web-design', 'Custom Website', 'one_off', null, 0],
            // SEO — 3 plans (monthly)
            ['seo', 'SEO Essentials', 'recurring', Service::INTERVAL_MONTHLY, 75000],
            ['seo', 'SEO Growth', 'recurring', Service::INTERVAL_MONTHLY, 150000],
            ['seo', 'Custom SEO', 'recurring', Service::INTERVAL_MONTHLY, 250000],
            // SMM — 1 plan
            ['smm', 'Social Media Management', 'recurring', Service::INTERVAL_MONTHLY, 25000],
            // Web Hosting — 2 plans (unmanaged / managed)
            ['hosting', 'Unmanaged Hosting', 'recurring', Service::INTERVAL_MONTHLY, 2500],
            ['hosting', 'Managed Hosting', 'recurring', Service::INTERVAL_MONTHLY, 5000],
        ];

        foreach ($catalogue as [$slug, $name, $billing, $interval, $price]) {
            if (Service::firstWhere('name', $name)) {
                continue;
            }
            Service::create([
                'category_id'  => $categories[$slug] ?? null,
                'name'         => $name,
                'billing_type' => $billing,
                'interval'     => $interval,
                'price_cents'  => $price,
                'currency'     => 'AUD',
                'active'       => 1,
            ]);
        }
    }

    private function user(string $name, string $email, string $role, int|string|null $clientId = null): void
    {
        if (User::findByEmail($email)) {
            return;
        }

        User::create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role'          => $role,
            'client_id'     => $clientId,
            'status'        => 'active',
        ]);
    }

    private function support(array $client): void
    {
        if (Ticket::firstWhere('client_id', $client['id'])) {
            return;
        }

        $clientUser = User::findByEmail('client@example.com');
        $staffUser = User::findByEmail('staff@optitide.io');
        $tickets = new TicketService();

        $ticket = $tickets->open(
            $client['id'],
            $clientUser['id'] ?? null,
            'Can we add online bookings to the website?',
            'Web Design',
            'normal',
            "Hi team,\n\nWe'd love to let customers book a table directly from the site. Is that something you can add to our current plan?\n\nThanks!",
        );

        $tickets->reply(
            $ticket['id'],
            $staffUser['id'] ?? null,
            "Absolutely — we can add a booking widget that emails you each reservation. I'll put together a quick quote and send it through today.",
            true,
        );
    }

    private function hosting(array $client): void
    {
        if (HostingAccount::firstWhere('username', 'coastca')) {
            return;
        }

        $demo = [
            ['coastlinecafe.com.au', 'coastca', 'Managed - 10GB', 'active', '203.0.113.24', 2450, 10240],
            ['coastlinecatering.com.au', 'coastcat', 'Managed - 10GB', 'active', '203.0.113.24', 880, 10240],
        ];

        foreach ($demo as [$domain, $user, $plan, $status, $ip, $used, $limit]) {
            HostingAccount::create([
                'client_id'     => $client['id'],
                'domain'        => $domain,
                'username'      => $user,
                'plan'          => $plan,
                'status'        => $status,
                'ip_address'    => $ip,
                'disk_used_mb'  => $used,
                'disk_limit_mb' => $limit,
                'server'        => 'Primary Server',
                'synced_at'     => now(),
            ]);
        }
    }

    private function boards(array $client): void
    {
        $blueprint = [
            ['web-design', 'Web Design', ['Backlog', 'In Design', 'In Development', 'Review', 'Launched'], [
                ['Backlog', 'New brochure site — Coastline Cafe', true],
                ['In Design', 'Homepage mockups', true],
                ['In Development', 'Booking form integration', false],
            ]],
            ['seo', 'SEO', ['Backlog', 'In Progress', 'On-Page', 'Reporting', 'Done'], [
                ['Backlog', 'Keyword research — local terms', true],
                ['In Progress', 'Fix crawl errors', false],
                ['Reporting', 'July ranking report', true],
            ]],
            ['smm', 'Social Media', ['Ideas', 'Scheduled', 'Published', 'Reporting'], [
                ['Ideas', 'Winter promo campaign concept', false],
                ['Scheduled', 'Weekly tips carousel', true],
                ['Published', 'Customer spotlight post', true],
            ]],
        ];

        foreach ($blueprint as $i => [$key, $name, $columnNames, $cards]) {
            if (Board::byKey($key)) {
                continue;
            }

            $board = Board::create(['key' => $key, 'name' => $name, 'position' => $i]);

            $columnIds = [];
            foreach ($columnNames as $ci => $colName) {
                $col = BoardColumn::create(['board_id' => $board['id'], 'name' => $colName, 'position' => $ci]);
                $columnIds[$colName] = $col['id'];
            }

            $pos = [];
            foreach ($cards as [$colName, $title, $linkClient]) {
                $columnId = $columnIds[$colName];
                $pos[$columnId] = ($pos[$columnId] ?? -1) + 1;
                BoardCard::create([
                    'board_id'  => $board['id'],
                    'column_id' => $columnId,
                    'client_id' => $linkClient ? $client['id'] : null,
                    'title'     => $title,
                    'notes'     => null,
                    'due_date'  => null,
                    'position'  => $pos[$columnId],
                ]);
            }
        }
    }

    private function blogs(): void
    {
        $posts = require __DIR__ . '/blog_posts.php';

        foreach ($posts as $i => [$category, $title, $excerpt, $keywords, $body]) {
            $slug = Blog::slugify($title);
            if (Blog::firstWhere('slug', $slug)) {
                continue;
            }

            Blog::create([
                'title'            => $title,
                'slug'             => $slug,
                'excerpt'          => $excerpt,
                'body'             => $body,
                'category'         => $category,
                'author'           => 'OptiTide',
                'keywords'         => $keywords,
                'meta_title'       => null,
                'meta_description' => $excerpt,
                'cover_image'      => null,
                'status'           => Blog::STATUS_PUBLISHED,
                // Stagger publish dates so the feed looks natural (newest first).
                'published_at'     => date('Y-m-d H:i:s', strtotime('-' . ($i * 3) . ' days')),
                'views'            => 0,
            ]);
        }
    }

    private function demoClient(): array
    {
        return Client::firstWhere('email', 'client@example.com') ?? Client::create([
            'business_name'    => 'Coastline Cafe',
            'contact_name'     => 'Jordan Rivers',
            'email'            => 'client@example.com',
            'phone'            => '0400 000 000',
            'abn'              => '12 345 678 901',
            'address_line1'    => '1 Marine Parade',
            'address_locality' => 'Byron Bay',
            'address_region'   => 'NSW',
            'address_postcode' => '2481',
            'address_country'  => 'Australia',
            'status'           => 'active',
        ]);
    }

    private function demoEngagementsAndInvoices(array $client): void
    {
        // Only seed sample billing data once.
        if (Invoice::firstWhere('client_id', $client['id'])) {
            return;
        }

        $hosting = Service::firstWhere('name', 'Managed Hosting');
        $engagement = ClientService::create([
            'client_id'         => $client['id'],
            'service_id'        => $hosting['id'] ?? null,
            'label'             => 'Managed Hosting',
            'billing_type'      => 'recurring',
            'interval'          => Service::INTERVAL_MONTHLY,
            'price_cents'       => 5000,
            'currency'          => 'AUD',
            'status'            => 'active',
            'started_at'        => today(),
            'next_invoice_date' => date('Y-m-d', strtotime('+1 month')),
        ]);
        ClientService::updateById($engagement['id'], ['reference' => 'JOB-' . str_pad((string) $engagement['id'], 6, '0', STR_PAD_LEFT)]);

        $invoices = new InvoiceService();

        // A sent (payable) invoice.
        $invoices->create([
            'client_id'  => $client['id'],
            'status'     => Invoice::STATUS_SENT,
            'issue_date' => today(),
            'due_date'   => date('Y-m-d', strtotime('+14 days')),
            'notes'      => 'Thanks for choosing OptiTide.',
        ], [
            ['description' => 'Business Website — design & build', 'quantity' => 1, 'unit_price_cents' => 150000],
        ]);

        // A paid invoice with a recorded payment for history.
        $paid = $invoices->create([
            'client_id'  => $client['id'],
            'status'     => Invoice::STATUS_SENT,
            'issue_date' => date('Y-m-d', strtotime('-20 days')),
            'due_date'   => date('Y-m-d', strtotime('-6 days')),
        ], [
            ['description' => 'Managed Hosting (setup)', 'quantity' => 1, 'unit_price_cents' => 5000],
        ]);
        $invoices->recordPayment($paid['id'], (int) $paid['total_cents'], 'payid', 'DEMO-REF', date('Y-m-d', strtotime('-10 days')), null);
    }
};
