<?php

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\DistributeSocialPostJob;
use App\Jobs\GenerateSocialPostsJob;
use App\Models\Blog;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use App\Services\AI\SocialPostPromptBuilder;
use App\Services\Social\SocialDistributionException;
use App\Services\Social\SocialDistributor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

// ---------------------------------------------------------------------------
// Generation
// ---------------------------------------------------------------------------

test('generating social posts drafts one pending-review post per platform', function () {
    $blog = Blog::factory()->published()->create();

    (new GenerateSocialPostsJob($blog->id))->handle($this->fake, app(SocialPostPromptBuilder::class));

    $posts = SocialPost::where('blog_id', $blog->id)->get();
    expect($posts)->toHaveCount(count(SocialPlatform::cases()))
        ->and($posts->pluck('status')->unique()->all())->toBe([SocialPostStatus::PendingReview])
        ->and($posts->whereNull('client_id')->count())->toBe($posts->count()); // agency channels
});

test('social generation is idempotent — a re-run does not duplicate drafts', function () {
    $blog = Blog::factory()->published()->create();
    $builder = app(SocialPostPromptBuilder::class);

    (new GenerateSocialPostsJob($blog->id))->handle($this->fake, $builder);
    (new GenerateSocialPostsJob($blog->id))->handle($this->fake, $builder);

    expect(SocialPost::where('blog_id', $blog->id)->count())->toBe(count(SocialPlatform::cases()));
});

// ---------------------------------------------------------------------------
// VA approval (Filament actions)
// ---------------------------------------------------------------------------

test('the VA approve action moves a draft into the distribution queue', function () {
    $staff = User::factory()->create(['role' => 'va']);
    $post = SocialPost::factory()->create(['status' => SocialPostStatus::PendingReview]);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\SocialPosts\Pages\ListSocialPosts::class)
        ->callTableAction('approve', $post, ['scheduled_for' => null]);

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Approved)
        ->and($post->scheduled_for)->not->toBeNull(); // defaults to now → distributed next run
});

test('the VA reject action marks the post rejected so it is never distributed', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $post = SocialPost::factory()->create(['status' => SocialPostStatus::PendingReview]);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\SocialPosts\Pages\ListSocialPosts::class)
        ->callTableAction('reject', $post);

    // Rejected is distinct from Failed (a distribution error) and is not due for publishing.
    expect($post->fresh()->status)->toBe(SocialPostStatus::Rejected)
        ->and(SocialPost::dueForPublishing()->whereKey($post->id)->exists())->toBeFalse();
});

test('an approved post with no schedule is still distributed (null scheduled_for is due now)', function () {
    $post = SocialPost::factory()->create(['status' => SocialPostStatus::Approved, 'scheduled_for' => null]);

    expect(SocialPost::dueForPublishing()->whereKey($post->id)->exists())->toBeTrue();
});

test('the retry action re-queues a failed post for distribution', function () {
    $staff = User::factory()->create(['role' => 'va']);
    $post = SocialPost::factory()->create(['status' => SocialPostStatus::Failed, 'error' => 'boom']);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\SocialPosts\Pages\ListSocialPosts::class)
        ->callTableAction('retry', $post);

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Approved)
        ->and($post->error)->toBeNull()
        ->and($post->scheduled_for)->not->toBeNull();
});

test('a non-SocialDistributionException failure still marks the post failed, never a silent published', function () {
    $this->app->bind(SocialDistributor::class, fn () => new class implements SocialDistributor
    {
        public function publish(SocialPost $post): string
        {
            throw new RuntimeException('unexpected driver blow-up');
        }
    });
    $post = SocialPost::factory()->approved()->create();

    (new DistributeSocialPostJob($post->id))->handle(app(SocialDistributor::class));

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Failed)
        ->and($post->error)->toContain('unexpected driver blow-up')
        ->and($post->published_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Distribution
// ---------------------------------------------------------------------------

test('the distribution cron dispatches jobs for due approved posts only', function () {
    Bus::fake([DistributeSocialPostJob::class]);
    $due = SocialPost::factory()->approved()->create();               // approved, scheduled in the past
    SocialPost::factory()->create(['status' => SocialPostStatus::PendingReview]); // not approved
    SocialPost::factory()->approved()->create(['scheduled_for' => now()->addDay()]); // future

    $this->artisan('social:distribute-due')->assertSuccessful();

    Bus::assertDispatchedTimes(DistributeSocialPostJob::class, 1);
    Bus::assertDispatched(DistributeSocialPostJob::class, fn ($job) => $job->postId === $due->id);
});

test('distribution fails closed when no platform driver is configured', function () {
    $post = SocialPost::factory()->approved()->create();

    // Default binding is NullSocialDistributor (fail-closed).
    (new DistributeSocialPostJob($post->id))->handle(app(SocialDistributor::class));

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Failed)
        ->and($post->error)->toContain('not configured')
        ->and($post->published_at)->toBeNull();
});

test('a configured distributor publishes the post and records the external id', function () {
    $this->app->bind(SocialDistributor::class, fn () => new class implements SocialDistributor
    {
        public function publish(SocialPost $post): string
        {
            return 'ext-'.$post->id;
        }
    });
    $post = SocialPost::factory()->approved()->create();

    (new DistributeSocialPostJob($post->id))->handle(app(SocialDistributor::class));

    $post->refresh();
    expect($post->status)->toBe(SocialPostStatus::Published)
        ->and($post->external_id)->toBe('ext-'.$post->id)
        ->and($post->published_at)->not->toBeNull();
});

test('distribution is at-most-once — a re-run never re-posts a published post', function () {
    $calls = 0;
    $this->app->bind(SocialDistributor::class, function () use (&$calls) {
        return new class($calls) implements SocialDistributor
        {
            public function __construct(public int &$calls) {}

            public function publish(SocialPost $post): string
            {
                $this->calls++;

                return 'ext';
            }
        };
    });
    $post = SocialPost::factory()->approved()->create();

    (new DistributeSocialPostJob($post->id))->handle(app(SocialDistributor::class));
    (new DistributeSocialPostJob($post->id))->handle(app(SocialDistributor::class)); // re-delivery

    expect($post->fresh()->status)->toBe(SocialPostStatus::Published)
        ->and($calls)->toBe(1); // claimed once; the second run publishes nothing
});

test('staff can render the social posts resource', function () {
    $staff = User::factory()->create(['role' => 'va']);
    SocialPost::factory()->count(2)->create();

    $this->actingAs($staff)->get(route('filament.admin.resources.social-posts.index'))->assertOk();
});
