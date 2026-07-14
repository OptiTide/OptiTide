<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * The OptiTide service catalog. Prices are AUD integer cents.
     */
    public function run(): void
    {
        $catalog = [
            // Web Development tiers — each triggers a different onboarding form.
            ['name' => 'Standard Website', 'category' => 'web_development', 'price' => 75_000, 'onboarding_form_key' => 'basic_onboarding',
                'description' => 'Entry-level web presence for small businesses.',
                'features' => ['Up to 5 pages', 'Mobile responsive design', 'Contact form', 'Basic on-page SEO']],
            ['name' => 'Pro Website', 'category' => 'web_development', 'price' => 150_000, 'onboarding_form_key' => 'intermediate_onboarding',
                'description' => 'Advanced functionality for growing businesses.',
                'features' => ['Up to 15 pages', 'Custom interactions & animations', 'Blog / news module', 'Analytics integration', 'Priority support']],
            ['name' => 'Custom Website', 'category' => 'web_development', 'price' => 250_000, 'onboarding_form_key' => 'exhaustive_onboarding', 'requires_contract' => true,
                'description' => 'Fully bespoke platform engineering, tailored end to end.',
                'features' => ['Unlimited pages', 'Bespoke design system', 'Custom application logic', 'E-commerce ready', 'Digital service agreement & e-signature', 'Dedicated project pipeline']],

            // SEO tiers.
            ['name' => 'Base SEO', 'category' => 'seo', 'price' => 50_000,
                'description' => 'Standalone SEO optimization; no social media deliverables.',
                'features' => ['Technical SEO audit', 'On-page optimization', 'Keyword strategy']],
            ['name' => 'Tier 2 SEO', 'category' => 'seo', 'price' => 100_000,
                'description' => 'SEO plus Basic Social Media Management.',
                'features' => ['Everything in Base SEO', 'Basic SMM (2 platforms)', 'Monthly reporting']],
            ['name' => 'Tier 3 SEO', 'category' => 'seo', 'price' => 150_000,
                'description' => 'SEO plus Full Social Media Management.',
                'features' => ['Everything in Tier 2', 'Full SMM (4 platforms)', 'Content calendar', 'Competitor tracking']],
            ['name' => 'Tier 4 SEO', 'category' => 'seo', 'price' => 250_000,
                'description' => 'Enterprise SEO with Full Social Media Management.',
                'features' => ['Everything in Tier 3', 'Enterprise link building', 'Quarterly strategy reviews', 'Dedicated account manager']],

            // Standalone SMM.
            ['name' => 'Basic SMM', 'category' => 'smm', 'price' => 25_000,
                'description' => 'Standalone social media management.',
                'features' => ['2 platforms', '8 posts per month', 'Engagement monitoring']],

            // Recurring hosting subscriptions.
            ['name' => 'Unmanaged Hosting', 'category' => 'hosting', 'price' => 2_500, 'billing_interval' => 'month', 'requires_contract' => true,
                'description' => 'Basic server provisioning. You manage your own updates.',
                'features' => ['Server provisioning', 'SSL certificate', '99.9% uptime target']],
            ['name' => 'Managed Hosting', 'category' => 'hosting', 'price' => 5_000, 'billing_interval' => 'month', 'requires_contract' => true,
                'description' => 'We handle updates, backups, and minor maintenance.',
                'features' => ['Everything in Unmanaged', 'Core updates & patches', 'Daily backups', 'Uptime & SSL monitoring', 'Minor content changes']],
        ];

        foreach ($catalog as $index => $product) {
            Product::updateOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    ...$product,
                    'currency' => 'AUD',
                    'billing_interval' => $product['billing_interval'] ?? null,
                    'onboarding_form_key' => $product['onboarding_form_key'] ?? null,
                    'requires_contract' => $product['requires_contract'] ?? false,
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
