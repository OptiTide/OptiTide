<?php

namespace App\Core\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public array $errors, string $message = 'The given data was invalid.')
    {
        parent::__construct($message);
    }
}
