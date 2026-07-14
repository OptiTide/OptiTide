<?php

namespace App\Providers;

use Anthropic\Client as AnthropicSdkClient;
use App\Listeners\CreateHostingContract;
use App\Models\User;
use App\Services\AI\AnthropicClaudeClient;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\Social\NullSocialDistributor;
use App\Services\Social\SocialDistributor;
use App\Services\MonitorService;
use App\Services\Whm\NullWhmClient;
use App\Services\Whm\WhmApiClient;
use App\Services\Whm\WhmClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Spatie\Onboard\Facades\Onboard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The webhook route is registered manually in routes/web.php using a
        // controller that extends Cashier's, so it handles both subscription
        // events and one-time checkout completions.
        Cashier::ignoreRoutes();

        // Bind the real Claude client when a key is configured; otherwise fall
        // back to the fake so local dev and tests can run the AI pipeline.
        $this->app->singleton(ClaudeClient::class, function () {
            $key = config('services.anthropic.key');

            if (blank($key)) {
                return new FakeClaudeClient;
            }

            return new AnthropicClaudeClient(
                new AnthropicSdkClient(apiKey: $key),
                config('services.anthropic.model'),
                config('services.anthropic.effort'),
            );
        });

        // Fail-closed social distribution until a real platform driver is wired.
        $this->app->bind(SocialDistributor::class, NullSocialDistributor::class);

        // WHM server management: real driver when credentials are present,
        // otherwise the fail-closed NullWhmClient.
        $this->app->singleton(WhmClient::class, function () {
            $host = config('services.whm.host');
            $username = config('services.whm.username');
            $token = config('services.whm.api_token');

            if (blank($host) || blank($username) || blank($token)) {
                return new NullWhmClient;
            }

            return new WhmApiClient($host, $username, $token, (int) config('services.whm.port', 2087));
        });

        // Uptime/SSL monitor service, thresholds sourced from config/monitoring.
        $this->app->bind(MonitorService::class, fn () => MonitorService::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Issue a hosting retainer agreement when a subscription is created.
        Event::listen(WebhookHandled::class, CreateHostingContract::class);

        // First-login onboarding: clients must complete their profile before
        // using the portal. The EnsureOnboarded middleware forces the wizard
        // while any step is unfinished. Steps are checked via completeIf, so
        // `onboarded_at` (stamped by the wizard) is the source of truth.
        // The completeIf callback is invoked via app()->call() with the model
        // bound to the parameter named `model` — it MUST be `$model` (a `$user`
        // param would be resolved from the container as a fresh, empty User).
        Onboard::addStep('Complete your profile')
            ->cta('Complete profile')
            ->link('/client/onboarding')
            ->completeIf(fn (User $model) => $model->hasCompletedOnboarding());
    }
}
