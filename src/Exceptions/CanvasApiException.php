<?php

namespace CanvasLMS\Exceptions;

use Exception;

class CanvasApiException extends Exception
{
    protected array $errors = [];

    /**
     * CanvasApiException constructor.
     * @param string $message
     * @param int $code
     * @param mixed[] $errors
     */
    public function __construct(string $message = '', int $code = 0, array $errors = [])
    {
        parent::__construct($message, $code);

        $this->errors = $errors;
    }

    /**
     * Get the errors
     * @return mixed[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
