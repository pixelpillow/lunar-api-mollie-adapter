<?php

namespace Pixelpillow\LunarApiMollieAdapter\Exceptions;

use Exception;

class MissingMetadataException extends Exception
{
    protected array $response_error;

    public function __construct(string $message)
    {
        $this->response_error['message'] = $message;
        parent::__construct($message);
    }
}
