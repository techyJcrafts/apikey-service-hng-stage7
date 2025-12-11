<?php

namespace App\Exceptions;

use Exception;

class TooManyApiKeysException extends Exception
{
    protected $message = 'Maximum 5 active API keys allowed';
    protected $code = 400;
}
