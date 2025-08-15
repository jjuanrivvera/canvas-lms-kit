<?php

declare(strict_types=1);

namespace CanvasLMS\Exceptions;

/**
 * Exception thrown when an OAuth token has expired and cannot be refreshed
 */
class OAuthTokenExpiredException extends CanvasApiException
{
    public function __construct(string $message = '')
    {
        $defaultMessage = 'OAuth token has expired and could not be refreshed. Please re-authenticate.';
        parent::__construct($message ?: $defaultMessage);
    }
}
