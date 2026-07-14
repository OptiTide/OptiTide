<?php

namespace App\Filament\Client\Auth;

use App\Services\ReferralService;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    /** Attach the referral (from the first-touch ?ref cookie) to the new user. */
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        app(ReferralService::class)->attachReferral($user, request()->cookie('referral'));

        return $user;
    }
}
