<?php

namespace Pixelpillow\LunarMollie\Exceptions;

use Exception;

class InvalidRequestException extends Exception
{
    protected array $response_error;

    public function __construct(array $responseError)
    {
        $this->response_error = $responseError;

        parent::__construct($responseError['message']);
    }
}
