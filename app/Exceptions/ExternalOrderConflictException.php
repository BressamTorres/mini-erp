<?php

namespace App\Exceptions;

use RuntimeException;

class ExternalOrderConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O identificador externo já está vinculado a um pedido diferente.');
    }
}
