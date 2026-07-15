<?php

namespace App\Services\Mail;

interface Mailer
{
    public function send(MailMessage $message): bool;
}
