<?php

namespace CanvasLMS\Tests;

use CanvasLMS\Config;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Exceptions\ConfigurationException;

class ConfigTest extends TestCase
{
    /**
     * @var string
     */
    private string $originalContext;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Save original context
        $this->originalContext = Config::getContext();
        
        // Reset to default context for each test
        Config::setContext('default');
        Config::resetContext('default');
    }

    protected function tearDown(): void
    {
        // Clean up any test contexts
        foreach (Config::getAllContexts() as $context) {
            if ($context !== 'default') {
                Config::resetContext($context);
            }
        }
        
        // Restore original context
        Config::setContext($this->originalContext);
        
        parent::tearDown();
    }

    public function testBackwardCompatibility(): void
    {
        // Test that existing usage patterns continue to work
        Config::setApiKey('test-key');
        Config::setBaseUrl('https://canvas.example.com');
        Config::setAccountId(123);
        Config::setApiVersion('v2');
        Config::setTimeout(60);

        $this->assertEquals('test-key', Config::getApiKey());
        $this->assertEquals('https://canvas.example.com/', Config::getBaseUrl());
        $this->assertEquals(123, Config::getAccountId());
        $this->assertEquals('v2', Config::getApiVersion());
        $this->assertEquals(60, Config::getTimeout());
    }

    public function testContextSwitching(): void
    {
        // Set up first context
        Config::setContext('tenant1');
        Config::setApiKey('tenant1-key');
        Config::setBaseUrl('https://tenant1.canvas.com');
        Config::setAccountId(100);

        // Set up second context
        Config::setContext('tenant2');
        Config::setApiKey('tenant2-key');
        Config::setBaseUrl('https://tenant2.canvas.com');
        Config::setAccountId(200);

        // Verify context isolation
        Config::setContext('tenant1');
        $this->assertEquals('tenant1-key', Config::getApiKey());
        $this->assertEquals('https://tenant1.canvas.com/', Config::getBaseUrl());
        $this->assertEquals(100, Config::getAccountId());

        Config::setContext('tenant2');
        $this->assertEquals('tenant2-key', Config::getApiKey());
        $this->assertEquals('https://tenant2.canvas.com/', Config::getBaseUrl());
        $this->assertEquals(200, Config::getAccountId());
    }

    public function testContextSpecificGetters(): void
    {
        // Set up multiple contexts
        Config::setApiKey('context1-key', 'context1');
        Config::setApiKey('context2-key', 'context2');

        // Test context-specific retrieval
        $this->assertEquals('context1-key', Config::getApiKey('context1'));
        $this->assertEquals('context2-key', Config::getApiKey('context2'));
    }

    public function testResetContext(): void
    {
        // Set up a context
        Config::setContext('test');
        Config::setApiKey('test-key');
        Config::setBaseUrl('https://test.canvas.com');

        // Reset the context
        Config::resetContext('test');

        // Verify values are cleared
        $this->assertNull(Config::getApiKey('test'));
        $this->assertNull(Config::getBaseUrl('test'));
    }

    public function testGetAllContexts(): void
    {
        // Set up multiple contexts
        Config::setApiKey('key1', 'context1');
        Config::setApiKey('key2', 'context2');
        Config::setApiKey('key3', 'context3');

        $contexts = Config::getAllContexts();
        
        $this->assertContains('context1', $contexts);
        $this->assertContains('context2', $contexts);
        $this->assertContains('context3', $contexts);
    }

    public function testEnvironmentAutoDetection(): void
    {
        // Set environment variables
        $_ENV['CANVAS_API_KEY'] = 'env-api-key';
        $_ENV['CANVAS_BASE_URL'] = 'https://env.canvas.com';
        $_ENV['CANVAS_ACCOUNT_ID'] = '999';
        $_ENV['CANVAS_API_VERSION'] = 'v3';
        $_ENV['CANVAS_TIMEOUT'] = '120';

        // Auto-detect configuration
        Config::autoDetect();

        // Verify values were loaded
        $this->assertEquals('env-api-key', Config::getApiKey());
        $this->assertEquals('https://env.canvas.com/', Config::getBaseUrl());
        $this->assertEquals(999, Config::getAccountId());
        $this->assertEquals('v3', Config::getApiVersion());
        $this->assertEquals(120, Config::getTimeout());

        // Clean up environment
        unset($_ENV['CANVAS_API_KEY']);
        unset($_ENV['CANVAS_BASE_URL']);
        unset($_ENV['CANVAS_ACCOUNT_ID']);
        unset($_ENV['CANVAS_API_VERSION']);
        unset($_ENV['CANVAS_TIMEOUT']);
    }

    public function testEnvironmentAutoDetectionWithContext(): void
    {
        // Set environment variables
        $_ENV['CANVAS_API_KEY'] = 'env-api-key';
        $_ENV['CANVAS_BASE_URL'] = 'https://env.canvas.com';

        // Auto-detect for specific context
        Config::autoDetect('env-context');

        // Verify values were loaded in correct context
        $this->assertEquals('env-api-key', Config::getApiKey('env-context'));
        $this->assertEquals('https://env.canvas.com/', Config::getBaseUrl('env-context'));

        // Clean up environment
        unset($_ENV['CANVAS_API_KEY']);
        unset($_ENV['CANVAS_BASE_URL']);
    }

    public function testDebugConfig(): void
    {
        Config::setApiKey('debug-key');
        Config::setBaseUrl('https://debug.canvas.com');
        Config::setAccountId(777);

        $debug = Config::debugConfig();

        $this->assertEquals('default', $debug['active_context']);
        $this->assertEquals('default', $debug['requested_context']);
        $this->assertEquals('***-key', $debug['app_key']); // Masked API key
        $this->assertEquals('https://debug.canvas.com/', $debug['base_url']);
        $this->assertEquals(777, $debug['account_id']);
        $this->assertIsArray($debug['all_contexts']);
    }

    public function testValidateConfiguration(): void
    {
        // Test valid configuration
        Config::setApiKey('valid-key');
        Config::setBaseUrl('https://valid.canvas.com');
        Config::setApiVersion('v1');

        // Should not throw exception
        Config::validate();
        $this->assertTrue(true);
    }

    public function testValidateConfigurationMissingApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('API key not set for context: default');

        Config::resetContext('default');
        Config::validate();
    }

    public function testValidateConfigurationMissingBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Base URL not set for context: default');

        Config::setApiKey('key');
        Config::validate();
    }

    public function testInvalidUrlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL provided for base URL: not-a-valid-url');

        Config::setBaseUrl('not-a-valid-url');
    }

    public function testBaseUrlNormalization(): void
    {
        // Test that trailing slash is added
        Config::setBaseUrl('https://canvas.example.com');
        $this->assertEquals('https://canvas.example.com/', Config::getBaseUrl());

        // Test that existing trailing slash is preserved
        Config::setBaseUrl('https://canvas.example.com/');
        $this->assertEquals('https://canvas.example.com/', Config::getBaseUrl());
    }

    public function testApiKeyAliases(): void
    {
        // Test setApiKey/getApiKey aliases
        Config::setApiKey('alias-test-key');
        $this->assertEquals('alias-test-key', Config::getApiKey());
        $this->assertEquals('alias-test-key', Config::getAppKey());
    }

    public function testDefaultValues(): void
    {
        // Reset context to test defaults
        Config::resetContext('fresh');
        Config::setContext('fresh');

        // Check default values
        $this->assertEquals(1, Config::getAccountId());
        $this->assertEquals('v1', Config::getApiVersion());
        $this->assertEquals(30, Config::getTimeout());
        $this->assertNull(Config::getApiKey());
        $this->assertNull(Config::getBaseUrl());
    }

    public function testMultiTenantScenario(): void
    {
        // Simulate a real multi-tenant scenario
        
        // Production tenant
        Config::setContext('production');
        Config::setApiKey('prod-key');
        Config::setBaseUrl('https://prod.canvas.com');
        Config::setAccountId(1);

        // Staging tenant
        Config::setContext('staging');
        Config::setApiKey('staging-key');
        Config::setBaseUrl('https://staging.canvas.com');
        Config::setAccountId(2);

        // Test tenant
        Config::setContext('test');
        Config::setApiKey('test-key');
        Config::setBaseUrl('https://test.canvas.com');
        Config::setAccountId(3);

        // Verify we can switch between tenants without interference
        Config::setContext('production');
        $this->assertEquals('prod-key', Config::getApiKey());

        Config::setContext('staging');
        $this->assertEquals('staging-key', Config::getApiKey());

        Config::setContext('test');
        $this->assertEquals('test-key', Config::getApiKey());

        // Verify direct context access
        $this->assertEquals('prod-key', Config::getApiKey('production'));
        $this->assertEquals('staging-key', Config::getApiKey('staging'));
        $this->assertEquals('test-key', Config::getApiKey('test'));
    }

    public function testEnvironmentValidationEmptyApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('CANVAS_API_KEY environment variable is empty');

        // Clean environment first
        unset($_ENV['CANVAS_BASE_URL'], $_ENV['CANVAS_ACCOUNT_ID'], $_ENV['CANVAS_API_VERSION'], $_ENV['CANVAS_TIMEOUT']);
        $_ENV['CANVAS_API_KEY'] = '   '; // Empty after trim
        Config::autoDetect();

        unset($_ENV['CANVAS_API_KEY']);
    }

    public function testEnvironmentValidationEmptyBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('CANVAS_BASE_URL environment variable is empty');

        // Clean environment first
        unset($_ENV['CANVAS_API_KEY'], $_ENV['CANVAS_ACCOUNT_ID'], $_ENV['CANVAS_API_VERSION'], $_ENV['CANVAS_TIMEOUT']);
        $_ENV['CANVAS_BASE_URL'] = '';
        Config::autoDetect();

        unset($_ENV['CANVAS_BASE_URL']);
    }

    public function testEnvironmentValidationInvalidAccountId(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('CANVAS_ACCOUNT_ID must be a positive integer, got: invalid');

        // Clean environment first
        unset($_ENV['CANVAS_API_KEY'], $_ENV['CANVAS_BASE_URL'], $_ENV['CANVAS_API_VERSION'], $_ENV['CANVAS_TIMEOUT']);
        $_ENV['CANVAS_ACCOUNT_ID'] = 'invalid';
        Config::autoDetect();

        unset($_ENV['CANVAS_ACCOUNT_ID']);
    }

    public function testEnvironmentValidationNegativeAccountId(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('CANVAS_ACCOUNT_ID must be a positive integer, got: -1');

        // Clean environment first
        unset($_ENV['CANVAS_API_KEY'], $_ENV['CANVAS_BASE_URL'], $_ENV['CANVAS_API_VERSION'], $_ENV['CANVAS_TIMEOUT']);
        $_ENV['CANVAS_ACCOUNT_ID'] = '-1';
        Config::autoDetect();

        unset($_ENV['CANVAS_ACCOUNT_ID']);
    }

    public function testEnvironmentValidationInvalidTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('CANVAS_TIMEOUT must be a positive integer, got: abc');

        // Clean environment first
        unset($_ENV['CANVAS_API_KEY'], $_ENV['CANVAS_BASE_URL'], $_ENV['CANVAS_ACCOUNT_ID'], $_ENV['CANVAS_API_VERSION']);
        $_ENV['CANVAS_TIMEOUT'] = 'abc';
        Config::autoDetect();

        unset($_ENV['CANVAS_TIMEOUT']);
    }

    public function testHttpsUrlValidation(): void
    {
        // Should work with HTTPS
        Config::setBaseUrl('https://canvas.instructure.com');
        $this->assertEquals('https://canvas.instructure.com/', Config::getBaseUrl());
    }

    public function testHttpUrlRejectedForProduction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Canvas URL must use HTTPS for security');

        Config::setBaseUrl('http://canvas.instructure.com');
    }

    public function testHttpUrlAllowedForLocalhost(): void
    {
        // Should allow HTTP for localhost
        Config::setBaseUrl('http://localhost:3000');
        $this->assertEquals('http://localhost:3000/', Config::getBaseUrl());

        Config::setBaseUrl('http://127.0.0.1:3000');
        $this->assertEquals('http://127.0.0.1:3000/', Config::getBaseUrl());

        Config::setBaseUrl('http://canvas.local');
        $this->assertEquals('http://canvas.local/', Config::getBaseUrl());
    }

    public function testUrlWithoutHostRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL provided for base URL: https://');

        Config::setBaseUrl('https://');
    }

    public function testConfigurationSynchronization(): void
    {
        // Set up context with values
        Config::setContext('sync-test');
        Config::setApiKey('sync-key');
        Config::setBaseUrl('https://sync.canvas.com');

        // Switch to different context
        Config::setContext('other');
        Config::setApiKey('other-key');

        // Switch back to sync-test - legacy values should sync
        Config::setContext('sync-test');
        
        // Verify legacy values are synchronized
        $this->assertEquals('sync-key', Config::getApiKey());
        $this->assertEquals('https://sync.canvas.com/', Config::getBaseUrl());
    }

    public function testHasAccountIdConfigured(): void
    {
        // Fresh context should not have account ID configured
        Config::setContext('account-test');
        $this->assertFalse(Config::hasAccountIdConfigured());

        // After setting account ID, should be configured
        Config::setAccountId(123);
        $this->assertTrue(Config::hasAccountIdConfigured());

        // Different context should not be configured
        $this->assertFalse(Config::hasAccountIdConfigured('other-context'));
    }
}