<?php

namespace CanvasLMS\Exceptions;

use Exception;

class MissingBaseUrlException extends Exception
{
    public function __construct()
    {
        parent::__construct('Missing base URL. Please set your base URL using the Config::setBaseUrl() method.');
    }
}