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
        $backToContact = fn () => $this->redirect(route('home') . '#contact');

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
        ]);

        RateLimiter::hit($key, 3600);

        Mail::to(config('company.email'), config('company.legal_name'))
            ->replyTo($data['email'])
            ->subject('New enquiry from ' . $data['name'] . ' — OptiTide')
            ->view('emails.contact', [
                'data' => $data,
                'ip'   => $request->ip(),
            ])
            ->send();

        Session::flash('success', 'Thanks ' . $data['name'] . ' — your enquiry has been sent. We\'ll be in touch soon.');

        return $backToContact();
    }
}
