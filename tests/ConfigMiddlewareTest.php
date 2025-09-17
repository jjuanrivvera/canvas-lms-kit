<?php

declare(strict_types=1);

namespace CanvasLMS\Tests;

use CanvasLMS\Config;
use PHPUnit\Framework\TestCase;

class ConfigMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset all contexts before each test
        foreach (Config::getAllContexts() as $context) {
            Config::resetContext($context);
        }
        Config::setContext('default');
    }

    public function testSetAndGetMiddleware()
    {
        $middlewareConfig = [
            'retry' => [
                'max_attempts' => 5,
                'delay' => 2000,
            ],
            'rate_limit' => [
                'enabled' => true,
                'wait_on_limit' => false,
            ],
            'logging' => [
                'enabled' => true,
                'log_level' => 'debug',
            ],
        ];

        Config::setMiddleware($middlewareConfig);

        $retrieved = Config::getMiddleware();
        $this->assertEquals($middlewareConfig, $retrieved);
    }

    public function testMiddlewarePerContext()
    {
        // Set different middleware for different contexts
        $productionMiddleware = [
            'retry' => ['max_attempts' => 3],
            'rate_limit' => ['wait_on_limit' => true],
        ];

        $testMiddleware = [
            'retry' => ['max_attempts' => 1],
            'rate_limit' => ['enabled' => false],
            'logging' => ['enabled' => true],
        ];

        Config::setMiddleware($productionMiddleware, 'production');
        Config::setMiddleware($testMiddleware, 'test');

        // Check production context
        Config::setContext('production');
        $this->assertEquals($productionMiddleware, Config::getMiddleware());

        // Check test context
        Config::setContext('test');
        $this->assertEquals($testMiddleware, Config::getMiddleware());
    }

    public function testEmptyMiddlewareConfig()
    {
        // Should return empty array when no middleware is configured
        $this->assertEquals([], Config::getMiddleware());
    }

    public function testPartialMiddlewareConfig()
    {
        // Test with only some middleware configured
        $partialConfig = [
            'retry' => ['max_attempts' => 2],
        ];

        Config::setMiddleware($partialConfig);
        $this->assertEquals($partialConfig, Config::getMiddleware());
    }
}
