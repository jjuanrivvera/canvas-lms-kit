<?php

declare(strict_types=1);

namespace CanvasLMS\Exceptions;

/**
 * Exception thrown when OAuth token refresh fails
 */
class OAuthRefreshFailedException extends CanvasApiException
{
    public function __construct(string $message = '')
    {
        $defaultMessage = 'Failed to refresh OAuth token';
        parent::__construct($message ?: $defaultMessage);
    }
}
