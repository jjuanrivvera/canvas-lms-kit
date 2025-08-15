<?php

declare(strict_types=1);

namespace CanvasLMS\Exceptions;

/**
 * Exception thrown when an OAuth token is required but not configured
 */
class MissingOAuthTokenException extends CanvasApiException
{
    public function __construct(string $message = '')
    {
        $defaultMessage = 'OAuth token not configured. Please authenticate first using ' .
            'OAuth::exchangeCode() or Config::setOAuthToken()';
        parent::__construct($message ?: $defaultMessage);
    }
}
