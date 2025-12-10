<?php

namespace App\Exceptions;

use Exception;

class InvalidTransferException extends Exception
{
    protected $message = 'Invalid transfer operation';
    protected $code = 400;
}
