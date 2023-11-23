<?php

namespace Pixelpillow\LunarApiMollieAdapter\Exceptions;

use Exception;

/**
 * Exception thrown when the payment issuer is missing on the cart
 */
class InvalidConfigurationException extends Exception
{
    protected array $response_error;

    public function __construct(string $message)
    {
        $this->response_error['message'] = $message;
        parent::__construct($message);
    }
}
