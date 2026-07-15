<?php

namespace App\Core\Exceptions;

class ModelNotFoundException extends HttpException
{
    public function __construct(string $message = 'Record not found.')
    {
        parent::__construct(404, $message);
    }
}
