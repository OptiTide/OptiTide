<?php

namespace App\Core\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(public int $status = 500, string $message = '')
    {
        parent::__construct($message);
    }
}
