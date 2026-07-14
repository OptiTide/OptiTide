<?php

use App\Models\CmsPage;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CmsPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Model: translatable + slug + sanitising
// ---------------------------------------------------------------------------

test('CmsPage title/body are translatable with fallback to the default locale', function () {
    $page = CmsPage::create(['title' => 'Privacy', 'body' => '<p>English body</p>', 'status' => 'published']);
    $page->setTranslation('title', 'fr', 'Confidentialité');
    $page->setTranslation('body', 'fr', '<p>Corps français</p>');
    $page->save();

    App::setLocale('fr');
    expect($page->fresh()->title)->toBe('Confidentialité');

    App::setLocale('de'); // no German → fallback to en
    expect($page->fresh()->title)->toBe('Privacy');

    App::setLocale('en');
});

test('duplicate titles get distinct unique slugs', function () {
    $a = CmsPage::create(['title' => 'About Us']);
    $b = CmsPage::create(['title' => 'About Us']);

    expect($a->slug)->toBe('about-us')->and($b->slug)->toBe('about-us-2');
});

test('safeBody sanitises the page body', function () {
    $page = new CmsPage(['body' => '<h2>Ok</h2><script>alert(1)</script><a href="javascript:x()">bad</a>']);
    $safe = $page->safeBody();

    expect($safe)->toContain('<h2>Ok</h2>')
        ->and($safe)->not->toContain('<script')
        ->and($safe)->not->toContain('javascript:');
});

// ---------------------------------------------------------------------------
// Public rendering + routing precedence
// ---------------------------------------------------------------------------

test('a published page renders at its slug with SEO meta', function () {
    $page = CmsPage::create([
        'title' => 'Our Story',
        'body' => '<p>Founded in Australia.</p>',
        'status' => 'published',
        'meta' => ['meta_description' => 'The OptiTide story.'],
    ]);

    $this->get(route('cms.show', $page->slug))
        ->assertOk()
        ->assertSee('Our Story')
        ->assertSee('Founded in Australia', false)
        ->assertSee('The OptiTide story.', false); // meta description
});

test('a draft page 404s', function () {
    $page = CmsPage::create(['title' => 'Secret Draft', 'status' => 'draft']);

    $this->get(route('cms.show', $page->slug))->assertNotFound();
});

test('an unknown slug 404s', function () {
    $this->get('/no-such-page')->assertNotFound();
});

test('the root catch-all does not shadow named or panel routes', function () {
    // These specific routes must still win over /{page:slug}.
    $this->get(route('blog.index'))->assertOk();
    $this->get(route('services.index'))->assertOk();
    $this->get(route('seo-audit.show'))->assertOk();
    $this->get('/admin')->assertRedirect(); // Filament login redirect, not a CMS 404
});

test('seeded legal pages render and appear in the storefront footer', function () {
    $this->seed(CmsPageSeeder::class);

    $this->get(route('cms.show', 'refund-policy'))
        ->assertOk()
        ->assertSee('No refunds for change of mind', false);

    // Footer links to the published footer pages appear on the storefront.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('cms.show', 'privacy'), false)
        ->assertSee(route('cms.show', 'terms'), false);
});

test('the lang query switches locale for translatable pages', function () {
    $page = CmsPage::create(['title' => 'Hello', 'body' => '<p>Hi</p>', 'status' => 'published']);
    $page->setTranslation('title', 'fr', 'Bonjour')->save();

    $this->get(route('cms.show', $page->slug).'?lang=fr')
        ->assertOk()
        ->assertSee('Bonjour');
});

// ---------------------------------------------------------------------------
// Analytics injection — the security property
// ---------------------------------------------------------------------------

test('a valid GA4 id renders the official gtag snippet on public pages', function () {
    Setting::put('ga4_measurement_id', 'G-ABC12345');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('googletagmanager.com/gtag/js?id=G-ABC12345', false);
});

test('a poisoned analytics id is dropped at render (never injected)', function () {
    // Simulate a value that bypassed the form (e.g. direct DB write).
    Setting::put('ga4_measurement_id', 'G-x"></script><script>alert(document.domain)</script>');

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('alert(document.domain)')
        ->and($html)->not->toContain('gtag/js?id=G-x'); // the malformed id never renders
});

test('no analytics scripts render when nothing is configured', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag', false)
        ->assertDontSee('connect.facebook.net', false);
});

// ---------------------------------------------------------------------------
// Admin: resource + settings page
// ---------------------------------------------------------------------------

test('staff can render the CMS pages resource and the analytics settings page', function () {
    $staff = User::factory()->create(['role' => 'admin']);

    $this->actingAs($staff)->get(route('filament.admin.resources.cms-pages.index'))->assertOk();
    $this->actingAs($staff)->get(route('filament.admin.pages.manage-analytics'))->assertOk();
});

test('the CMS page edit screen renders and preserves other-locale translations on save', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $page = CmsPage::create(['title' => 'About Us', 'body' => '<p>Hello</p>', 'status' => 'published']);
    $page->setTranslation('title', 'fr', 'À propos')->save();
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\CmsPages\Pages\EditCmsPage::class, ['record' => $page->getRouteKey()])
        ->assertOk() // the translatable-array-into-RichEditor 500 is fixed
        ->fillForm(['title' => 'About Our Team'])
        ->call('save')
        ->assertHasNoFormErrors();

    $page->refresh();
    expect($page->getTranslation('title', 'en'))->toBe('About Our Team')
        ->and($page->getTranslation('title', 'fr'))->toBe('À propos'); // preserved
});

test('the slug field rejects reserved and malformed slugs', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(\App\Filament\Resources\CmsPages\Pages\CreateCmsPage::class)
        ->fillForm(['title' => 'X', 'slug' => 'blog', 'status' => 'draft'])
        ->call('create')
        ->assertHasFormErrors(['slug']); // reserved (collides with /blog)

    Livewire\Livewire::test(\App\Filament\Resources\CmsPages\Pages\CreateCmsPage::class)
        ->fillForm(['title' => 'Y', 'slug' => 'About_Us', 'status' => 'draft'])
        ->call('create')
        ->assertHasFormErrors(['slug']); // uppercase/underscore is unreachable
});

test('a page with a leading-digit slug is reachable', function () {
    $page = CmsPage::create(['title' => '2024 Report', 'slug' => '2024-report', 'body' => '<p>x</p>', 'status' => 'published']);

    $this->get(route('cms.show', $page->slug))->assertOk()->assertSee('2024 Report');
});

test('the analytics settings page rejects malformed ids and persists valid ones', function () {
    $staff = User::factory()->create(['role' => 'admin']);
    $this->actingAs($staff);
    Filament\Facades\Filament::setCurrentPanel('admin');

    // Malformed → validation error, nothing stored.
    Livewire\Livewire::test(\App\Filament\Pages\ManageAnalytics::class)
        ->set('ga4', 'G-x"><script>alert(1)</script>')
        ->call('save')
        ->assertHasErrors('ga4');

    expect(Setting::get('ga4_measurement_id'))->toBeNull();

    // Valid → persisted.
    Livewire\Livewire::test(\App\Filament\Pages\ManageAnalytics::class)
        ->set('ga4', 'G-ABC12345')
        ->set('pixel', '123456789012')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('ga4_measurement_id'))->toBe('G-ABC12345')
        ->and(Setting::get('meta_pixel_id'))->toBe('123456789012');
});
