<?php

namespace Database\Seeders;

use App\Models\FormSchema;
use Illuminate\Database\Seeder;

class FormSchemaSeeder extends Seeder
{
    /**
     * Dynamic intake form definitions. Each field is rendered by the client
     * portal's schema-driven form builder; `type` maps to an HTML input type.
     */
    public function run(): void
    {
        $baseFields = [
            ['name' => 'business_name', 'label' => 'Business name', 'type' => 'text', 'required' => true],
            ['name' => 'business_description', 'label' => 'What does your business do?', 'type' => 'textarea', 'required' => true],
            ['name' => 'industry', 'label' => 'Industry', 'type' => 'text', 'required' => true],
            ['name' => 'logo', 'label' => 'Upload your logo (high resolution)', 'type' => 'file', 'required' => true, 'accept' => 'image/*,.svg'],
            ['name' => 'primary_color', 'label' => 'Primary brand color', 'type' => 'color', 'required' => true],
            ['name' => 'secondary_color', 'label' => 'Secondary brand color', 'type' => 'color', 'required' => false],
            ['name' => 'existing_website', 'label' => 'Existing website (if any)', 'type' => 'url', 'required' => false],
        ];

        $intermediateExtras = [
            ['name' => 'brand_imagery', 'label' => 'Existing brand imagery', 'type' => 'file', 'required' => false, 'multiple' => true, 'accept' => 'image/*'],
            ['name' => 'font_files', 'label' => 'Brand font files (if any)', 'type' => 'file', 'required' => false, 'multiple' => true, 'accept' => '.woff,.woff2,.ttf,.otf'],
            ['name' => 'competitors', 'label' => 'Competitor websites you admire', 'type' => 'textarea', 'required' => false],
            ['name' => 'pages_needed', 'label' => 'Pages you need', 'type' => 'textarea', 'required' => true],
            ['name' => 'social_profiles', 'label' => 'Social media profile links', 'type' => 'textarea', 'required' => false],
        ];

        $exhaustiveExtras = [
            ['name' => 'target_audience', 'label' => 'Describe your target audience in detail', 'type' => 'textarea', 'required' => true],
            ['name' => 'brand_voice', 'label' => 'Brand voice & tone', 'type' => 'select', 'required' => true,
                'options' => ['Professional', 'Friendly', 'Bold', 'Luxurious', 'Playful', 'Technical']],
            ['name' => 'must_have_features', 'label' => 'Must-have functionality (booking, e-commerce, member area...)', 'type' => 'textarea', 'required' => true],
            ['name' => 'integrations', 'label' => 'Third-party integrations required', 'type' => 'textarea', 'required' => false],
            ['name' => 'content_ready', 'label' => 'Is your copy/content ready?', 'type' => 'select', 'required' => true,
                'options' => ['Yes, all ready', 'Partially', 'No, we need copywriting']],
            ['name' => 'launch_deadline', 'label' => 'Target launch date', 'type' => 'date', 'required' => false],
            ['name' => 'inspiration', 'label' => 'Design inspiration links & notes', 'type' => 'textarea', 'required' => false],
        ];

        $schemas = [
            ['key' => 'basic_onboarding', 'name' => 'Standard Website Onboarding',
                'description' => 'Basic project requirements for the Standard Website tier.',
                'schema' => ['fields' => $baseFields]],
            ['key' => 'intermediate_onboarding', 'name' => 'Pro Website Onboarding',
                'description' => 'Intermediate project requirements for the Pro Website tier.',
                'schema' => ['fields' => [...$baseFields, ...$intermediateExtras]]],
            ['key' => 'exhaustive_onboarding', 'name' => 'Custom Website Onboarding',
                'description' => 'Exhaustive project requirements for fully bespoke builds.',
                'schema' => ['fields' => [...$baseFields, ...$intermediateExtras, ...$exhaustiveExtras]]],
        ];

        foreach ($schemas as $schema) {
            FormSchema::updateOrCreate(
                ['key' => $schema['key']],
                [...$schema, 'is_active' => true],
            );
        }
    }
}
