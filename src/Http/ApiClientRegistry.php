<?php

declare(strict_types=1);

namespace CanvasLMS\Http;

use CanvasLMS\Config;
use CanvasLMS\Http\Middleware\LoggingMiddleware;
use CanvasLMS\Http\Middleware\RateLimitMiddleware;
use CanvasLMS\Http\Middleware\RetryMiddleware;
use CanvasLMS\Interfaces\HttpClientInterface;

/**
 * Central registry for the HTTP clients used by API resource classes.
 *
 * All resources share one lazily-created, middleware-configured default
 * client. Individual classes can be given their own client without
 * affecting the rest (test isolation, custom transports).
 */
final class ApiClientRegistry
{
    /**
     * @var array<class-string, HttpClientInterface> Per-class client overrides
     */
    private static array $overrides = [];

    /**
     * @var HttpClientInterface|null Shared default client
     */
    private static ?HttpClientInterface $default = null;

    /**
     * Set the shared default client used by every API class without an override.
     *
     * @param HttpClientInterface $client The HTTP client
     *
     * @return void
     */
    public static function setDefault(HttpClientInterface $client): void
    {
        self::$default = $client;
    }

    /**
     * Set a client for one specific class only.
     *
     * @param class-string $class The API class to scope the client to
     * @param HttpClientInterface $client The HTTP client
     *
     * @return void
     */
    public static function setFor(string $class, HttpClientInterface $client): void
    {
        self::$overrides[$class] = $client;
    }

    /**
     * Remove the override for one specific class.
     *
     * @param class-string $class The API class
     *
     * @return void
     */
    public static function removeFor(string $class): void
    {
        unset(self::$overrides[$class]);
    }

    /**
     * Resolve the client for a class: its override if set, otherwise the
     * shared default (created from Config middleware settings on first use).
     *
     * @param class-string $class The API class requesting a client
     *
     * @return HttpClientInterface
     */
    public static function resolve(string $class): HttpClientInterface
    {
        if (isset(self::$overrides[$class])) {
            return self::$overrides[$class];
        }

        if (self::$default === null) {
            self::$default = self::createConfiguredHttpClient();
        }

        return self::$default;
    }

    /**
     * Clear the default client and all per-class overrides.
     *
     * Intended for test teardown so a mock set in one test never leaks
     * into the next.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$overrides = [];
        self::$default = null;
    }

    /**
     * Create an HttpClient with middleware built from Config settings.
     *
     * @return HttpClient
     */
    public static function createConfiguredHttpClient(): HttpClient
    {
        $middlewareConfig = Config::getMiddleware();
        $middleware = [];
        $logger = null;

        // Check if logging is configured
        if (isset($middlewareConfig['logging']) && $middlewareConfig['logging']['enabled'] !== false) {
            // Use the configured logger from Config, defaults to NullLogger if not configured
            $logger = Config::getLogger();
        }

        // If middleware config is empty, HttpClient will use defaults
        if (!empty($middlewareConfig)) {
            // Build middleware instances from configuration
            if (isset($middlewareConfig['retry'])) {
                $middleware[] = new RetryMiddleware($middlewareConfig['retry']);
            }

            if (isset($middlewareConfig['rate_limit'])) {
                $middleware[] = new RateLimitMiddleware($middlewareConfig['rate_limit']);
            }

            if (isset($middlewareConfig['logging']) && $logger !== null) {
                $loggingConfig = $middlewareConfig['logging'];
                unset($loggingConfig['enabled']); // Remove the enabled flag
                $middleware[] = new LoggingMiddleware($logger, $loggingConfig);
            }
        }

        return new HttpClient(null, $logger, $middleware);
    }
}
