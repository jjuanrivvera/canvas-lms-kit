<?php

namespace CanvasLMS\Http\Middleware;

/**
 * Interface for HTTP client middleware
 */
interface MiddlewareInterface
{
    /**
     * Get the name of the middleware
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the Guzzle middleware callable
     *
     * @return callable
     */
    public function __invoke(): callable;

    /**
     * Set configuration for the middleware
     *
     * @param array<string, mixed> $config
     * @return void
     */
    public function configure(array $config): void;
}
