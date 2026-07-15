<?php

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Invoices\InvoiceService;

return new class {
    public function run(callable $out): void
    {
        $out('Seeding service lines…');
        $categories = $this->categories();

        $out('Seeding service catalogue…');
        $this->services($categories);

        $out('Seeding users…');
        $this->user('OptiTide Admin', 'Hello@OptiTide.io', User::ROLE_ADMIN);
        $this->user('Support VA', 'va@optitide.io', User::ROLE_STAFF);

        $out('Seeding demo client + portal login…');
        $client = $this->demoClient();
        $this->user('Demo Client', 'client@example.com', User::ROLE_CLIENT, $client['id']);

        $this->demoEngagementsAndInvoices($client);

        $out('');
        $out('Done. Local logins (password: "password"):');
        $out('  Hello@OptiTide.io    (admin)');
        $out('  va@optitide.io       (staff)');
        $out('  client@example.com   (client portal)');
    }

    private function categories(): array
    {
        $lines = [
            ['Web Design', 'web-design'],
            ['SEO', 'seo'],
            ['SMM', 'smm'],
            ['Hosting', 'hosting'],
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
        $catalogue = [
            ['web-design', 'Starter Website', 'one_off', null, 149900],
            ['web-design', 'Business Website', 'one_off', null, 299900],
            ['seo', 'SEO Retainer', 'recurring', Service::INTERVAL_MONTHLY, 99000],
            ['smm', 'Social Media Management', 'recurring', Service::INTERVAL_MONTHLY, 79000],
            ['hosting', 'Managed Hosting — Basic', 'recurring', Service::INTERVAL_MONTHLY, 4900],
            ['hosting', 'Managed Hosting — Pro', 'recurring', Service::INTERVAL_MONTHLY, 9900],
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

        $hosting = Service::firstWhere('name', 'Managed Hosting — Basic');
        ClientService::create([
            'client_id'         => $client['id'],
            'service_id'        => $hosting['id'] ?? null,
            'label'             => 'Managed Hosting — Basic',
            'billing_type'      => 'recurring',
            'interval'          => Service::INTERVAL_MONTHLY,
            'price_cents'       => 4900,
            'currency'          => 'AUD',
            'status'            => 'active',
            'started_at'        => today(),
            'next_invoice_date' => date('Y-m-d', strtotime('+1 month')),
        ]);

        $invoices = new InvoiceService();

        // A sent (payable) invoice.
        $invoices->create([
            'client_id'  => $client['id'],
            'status'     => Invoice::STATUS_SENT,
            'issue_date' => today(),
            'due_date'   => date('Y-m-d', strtotime('+14 days')),
            'notes'      => 'Thanks for choosing OptiTide.',
        ], [
            ['description' => 'Business Website — design & build', 'quantity' => 1, 'unit_price_cents' => 299900],
        ]);

        // A paid invoice with a recorded payment for history.
        $paid = $invoices->create([
            'client_id'  => $client['id'],
            'status'     => Invoice::STATUS_SENT,
            'issue_date' => date('Y-m-d', strtotime('-20 days')),
            'due_date'   => date('Y-m-d', strtotime('-6 days')),
        ], [
            ['description' => 'Managed Hosting — Basic (setup)', 'quantity' => 1, 'unit_price_cents' => 4900],
        ]);
        $invoices->recordPayment($paid['id'], (int) $paid['total_cents'], 'payid', 'DEMO-REF', date('Y-m-d', strtotime('-10 days')), null);
    }
};
