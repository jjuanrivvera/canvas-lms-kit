<?php

declare(strict_types=1);

namespace Tests\Support;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for API resource tests.
 *
 * Provides the mock HTTP client + Config setup that resource tests
 * otherwise duplicate, and guarantees the shared client registry is
 * reset in teardown so mocks never leak between tests.
 */
abstract class ApiTestCase extends TestCase
{
    protected HttpClientInterface&MockObject $httpClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        AbstractBaseApi::setApiClient($this->httpClientMock);

        Config::setBaseUrl('https://canvas.example.com/');
        Config::setApiKey('test-api-key');
        Config::setAccountId(1);
    }

    protected function tearDown(): void
    {
        AbstractBaseApi::resetApiClients();
        parent::tearDown();
    }
}
