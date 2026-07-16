<?php

namespace App\Controllers\PublicSite;

use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Mail\Mail;

class ContactController extends Controller
{
    public function submit(Request $request): Response
    {
        // Return to the page the form was posted from (only ever a local path),
        // so the dedicated /contact page doesn't bounce you back to the homepage.
        $return = (string) $request->input('return', '');
        if ($return === '' || $return[0] !== '/' || str_starts_with($return, '//')) {
            $return = route('home') . '#contact';
        }
        $backToContact = fn () => $this->redirect($return);

        // Honeypot — a bot filling the hidden "website" field gets a silent OK.
        if ($request->filled('website')) {
            Session::flash('success', 'Thanks — your enquiry has been sent. We\'ll be in touch soon.');

            return $backToContact();
        }

        $key = 'contact:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Session::flash('error', 'You have sent a few enquiries already — please try again later or email us directly.');

            return $backToContact();
        }

        $data = $this->validate($request, [
            'name'    => 'required|max:120',
            'email'   => 'required|email|max:180',
            'phone'   => 'nullable|max:40',
            'service' => 'nullable|max:60',
            'message' => 'required|max:4000',
            'captcha' => 'required',
        ]);

        // In-house captcha — verified after field validation so a field error
        // never silently consumes the challenge.
        if (! \App\Support\Captcha::verify($request->input('captcha'))) {
            Session::flash('error', 'The quick-check answer was incorrect — please try again.');

            return $backToContact();
        }

        RateLimiter::hit($key, 3600);

        Mail::to(config('company.email'), config('company.legal_name'))
            ->replyTo($data['email'])
            ->subject('New enquiry from ' . $data['name'] . ' — ' . config('company.brand_name'))
            ->view('emails.contact', [
                'data' => $data,
                'ip'   => $request->ip(),
            ])
            ->send();

        // Auto-reply to the person who enquired.
        Mail::to($data['email'], $data['name'])
            ->subject('We received your enquiry — ' . config('company.brand_name'))
            ->view('emails.contact-received', [
                'name'    => $data['name'],
                'service' => $data['service'] ?? '',
                'message' => $data['message'],
            ])
            ->send();

        Session::flash('success', 'Thanks ' . $data['name'] . ' — your enquiry has been sent. We\'ll be in touch soon.');

        return $backToContact();
    }

    /** Hero "Get Your Free Proposal" lead form — honeypot-protected, no captcha. */
    public function proposal(Request $request): Response
    {
        $back = fn () => $this->redirect(route('home') . '#proposal');

        if ($request->filled('website')) {
            Session::flash('success', 'Thanks — we\'ve received your request and will be in touch soon.');

            return $back();
        }

        $key = 'proposal:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            Session::flash('error', 'You\'ve already sent a few requests — please email us directly.');

            return $back();
        }

        $data = $this->validate($request, [
            'name'          => 'required|max:120',
            'email'         => 'required|email|max:180',
            'phone'         => 'nullable|max:40',
            'business_type' => 'nullable|max:80',
            'service'       => 'nullable|max:80',
            'message'       => 'nullable|max:4000',
        ]);

        RateLimiter::hit($key, 3600);

        $message = trim(($data['message'] ?? '') . "\n\nBusiness type: " . ($data['business_type'] ?: '—'));
        $forTeam = array_merge($data, ['message' => $message ?: 'Free-proposal request (no details provided).']);

        Mail::to(config('company.email'), config('company.legal_name'))
            ->replyTo($data['email'])
            ->subject('New free-proposal request from ' . $data['name'] . ' — ' . config('company.brand_name'))
            ->view('emails.contact', ['data' => $forTeam, 'ip' => $request->ip()])
            ->send();

        Mail::to($data['email'], $data['name'])
            ->subject('We received your request — ' . config('company.brand_name'))
            ->view('emails.contact-received', ['name' => $data['name'], 'service' => $data['service'] ?? '', 'message' => $data['message'] ?? ''])
            ->send();

        Session::flash('success', 'Thanks ' . $data['name'] . ' — we\'ll send your free proposal within 24 hours.');

        return $back();
    }
}
