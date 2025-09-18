<?php

declare(strict_types=1);

namespace CanvasLMS\Exceptions;

use Exception;

class MissingApiKeyException extends Exception
{
    public function __construct()
    {
        parent::__construct('Missing API key. Please set your API key using the Config::setAppKey() method.');
    }
}
