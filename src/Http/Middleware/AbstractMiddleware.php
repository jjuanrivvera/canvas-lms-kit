<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

/**
 * Base class for HTTP client middleware
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * @inheritDoc
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->getDefaultConfig(), $this->config, $config);
    }

    /**
     * Get default configuration for the middleware
     *
     * @return array<string, mixed>
     */
    protected function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * Get a configuration value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
