<?php

namespace CanvasLMS\Tests;

use CanvasLMS\Config;
use PHPUnit\Framework\TestCase;

class ConfigContextIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset all contexts before each test
        foreach (Config::getAllContexts() as $context) {
            Config::resetContext($context);
        }
        Config::setContext('default');
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        foreach (Config::getAllContexts() as $context) {
            Config::resetContext($context);
        }
        Config::setContext('default');
    }

    public function testContextIsolationPreventsValueBleeding(): void
    {
        // Test that switching to a new context without setting all values
        // properly uses defaults instead of bleeding values from previous contexts
        
        // First set up a context with non-default values
        Config::setContext('source');
        Config::setApiKey('source-key');
        Config::setBaseUrl('https://source.canvas.com/');
        Config::setAccountId(999);
        Config::setTimeout(120);
        Config::setApiVersion('v5');
        
        // Now switch to a new context and only set some values
        Config::setContext('target');
        Config::setApiKey('target-key');
        Config::setBaseUrl('https://target.canvas.com/');
        // Don't set accountId, timeout, or apiVersion
        
        // Verify that unset values use defaults, not values from 'source' context
        $this->assertSame('target-key', Config::getApiKey());
        $this->assertSame('https://target.canvas.com/', Config::getBaseUrl());
        $this->assertSame(1, Config::getAccountId()); // Default, not 999
        $this->assertSame(30, Config::getTimeout()); // Default, not 120
        $this->assertSame('v1', Config::getApiVersion()); // Default, not 'v5'
        
        // Verify source context still has its values
        $this->assertSame('source-key', Config::getApiKey('source'));
        $this->assertSame('https://source.canvas.com/', Config::getBaseUrl('source'));
        $this->assertSame(999, Config::getAccountId('source'));
        $this->assertSame(120, Config::getTimeout('source'));
        $this->assertSame('v5', Config::getApiVersion('source'));
    }
    
    public function testContextSwitchingResetsProperly(): void
    {
        // Test rapid context switching to ensure no value contamination
        $contexts = [
            'ctx1' => ['key' => 'key1', 'url' => 'https://ctx1.com/', 'account' => 10, 'timeout' => 20, 'version' => 'v1'],
            'ctx2' => ['key' => 'key2', 'url' => 'https://ctx2.com/', 'account' => 20, 'timeout' => 40, 'version' => 'v2'],
            'ctx3' => ['key' => 'key3', 'url' => 'https://ctx3.com/', 'account' => 30, 'timeout' => 60, 'version' => 'v3'],
        ];
        
        // Set up all contexts
        foreach ($contexts as $name => $values) {
            Config::setContext($name);
            Config::setApiKey($values['key']);
            Config::setBaseUrl($values['url']);
            Config::setAccountId($values['account']);
            Config::setTimeout($values['timeout']);
            Config::setApiVersion($values['version']);
        }
        
        // Rapidly switch between contexts and verify isolation
        for ($i = 0; $i < 10; $i++) {
            foreach ($contexts as $name => $expected) {
                Config::setContext($name);
                $this->assertSame($expected['key'], Config::getApiKey(), "Iteration $i, context $name: API key mismatch");
                $this->assertSame($expected['url'], Config::getBaseUrl(), "Iteration $i, context $name: URL mismatch");
                $this->assertSame($expected['account'], Config::getAccountId(), "Iteration $i, context $name: Account ID mismatch");
                $this->assertSame($expected['timeout'], Config::getTimeout(), "Iteration $i, context $name: Timeout mismatch");
                $this->assertSame($expected['version'], Config::getApiVersion(), "Iteration $i, context $name: Version mismatch");
            }
        }
    }
    
    public function testPartialContextConfigurationUsesDefaults(): void
    {
        // Test that each field can be independently set or use defaults
        Config::setContext('partial1');
        Config::setApiKey('partial1-key');
        // Leave everything else as default
        
        $this->assertSame('partial1-key', Config::getApiKey());
        $this->assertNull(Config::getBaseUrl());
        $this->assertSame(1, Config::getAccountId());
        $this->assertSame(30, Config::getTimeout());
        $this->assertSame('v1', Config::getApiVersion());
        
        // Different partial configuration
        Config::setContext('partial2');
        Config::setBaseUrl('https://partial2.com/');
        Config::setAccountId(42);
        // Leave other fields as default
        
        $this->assertNull(Config::getApiKey());
        $this->assertSame('https://partial2.com/', Config::getBaseUrl());
        $this->assertSame(42, Config::getAccountId());
        $this->assertSame(30, Config::getTimeout());
        $this->assertSame('v1', Config::getApiVersion());
        
        // Verify partial1 still has its configuration
        Config::setContext('partial1');
        $this->assertSame('partial1-key', Config::getApiKey());
        $this->assertNull(Config::getBaseUrl());
        $this->assertSame(1, Config::getAccountId());
    }
    
    public function testDefaultContextRemainsIsolated(): void
    {
        // Configure default context
        Config::setContext('default');
        Config::setApiKey('default-key');
        Config::setBaseUrl('https://default.com/');
        Config::setAccountId(1000);
        
        // Switch to new context
        Config::setContext('other');
        Config::setApiKey('other-key');
        
        // Default context should not affect 'other' context
        $this->assertSame('other-key', Config::getApiKey());
        $this->assertNull(Config::getBaseUrl()); // Should be null, not default's URL
        $this->assertSame(1, Config::getAccountId()); // Should be 1, not 1000
        
        // Switch back to default
        Config::setContext('default');
        $this->assertSame('default-key', Config::getApiKey());
        $this->assertSame('https://default.com/', Config::getBaseUrl());
        $this->assertSame(1000, Config::getAccountId());
    }
    
    public function testSecurityScenarioApiKeyIsolation(): void
    {
        // Critical security test: API keys must never leak between contexts
        
        // Production context with real API key
        Config::setContext('production');
        Config::setApiKey('prod-secret-key-12345');
        Config::setBaseUrl('https://prod.canvas.com/');
        
        // Development context
        Config::setContext('development');
        Config::setApiKey('dev-test-key');
        Config::setBaseUrl('https://dev.canvas.com/');
        
        // Test context without API key
        Config::setContext('test');
        Config::setBaseUrl('https://test.canvas.com/');
        
        // Verify no API key leakage to test context
        $this->assertNull(Config::getApiKey());
        $this->assertSame('https://test.canvas.com/', Config::getBaseUrl());
        
        // Verify each context maintains its own API key
        $this->assertSame('prod-secret-key-12345', Config::getApiKey('production'));
        $this->assertSame('dev-test-key', Config::getApiKey('development'));
        $this->assertNull(Config::getApiKey('test'));
        
        // Switch contexts multiple times and verify isolation
        Config::setContext('production');
        $this->assertSame('prod-secret-key-12345', Config::getApiKey());
        
        Config::setContext('test');
        $this->assertNull(Config::getApiKey()); // Must remain null
        
        Config::setContext('development');
        $this->assertSame('dev-test-key', Config::getApiKey());
    }
    
    public function testMiddlewareContextIsolation(): void
    {
        // Test that middleware configuration is also isolated between contexts
        Config::setContext('context1');
        Config::setMiddleware([
            'retry' => ['max_attempts' => 5, 'delay' => 1000],
            'rate_limit' => ['enabled' => true],
        ]);
        
        Config::setContext('context2');
        Config::setMiddleware([
            'retry' => ['max_attempts' => 2, 'delay' => 500],
            'rate_limit' => ['enabled' => false],
        ]);
        
        // Verify context1 middleware
        Config::setContext('context1');
        $middleware1 = Config::getMiddleware();
        $this->assertSame(5, $middleware1['retry']['max_attempts']);
        $this->assertSame(1000, $middleware1['retry']['delay']);
        $this->assertTrue($middleware1['rate_limit']['enabled']);
        
        // Verify context2 middleware
        Config::setContext('context2');
        $middleware2 = Config::getMiddleware();
        $this->assertSame(2, $middleware2['retry']['max_attempts']);
        $this->assertSame(500, $middleware2['retry']['delay']);
        $this->assertFalse($middleware2['rate_limit']['enabled']);
        
        // Context without middleware should have empty array
        Config::setContext('context3');
        $this->assertSame([], Config::getMiddleware());
    }
    
    public function testResetContextClearsAllValues(): void
    {
        // Set up a context with all values
        Config::setContext('to-reset');
        Config::setApiKey('reset-key');
        Config::setBaseUrl('https://reset.com/');
        Config::setAccountId(500);
        Config::setTimeout(45);
        Config::setApiVersion('v4');
        Config::setMiddleware(['retry' => ['max_attempts' => 10]]);
        
        // Verify values are set
        $this->assertSame('reset-key', Config::getApiKey('to-reset'));
        $this->assertSame('https://reset.com/', Config::getBaseUrl('to-reset'));
        $this->assertSame(500, Config::getAccountId('to-reset'));
        
        // Reset the context
        Config::resetContext('to-reset');
        
        // If we're in the reset context, values should be defaults
        Config::setContext('to-reset');
        $this->assertNull(Config::getApiKey());
        $this->assertNull(Config::getBaseUrl());
        $this->assertSame(1, Config::getAccountId());
        $this->assertSame(30, Config::getTimeout());
        $this->assertSame('v1', Config::getApiVersion());
        $this->assertSame([], Config::getMiddleware());
    }
}