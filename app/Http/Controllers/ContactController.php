<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('storefront.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $successMessage = fn (string $name) => "Thanks {$name} — we've received your enquiry and will be in touch within one business day.";

        // Honeypot: hidden from humans. Bots that fill it get a fake success
        // and no lead is created, so they don't learn they were filtered.
        if ($request->filled('company_website')) {
            return redirect()
                ->route('contact.show')
                ->with('success', $successMessage($request->input('name', 'there')));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        Lead::create([
            ...$data,
            'source' => 'contact_form',
        ]);

        return redirect()
            ->route('contact.show')
            ->with('success', $successMessage($data['name']));
    }
}
