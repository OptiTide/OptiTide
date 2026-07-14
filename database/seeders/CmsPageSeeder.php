<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            CmsPage::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'body' => $page['body'],
                    'status' => 'published',
                    'show_in_footer' => true,
                    // No explicit meta_title — metaTitle() derives it from the
                    // (translatable) title so localised pages get localised SEO.
                    'meta' => [],
                ],
            );
        }
    }

    /** @return array<int, array{slug:string,title:string,body:string}> */
    protected function pages(): array
    {
        return [
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'body' => '<p>OptiTide respects your privacy. This policy explains what personal '
                    .'information we collect, how we use it, and your rights.</p>'
                    .'<h2>Information we collect</h2>'
                    .'<p>We collect information you give us — such as your name, email, and business '
                    .'details — when you contact us, request a quote, place an order, or use our client '
                    .'portal. We also collect basic technical data (such as your IP address and browser) '
                    .'to keep our services secure and working.</p>'
                    .'<h2>How we use it</h2>'
                    .'<ul><li>To deliver the services you request and support your account.</li>'
                    .'<li>To communicate with you about your projects, invoices, and support requests.</li>'
                    .'<li>To improve our services and meet our legal obligations.</li></ul>'
                    .'<h2>Your rights</h2>'
                    .'<p>You can ask us to access, correct, or delete the personal information we hold '
                    .'about you. Contact us and we will respond in line with the Australian Privacy '
                    .'Principles.</p>',
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'body' => '<p>These terms govern your use of the OptiTide website and services. By using '
                    .'our services you agree to them.</p>'
                    .'<h2>Our services</h2>'
                    .'<p>We provide web design, SEO, social media, and hosting services. The specific '
                    .'scope, price, and timeline for your project are set out in your order and any '
                    .'related agreement.</p>'
                    .'<h2>Your responsibilities</h2>'
                    .'<ul><li>Provide accurate information and the materials we need to do the work.</li>'
                    .'<li>Pay invoices by their due date.</li>'
                    .'<li>Use our services lawfully.</li></ul>'
                    .'<h2>Liability</h2>'
                    .'<p>Nothing in these terms limits rights you have under the Australian Consumer Law. '
                    .'To the extent permitted by law, our liability is limited to re-supplying the '
                    .'affected service.</p>',
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund Policy',
                'body' => '<p>We want you to be happy with our work.</p>'
                    .'<h2>Change of mind</h2>'
                    .'<p>All prices are in Australian Dollars (AUD). No refunds for change of mind. '
                    .'This does not limit your rights under the Australian Consumer Law.</p>'
                    .'<h2>If something is wrong</h2>'
                    .'<p>If a service is not delivered as agreed, or is not of acceptable quality, you '
                    .'may be entitled to a remedy under the Australian Consumer Law. Contact us and we '
                    .'will work with you to put it right.</p>',
            ],
        ];
    }
}
