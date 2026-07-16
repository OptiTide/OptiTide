<?php

namespace App\Support;

/**
 * Feature toggles — the single read path for config/features.php.
 *
 * Values arrive as either a real bool (config default) or the string '1'/'0'
 * (an admin Setting override applied at boot), so every read is normalised here
 * rather than at ~40 call sites.
 */
final class Features
{
    /**
     * An UNKNOWN key is enabled. A typo ('careerz') must fail toward the feature
     * working — silently disabling a live page because someone misspelled a
     * string is far worse than a toggle that doesn't bite.
     */
    public static function enabled(string $key): bool
    {
        $value = config('features.' . $key);

        if ($value === null) {
            return true;
        }

        // Anything unparseable (a hand-edited settings row) is treated as on,
        // for the same fail-open reason.
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }

    /**
     * The Settings screen's source of truth: key => ['label', 'help'].
     *
     * Help text describes what actually stops working when the switch is off —
     * it's the only warning the owner gets before taking a public page down.
     *
     * @return array<string,array{label:string,help:string}>
     */
    public static function all(): array
    {
        return [
            'live_chat' => [
                'label' => 'Live Chat',
                'help'  => 'The chat bubble on the public site and client portal. Off hides the widget and closes the chat endpoints.',
            ],
            'ai_chat' => [
                'label' => 'AI Chat Replies',
                'help'  => 'Instant AI answers in live chat. Off keeps chat running, but only your team can reply — so watch the Chat inbox.',
            ],
            'careers' => [
                'label' => 'Careers Pages',
                'help'  => 'The public careers listing, role pages and application form. Off returns not found and drops the roles from your sitemap.',
            ],
            'blog' => [
                'label' => 'Blog',
                'help'  => 'The public blog, articles and RSS feed. Off returns not found and drops the articles from your sitemap.',
            ],
            'affiliate' => [
                'label' => 'Referral Program',
                'help'  => 'Refer & Earn in the portal, referral links and commissions. Off stops new referrals being attributed; commissions already earned are unaffected.',
            ],
            'api_credits' => [
                'label' => 'White-Label API',
                'help'  => 'API credits in the portal and the public API endpoint. Off stops new calls and top-ups; existing credit balances are kept.',
            ],
            'meetings' => [
                'label' => 'Meetings',
                'help'  => 'Meeting requests from clients and the admin meetings screen. Off hides both; booked meetings are kept.',
            ],
            'currency_switcher' => [
                'label' => 'Currency Switcher',
                'help'  => 'The AUD/USD picker in the site header. Off shows every price in the default currency.',
            ],
            'quotes' => [
                'label' => 'Quotes',
                'help'  => 'Client quote requests and the quotes screen.',
            ],
        ];
    }
}
