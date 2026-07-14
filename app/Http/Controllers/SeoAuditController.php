<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSeoAuditJob;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoAuditController extends Controller
{
    public function show(): View
    {
        return view('storefront.seo-audit');
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: bots fill the hidden field. Fake success, capture nothing.
        if (filled($request->input('company_website'))) {
            return back()->with('success', 'Thanks! Your SEO audit is on its way.');
        }

        // url:http,https rejects non-web schemes before the fetcher's SSRF guard.
        $data = $request->validate([
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $message = 'Thanks! Your SEO audit is being generated and will land in your inbox shortly.';

        // Per-email cooldown: one audit email per address per day. Stops the
        // public form being used to mailbomb a third party (reflected spam),
        // regardless of source IP. We respond identically either way.
        $recentlyAudited = Lead::where('email', $data['email'])
            ->where('source', 'seo_audit')
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($recentlyAudited) {
            return back()->with('success', $message);
        }

        $lead = Lead::create([
            'email' => $data['email'],
            'website_url' => $data['website_url'],
            'source' => 'seo_audit',
            'status' => 'new',
        ]);

        GenerateSeoAuditJob::dispatch($lead->id);

        return back()->with('success', $message);
    }
}
