<?php

namespace App\Exceptions;

use Exception;

class DuplicateTransactionException extends Exception
{
    protected $message = 'Transaction already processed';
    protected $code = 409;
}
