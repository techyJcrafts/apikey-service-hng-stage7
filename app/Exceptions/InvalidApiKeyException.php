<?php

namespace App\Exceptions;

use Exception;

class InvalidApiKeyException extends Exception
{
    protected $message = 'Invalid or expired API key';
    protected $code = 401;
}
