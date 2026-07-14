<?php

use App\Enums\BlogStatus;
use App\Jobs\GenerateBlogJob;
use App\Jobs\GenerateSocialPostsJob;
use App\Models\Blog;
use App\Models\User;
use App\Services\AI\BlogPromptBuilder;
use App\Services\AI\ClaudeClient;
use App\Services\AI\FakeClaudeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fake = new FakeClaudeClient;
    $this->app->instance(ClaudeClient::class, $this->fake);
});

function generateBlog(Blog $blog): void
{
    (new GenerateBlogJob($blog->id))->handle(test()->fake, app(BlogPromptBuilder::class));
}

// ---------------------------------------------------------------------------
// AI generation
// ---------------------------------------------------------------------------

test('generating a blog fills the draft and moves it to pending review', function () {
    $blog = Blog::factory()->create(['status' => BlogStatus::Draft, 'meta' => ['topic' => 'SEO basics', 'focus_keywords' => ['seo']]]);

    generateBlog($blog);

    $blog->refresh();
    expect($blog->status)->toBe(BlogStatus::PendingReview)
        ->and($blog->is_ai_generated)->toBeTrue()
        ->and($blog->body)->not->toBeEmpty()
        ->and($blog->metaDescription())->not->toBeEmpty()
        ->and($this->fake->lastSystem)->toContain('blog article');
});

test('a generation failure records the error and leaves the blog a draft', function () {
    $this->fake->shouldThrow = true;
    $blog = Blog::factory()->create(['status' => BlogStatus::Draft, 'meta' => ['topic' => 'X']]);

    generateBlog($blog);

    $blog->refresh();
    expect($blog->status)->toBe(BlogStatus::Draft)
        ->and($blog->meta['generation_error'] ?? null)->not->toBeNull();
});

test('malformed AI JSON is rejected rather than published', function () {
    $this->fake->nextResponse = 'This is not JSON at all.';
    $blog = Blog::factory()->create(['status' => BlogStatus::Draft, 'meta' => ['topic' => 'X']]);

    generateBlog($blog);

    expect($blog->fresh()->status)->toBe(BlogStatus::Draft);
});

test('duplicate titles get distinct unique slugs instead of a constraint violation', function () {
    $a = Blog::create(['title' => 'Same Title']);
    $b = Blog::create(['title' => 'Same Title']);
    $c = Blog::create(['title' => 'Same Title']);

    expect($a->slug)->toBe('same-title')
        ->and($b->slug)->toBe('same-title-2')
        ->and($c->slug)->toBe('same-title-3');
});

test('generation re-slugs from the AI article title, not the raw topic prompt', function () {
    $blog = Blog::create(['title' => 'raw topic prompt', 'status' => BlogStatus::Draft, 'meta' => ['topic' => 'raw topic prompt']]);
    expect($blog->slug)->toBe('raw-topic-prompt');

    generateBlog($blog); // fake returns title "Placeholder Blog Article"

    expect($blog->fresh()->slug)->toBe('placeholder-blog-article');
});

// ---------------------------------------------------------------------------
// Publish cron
// ---------------------------------------------------------------------------

test('the publish cron publishes due scheduled blogs and queues promo social posts', function () {
    Bus::fake([GenerateSocialPostsJob::class]);
    $due = Blog::factory()->dueForPublishing()->create();
    $future = Blog::factory()->scheduled()->create();
    $draft = Blog::factory()->create();

    $this->artisan('blogs:publish-due')->assertSuccessful();

    expect($due->fresh()->status)->toBe(BlogStatus::Published)
        ->and($due->fresh()->published_at)->not->toBeNull()
        ->and($future->fresh()->status)->toBe(BlogStatus::Scheduled) // not yet due
        ->and($draft->fresh()->status)->toBe(BlogStatus::Draft);

    Bus::assertDispatched(GenerateSocialPostsJob::class, fn ($job) => $job->blogId === $due->id);
});

// ---------------------------------------------------------------------------
// Public rendering + SEO
// ---------------------------------------------------------------------------

test('the public blog index lists only published articles', function () {
    $published = Blog::factory()->published()->create(['title' => 'Live Article']);
    Blog::factory()->create(['title' => 'Draft Article']); // draft

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Live Article')
        ->assertDontSee('Draft Article');
});

test('a published article renders with SEO meta and JSON-LD', function () {
    $blog = Blog::factory()->published()->create([
        'title' => 'Ranking Higher',
        'meta' => ['meta_title' => 'Ranking Higher | OptiTide', 'meta_description' => 'How to rank higher in search.', 'focus_keywords' => ['seo']],
    ]);

    $response = $this->get(route('blog.show', $blog))->assertOk();

    $response->assertSee('Ranking Higher | OptiTide', false)        // <title> / og:title
        ->assertSee('How to rank higher in search.', false)         // meta description
        ->assertSee('rel="canonical"', false)
        ->assertSee('og:type', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('BlogPosting', false);
});

test('an unpublished article 404s on the public route', function () {
    $draft = Blog::factory()->create();
    $scheduled = Blog::factory()->scheduled()->create();

    $this->get(route('blog.show', $draft))->assertNotFound();
    $this->get(route('blog.show', $scheduled))->assertNotFound();
});

test('safeBody (DOM allow-list) removes disallowed tags, attributes and unsafe schemes', function () {
    $blog = new Blog([
        'body' => '<p>Hi</p><script>alert(1)</script><style>x{}</style>'
            .'<a href="javascript:evil()" onclick="steal()">link</a><h2 onload="x">Head</h2>'
            .'<div><img src=x onerror="alert(1)"></div>',
    ]);

    $safe = $blog->safeBody();

    expect($safe)->toContain('Hi')
        ->and($safe)->toContain('Head')
        ->and($safe)->not->toContain('<script')
        ->and($safe)->not->toContain('alert(1)')
        ->and($safe)->not->toContain('<style')
        ->and($safe)->not->toContain('<img')
        ->and($safe)->not->toContain('onclick')
        ->and($safe)->not->toContain('onload')
        ->and($safe)->not->toContain('onerror')
        ->and($safe)->not->toContain('javascript:');
});

test('safeBody defeats the classic regex-sanitiser bypasses', function () {
    // (1) entity-encoded scheme, (2) slash-separated on* handler, (3) data: URI.
    $blog = new Blog([
        'body' => '<a href="jav&#x09;ascript:alert(document.domain)">a</a>'
            .'<a href="x"/onmouseover="alert(1)">b</a>'
            .'<a href="data:text/html,<script>alert(1)</script>">c</a>',
    ]);

    $safe = strtolower($blog->safeBody());

    expect($safe)->not->toContain('javascript')   // entity-encoded scheme neutralised
        ->and($safe)->not->toContain('onmouseover') // slash-separated handler stripped
        ->and($safe)->not->toContain('data:')       // data: scheme rejected
        ->and($safe)->not->toContain('alert');
});

test('the JSON-LD block cannot be broken out of by a crafted title', function () {
    $blog = Blog::factory()->published()->create([
        'title' => '</script><script>alert(document.domain)</script>',
        'meta' => ['meta_title' => 'Safe Title', 'meta_description' => 'ok'],
    ]);

    $html = $this->get(route('blog.show', $blog))->getContent();

    // No unescaped </script> breakout from the title, and the ld+json block
    // escaped the angle brackets (JSON_HEX_TAG -> <).
    expect($html)->not->toContain('<script>alert(document.domain)</script>')
        ->and($html)->not->toContain('script>alert(document.domain)'); // no ld+json breakout
});

test('the sitemap and robots endpoints serve published articles and the sitemap link', function () {
    $blog = Blog::factory()->published()->create();

    $sitemap = $this->get(route('sitemap'));
    $sitemap->assertOk();
    expect($sitemap->headers->get('content-type'))->toContain('application/xml');
    $sitemap->assertSee(route('blog.show', $blog), false)
        ->assertSee('<urlset', false);

    $robots = $this->get(route('robots'))->assertOk();
    $robots->assertSee('Sitemap: '.url('/sitemap.xml'), false)
        ->assertSee('Disallow: /admin', false);
});

// ---------------------------------------------------------------------------
// Admin generate action
// ---------------------------------------------------------------------------

test('the admin Generate action creates a stub and queues generation', function () {
    Bus::fake([GenerateBlogJob::class]);
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\Blogs\Pages\ListBlogs::class)
        ->callAction('generateWithAi', ['topic' => 'Local SEO for cafes', 'keywords' => 'local seo, cafe']);

    $blog = Blog::first();
    expect($blog)->not->toBeNull()
        ->and($blog->meta['topic'])->toBe('Local SEO for cafes')
        ->and($blog->meta['focus_keywords'])->toBe(['local seo', 'cafe'])
        ->and($blog->is_ai_generated)->toBeTrue();

    Bus::assertDispatched(GenerateBlogJob::class, fn ($job) => $job->blogId === $blog->id);
});
