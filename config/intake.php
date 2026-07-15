<?php

/**
 * Per-service project intake questions, shown right after a client orders so we
 * get a proper brief up front. Keyed by service-line slug. Lines with no entry
 * here (e.g. hosting) simply skip the questionnaire.
 *
 * Each question: key, label, type (text|textarea|url|select), options (for select).
 */
return [
    'web-design' => [
        'label'     => 'Web Design project brief',
        'questions' => [
            ['key' => 'business', 'label' => 'Tell us about your business — what you do and who your customers are', 'type' => 'textarea'],
            ['key' => 'goals', 'label' => 'What are the main goals for your new website?', 'type' => 'textarea'],
            ['key' => 'pages', 'label' => 'Which pages do you need? (e.g. Home, About, Services, Contact)', 'type' => 'textarea'],
            ['key' => 'examples', 'label' => 'Any websites you love (or dislike)? Paste a few links', 'type' => 'textarea'],
            ['key' => 'branding', 'label' => 'Do you have a logo and brand colours ready?', 'type' => 'select', 'options' => ['Yes, ready to go', 'Partly', 'No — we need help']],
            ['key' => 'content', 'label' => 'Is your content (text & images) ready?', 'type' => 'select', 'options' => ['Yes', 'Some of it', 'No']],
            ['key' => 'deadline', 'label' => 'Any deadline we should know about?', 'type' => 'text'],
        ],
    ],
    'seo' => [
        'label'     => 'SEO project brief',
        'questions' => [
            ['key' => 'website', 'label' => 'Your website address', 'type' => 'url'],
            ['key' => 'locations', 'label' => 'Which towns, suburbs or regions do you want to rank in?', 'type' => 'textarea'],
            ['key' => 'keywords', 'label' => 'What services or products should you be found for on Google?', 'type' => 'textarea'],
            ['key' => 'competitors', 'label' => 'Who are your main competitors online?', 'type' => 'textarea'],
            ['key' => 'history', 'label' => 'What SEO or marketing have you done so far (if any)?', 'type' => 'textarea'],
            ['key' => 'gbp', 'label' => 'Do you have a Google Business Profile set up?', 'type' => 'select', 'options' => ['Yes', 'Not sure', 'No']],
        ],
    ],
    'smm' => [
        'label'     => 'Social Media project brief',
        'questions' => [
            ['key' => 'platforms', 'label' => 'Which platforms? (Instagram, Facebook, LinkedIn, TikTok…)', 'type' => 'text'],
            ['key' => 'handles', 'label' => 'Your existing account handles or links', 'type' => 'textarea'],
            ['key' => 'voice', 'label' => 'How would you describe your brand\'s voice and tone?', 'type' => 'textarea'],
            ['key' => 'themes', 'label' => 'Content ideas or themes you\'d like us to focus on', 'type' => 'textarea'],
            ['key' => 'goals', 'label' => 'What do you want social media to achieve for you?', 'type' => 'textarea'],
        ],
    ],
];
