<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Доступ запрещён.')
    {
        parent::__construct(403, $message);
    }
}
