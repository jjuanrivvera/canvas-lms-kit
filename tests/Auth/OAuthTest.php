<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Auth;

use CanvasLMS\Auth\OAuth;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\OAuthRefreshFailedException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

/**
 * Tests for OAuth authentication functionality
 */
class OAuthTest extends TestCase
{
    private HttpClientInterface $mockClient;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset configuration
        Config::setContext('default');
        Config::setBaseUrl('https://canvas.test.com');
        Config::setOAuthClientId('test_client_id');
        Config::setOAuthClientSecret('test_client_secret');
        Config::setOAuthRedirectUri('https://app.test.com/oauth/callback');
        
        // Create mock HTTP client
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        OAuth::setHttpClient($this->mockClient);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Config::setContext('default');
        Config::clearOAuthTokens();
        Config::useApiKey();
    }
    
    /**
     * Test authorization URL generation
     */
    public function testGetAuthorizationUrl(): void
    {
        $url = OAuth::getAuthorizationUrl([
            'state' => 'test_state',
            'scope' => 'url:GET|/api/v1/courses'
        ]);
        
        $this->assertStringStartsWith('https://canvas.test.com/login/oauth2/auth', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://app.test.com/oauth/callback'), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=test_state', $url);
        $this->assertStringContainsString('scope=' . urlencode('url:GET|/api/v1/courses'), $url);
    }
    
    /**
     * Test authorization URL with optional parameters
     */
    public function testGetAuthorizationUrlWithOptionalParams(): void
    {
        $url = OAuth::getAuthorizationUrl([
            'state' => 'test_state',
            'force_login' => '1',
            'unique_id' => 'user@example.com',
            'purpose' => 'Test Application'
        ]);
        
        $this->assertStringContainsString('force_login=1', $url);
        $this->assertStringContainsString('unique_id=' . urlencode('user@example.com'), $url);
        $this->assertStringContainsString('purpose=' . urlencode('Test Application'), $url);
    }
    
    /**
     * Test successful code exchange
     */
    public function testExchangeCode(): void
    {
        $tokenData = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
            'user' => [
                'id' => 123,
                'name' => 'Test User',
                'effective_locale' => 'en'
            ]
        ];
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/oauth2/token'),
                $this->equalTo([
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => 'test_client_id',
                        'client_secret' => 'test_client_secret',
                        'redirect_uri' => 'https://app.test.com/oauth/callback',
                        'code' => 'test_code'
                    ],
                    'skipAuth' => true
                ])
            )
            ->willReturn(new Response(200, [], json_encode($tokenData)));
        
        $result = OAuth::exchangeCode('test_code');
        
        $this->assertEquals('test_access_token', $result['access_token']);
        $this->assertEquals('test_refresh_token', $result['refresh_token']);
        $this->assertEquals(3600, $result['expires_in']);
        $this->assertEquals(123, $result['user']['id']);
        
        // Verify tokens were stored in Config
        $this->assertEquals('test_access_token', Config::getOAuthToken());
        $this->assertEquals('test_refresh_token', Config::getOAuthRefreshToken());
        $this->assertEquals(123, Config::getOAuthUserId());
        $this->assertEquals('Test User', Config::getOAuthUserName());
    }
    
    /**
     * Test code exchange with replace_tokens option
     */
    public function testExchangeCodeWithReplaceTokens(): void
    {
        $tokenData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
            'user' => ['id' => 456, 'name' => 'New User']
        ];
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/oauth2/token'),
                $this->callback(function ($options) {
                    return isset($options['form_params']['replace_tokens']) &&
                           $options['form_params']['replace_tokens'] === '1' &&
                           isset($options['skipAuth']) &&
                           $options['skipAuth'] === true;
                })
            )
            ->willReturn(new Response(200, [], json_encode($tokenData)));
        
        $result = OAuth::exchangeCode('test_code', ['replace_tokens' => '1']);
        
        $this->assertEquals('new_access_token', $result['access_token']);
    }
    
    /**
     * Test successful token refresh
     */
    public function testRefreshToken(): void
    {
        // Set up existing tokens
        Config::setOAuthRefreshToken('existing_refresh_token');
        Config::setOAuthExpiresAt(time() - 100); // Expired
        
        $refreshData = [
            'access_token' => 'refreshed_access_token',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ];
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/oauth2/token'),
                $this->equalTo([
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'client_id' => 'test_client_id',
                        'client_secret' => 'test_client_secret',
                        'refresh_token' => 'existing_refresh_token'
                    ],
                    'skipAuth' => true
                ])
            )
            ->willReturn(new Response(200, [], json_encode($refreshData)));
        
        $result = OAuth::refreshToken();
        
        $this->assertEquals('refreshed_access_token', $result['access_token']);
        $this->assertEquals(3600, $result['expires_in']);
        
        // Verify new token was stored
        $this->assertEquals('refreshed_access_token', Config::getOAuthToken());
        // Refresh token should remain the same (Canvas doesn't return new one)
        $this->assertEquals('existing_refresh_token', Config::getOAuthRefreshToken());
    }
    
    /**
     * Test refresh token failure
     */
    public function testRefreshTokenFailure(): void
    {
        Config::setOAuthRefreshToken('invalid_refresh_token');
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/oauth2/token'),
                $this->callback(function ($options) {
                    return isset($options['skipAuth']) && $options['skipAuth'] === true;
                })
            )
            ->willReturn(new Response(401, [], json_encode([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid refresh token'
            ])));
        
        $this->expectException(OAuthRefreshFailedException::class);
        $this->expectExceptionMessage('Invalid response from token refresh');
        
        OAuth::refreshToken();
    }
    
    /**
     * Test token revocation
     */
    public function testRevokeToken(): void
    {
        Config::setOAuthToken('token_to_revoke');
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('DELETE'),
                $this->equalTo('/login/oauth2/token'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer token_to_revoke'
                    ]
                ])
            )
            ->willReturn(new Response(200, [], json_encode(['success' => true])));
        
        $result = OAuth::revokeToken();
        
        $this->assertTrue($result['success']);
        
        // Verify tokens were cleared
        $this->assertNull(Config::getOAuthToken());
        $this->assertNull(Config::getOAuthRefreshToken());
    }
    
    /**
     * Test token revocation with session expiration
     */
    public function testRevokeTokenWithSessionExpiration(): void
    {
        Config::setOAuthToken('token_to_revoke');
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('DELETE'),
                $this->equalTo('/login/oauth2/token'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer token_to_revoke'
                    ],
                    'query' => [
                        'expire_sessions' => 1
                    ]
                ])
            )
            ->willReturn(new Response(200, [], json_encode([
                'forward_url' => 'https://sso.example.com/logout'
            ])));
        
        $result = OAuth::revokeToken(true);
        
        $this->assertEquals('https://sso.example.com/logout', $result['forward_url']);
    }
    
    /**
     * Test getting session token
     */
    public function testGetSessionToken(): void
    {
        Config::setOAuthToken('test_token');
        
        $sessionUrl = 'https://canvas.test.com/sessions/123abc';
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/session_token'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer test_token'
                    ],
                    'json' => []
                ])
            )
            ->willReturn(new Response(200, [], json_encode([
                'session_url' => $sessionUrl
            ])));
        
        $result = OAuth::getSessionToken();
        
        $this->assertEquals($sessionUrl, $result);
    }
    
    /**
     * Test getting session token with return URL
     */
    public function testGetSessionTokenWithReturnUrl(): void
    {
        Config::setOAuthToken('test_token');
        
        $sessionUrl = 'https://canvas.test.com/sessions/123abc?return_to=%2Fcourses%2F123';
        
        $this->mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('/login/session_token'),
                $this->equalTo([
                    'headers' => [
                        'Authorization' => 'Bearer test_token'
                    ],
                    'json' => [
                        'return_to' => '/courses/123'
                    ]
                ])
            )
            ->willReturn(new Response(200, [], json_encode([
                'session_url' => $sessionUrl
            ])));
        
        $result = OAuth::getSessionToken('/courses/123');
        
        $this->assertEquals($sessionUrl, $result);
    }
    
    /**
     * Test OAuth mode switching
     */
    public function testAuthModeSwitching(): void
    {
        // Start with API key mode
        Config::useApiKey();
        $this->assertEquals('api_key', Config::getAuthMode());
        
        // Switch to OAuth mode
        Config::useOAuth();
        $this->assertEquals('oauth', Config::getAuthMode());
        
        // Switch back to API key mode
        Config::useApiKey();
        $this->assertEquals('api_key', Config::getAuthMode());
    }
    
    /**
     * Test token expiration checking
     */
    public function testTokenExpirationChecking(): void
    {
        // Test expired token
        Config::setOAuthExpiresAt(time() - 100);
        $this->assertTrue(Config::isOAuthTokenExpired());
        
        // Test valid token
        Config::setOAuthExpiresAt(time() + 3600);
        $this->assertFalse(Config::isOAuthTokenExpired());
        
        // Test with buffer (5 minutes before expiry)
        Config::setOAuthExpiresAt(time() + 200); // Expires in 200 seconds
        $this->assertTrue(Config::isOAuthTokenExpired()); // Should be considered expired with 5-min buffer
        
        Config::setOAuthExpiresAt(time() + 400); // Expires in 400 seconds
        $this->assertFalse(Config::isOAuthTokenExpired()); // Still valid with buffer
    }
    
    /**
     * Test multi-context OAuth configuration
     */
    public function testMultiContextOAuth(): void
    {
        // Configure production context
        Config::setContext('production');
        Config::setOAuthClientId('prod_client_id', 'production');
        Config::setOAuthToken('prod_token', 'production');
        Config::useOAuth('production');
        
        // Configure test context
        Config::setContext('test');
        Config::setOAuthClientId('test_client_id', 'test');
        Config::setOAuthToken('test_token', 'test');
        Config::useOAuth('test');
        
        // Verify production context
        Config::setContext('production');
        $this->assertEquals('prod_client_id', Config::getOAuthClientId());
        $this->assertEquals('prod_token', Config::getOAuthToken());
        $this->assertEquals('oauth', Config::getAuthMode());
        
        // Verify test context
        Config::setContext('test');
        $this->assertEquals('test_client_id', Config::getOAuthClientId());
        $this->assertEquals('test_token', Config::getOAuthToken());
        $this->assertEquals('oauth', Config::getAuthMode());
    }
    
    /**
     * Test clearing OAuth tokens
     */
    public function testClearOAuthTokens(): void
    {
        // Set up tokens
        Config::setOAuthToken('test_token');
        Config::setOAuthRefreshToken('test_refresh');
        Config::setOAuthExpiresAt(time() + 3600);
        Config::setOAuthUserId(123);
        Config::setOAuthUserName('Test User');
        Config::setOAuthScopes(['scope1', 'scope2']);
        
        // Clear tokens
        Config::clearOAuthTokens();
        
        // Verify all OAuth data was cleared
        $this->assertNull(Config::getOAuthToken());
        $this->assertNull(Config::getOAuthRefreshToken());
        $this->assertNull(Config::getOAuthExpiresAt());
        $this->assertNull(Config::getOAuthUserId());
        $this->assertNull(Config::getOAuthUserName());
        $this->assertNull(Config::getOAuthScopes());
    }
    
    /**
     * Test environment variable configuration
     */
    public function testEnvironmentVariableConfiguration(): void
    {
        // Simulate environment variables
        $_ENV['CANVAS_OAUTH_CLIENT_ID'] = 'env_client_id';
        $_ENV['CANVAS_OAUTH_CLIENT_SECRET'] = 'env_client_secret';
        $_ENV['CANVAS_OAUTH_REDIRECT_URI'] = 'https://env.app.com/callback';
        $_ENV['CANVAS_AUTH_MODE'] = 'oauth';
        
        // Auto-detect from environment
        Config::autoDetect();
        
        $this->assertEquals('env_client_id', Config::getOAuthClientId());
        $this->assertEquals('env_client_secret', Config::getOAuthClientSecret());
        $this->assertEquals('https://env.app.com/callback', Config::getOAuthRedirectUri());
        $this->assertEquals('oauth', Config::getAuthMode());
        
        // Clean up
        unset($_ENV['CANVAS_OAUTH_CLIENT_ID']);
        unset($_ENV['CANVAS_OAUTH_CLIENT_SECRET']);
        unset($_ENV['CANVAS_OAUTH_REDIRECT_URI']);
        unset($_ENV['CANVAS_AUTH_MODE']);
    }
    
    /**
     * Test authentication bypass functionality for OAuth token exchange
     */
    public function testAuthenticationBypassForTokenExchange(): void
    {
        // Reset to a real HttpClient to test authentication bypass
        OAuth::setHttpClient(null);
        
        // Set auth mode to OAuth but don't set any tokens (should not cause exception)
        Config::useOAuth();
        Config::clearOAuthTokens();
        
        $tokenData = [
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
            'user' => ['id' => 123, 'name' => 'Test User']
        ];
        
        // Create a mock HTTP client that will be used internally by OAuth
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->stringContains('/login/oauth2/token'),
                $this->callback(function ($options) {
                    // Verify that skipAuth is present and true
                    return isset($options['skipAuth']) && $options['skipAuth'] === true;
                })
            )
            ->willReturn(new Response(200, [], json_encode($tokenData)));
        
        OAuth::setHttpClient($mockHttpClient);
        
        // This should not throw MissingOAuthTokenException or MissingApiKeyException
        $result = OAuth::exchangeCode('test_code');
        
        $this->assertEquals('test_access_token', $result['access_token']);
    }
    
    /**
     * Test authentication bypass functionality for OAuth token refresh
     */
    public function testAuthenticationBypassForTokenRefresh(): void
    {
        // Reset to a real HttpClient to test authentication bypass
        OAuth::setHttpClient(null);
        
        // Set auth mode to API key but don't set API key (should not cause exception)
        Config::useApiKey();
        Config::setAppKey(''); // Clear API key
        Config::setOAuthRefreshToken('test_refresh_token');
        
        $refreshData = [
            'access_token' => 'refreshed_access_token',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ];
        
        // Create a mock HTTP client that will be used internally by OAuth
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->stringContains('/login/oauth2/token'),
                $this->callback(function ($options) {
                    // Verify that skipAuth is present and true
                    return isset($options['skipAuth']) && $options['skipAuth'] === true;
                })
            )
            ->willReturn(new Response(200, [], json_encode($refreshData)));
        
        OAuth::setHttpClient($mockHttpClient);
        
        // This should not throw MissingApiKeyException
        $result = OAuth::refreshToken();
        
        $this->assertEquals('refreshed_access_token', $result['access_token']);
    }
    
    /**
     * Test that OAuth token revocation still requires authentication
     */
    public function testTokenRevocationStillRequiresAuthentication(): void
    {
        // Reset to a real HttpClient
        OAuth::setHttpClient(null);
        
        // Set OAuth token for revocation
        Config::setOAuthToken('token_to_revoke');
        
        // Create a mock HTTP client 
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('DELETE'),
                $this->stringContains('/login/oauth2/token'),
                $this->callback(function ($options) {
                    // Verify that skipAuth is NOT present (authentication should be applied)
                    return !isset($options['skipAuth']) &&
                           isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer token_to_revoke';
                })
            )
            ->willReturn(new Response(200, [], json_encode(['success' => true])));
        
        OAuth::setHttpClient($mockHttpClient);
        
        $result = OAuth::revokeToken();
        
        $this->assertTrue($result['success']);
    }
    
    /**
     * Test that OAuth session token creation still requires authentication
     */
    public function testSessionTokenCreationStillRequiresAuthentication(): void
    {
        // Reset to a real HttpClient
        OAuth::setHttpClient(null);
        
        // Set OAuth token for session creation
        Config::setOAuthToken('test_token');
        
        $sessionUrl = 'https://canvas.test.com/sessions/123abc';
        
        // Create a mock HTTP client
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->stringContains('/login/session_token'),
                $this->callback(function ($options) {
                    // Verify that skipAuth is NOT present (authentication should be applied)
                    return !isset($options['skipAuth']) &&
                           isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer test_token';
                })
            )
            ->willReturn(new Response(200, [], json_encode(['session_url' => $sessionUrl])));
        
        OAuth::setHttpClient($mockHttpClient);
        
        $result = OAuth::getSessionToken();
        
        $this->assertEquals($sessionUrl, $result);
    }
}