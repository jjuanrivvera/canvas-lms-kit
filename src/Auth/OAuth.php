<?php

declare(strict_types=1);

namespace CanvasLMS\Auth;

use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Exceptions\MissingOAuthTokenException;
use CanvasLMS\Exceptions\OAuthRefreshFailedException;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Traits\ActivityLoggingTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * OAuth 2.0 authentication utility for Canvas LMS
 *
 * Implements the Canvas OAuth 2.0 flow including:
 * - Authorization URL generation
 * - Authorization code exchange
 * - Token refresh
 * - Token revocation
 * - Session token creation
 */
class OAuth
{
    use ActivityLoggingTrait;

    private static ?HttpClientInterface $httpClient = null;

    /**
     * Generate the authorization URL for OAuth flow
     *
     * @param array<string, mixed> $params Optional parameters including:
     *   - state: Recommended for CSRF protection
     *   - scope: Canvas API scopes (e.g., "url:GET|/api/v1/courses")
     *   - purpose: Token description for user identification
     *   - force_login: Set to '1' to force re-authentication
     *   - unique_id: Pre-populate login form
     *
     * @throws CanvasApiException If client_id or redirect_uri are not configured
     *
     * @return string The authorization URL to redirect the user to
     */
    public static function getAuthorizationUrl(array $params = []): string
    {
        $defaults = [
            'client_id' => Config::getOAuthClientId(),
            'response_type' => 'code',
            'redirect_uri' => Config::getOAuthRedirectUri(),
        ];

        $params = array_merge($defaults, $params);

        // Log OAuth operation
        $logger = Config::getLogger();
        $logger->info('OAuth: Generating authorization URL', [
            'has_state' => isset($params['state']),
            'has_scope' => isset($params['scope']),
            'has_purpose' => isset($params['purpose']),
            'force_login' => $params['force_login'] ?? false,
        ]);

        if (empty($params['client_id']) || empty($params['redirect_uri'])) {
            throw new CanvasApiException('OAuth client_id and redirect_uri must be configured');
        }

        $baseUrl = rtrim(Config::getBaseUrl() ?? '', '/');
        if (empty($baseUrl)) {
            throw new CanvasApiException('Base URL must be configured');
        }

        // Remove /api/v1 if present in base URL
        $baseUrl = preg_replace('#/api/v\d+/?$#', '', $baseUrl);

        return $baseUrl . '/login/oauth2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code The authorization code from Canvas callback
     * @param array<string, mixed> $options Optional parameters including:
     *   - replace_tokens: Set to '1' to replace existing tokens
     *
     * @throws CanvasApiException On exchange failure
     *
     * @return array<string, mixed> Token data including access_token, refresh_token, expires_in, user
     */
    public static function exchangeCode(string $code, array $options = []): array
    {
        $logger = Config::getLogger();
        $logger->info('OAuth: Starting authorization code exchange', [
            'has_code' => !empty($code),
            'replace_tokens' => $options['replace_tokens'] ?? false,
        ]);

        $params = array_merge([
            'grant_type' => 'authorization_code',
            'client_id' => Config::getOAuthClientId(),
            'client_secret' => Config::getOAuthClientSecret(),
            'redirect_uri' => Config::getOAuthRedirectUri(),
            'code' => $code,
        ], $options);

        if (empty($params['client_id']) || empty($params['client_secret']) || empty($params['redirect_uri'])) {
            throw new CanvasApiException('OAuth client credentials must be configured');
        }

        $baseUrl = Config::getBaseUrl();
        if (empty($baseUrl)) {
            throw new CanvasApiException('Base URL must be configured');
        }

        try {
            $client = self::getClient();
            // HttpClient now handles OAuth URLs properly - no need to manipulate URL
            $response = $client->request('POST', '/login/oauth2/token', [
                'form_params' => $params,
                'skipAuth' => true,
            ]);

            $body = $response->getBody()->getContents();

            if (!$body) {
                throw new CanvasApiException('Empty response from OAuth token endpoint');
            }

            $data = json_decode($body, true);

            if (!$data || !isset($data['access_token'])) {
                throw new CanvasApiException('Invalid response from OAuth token endpoint');
            }

            // Store tokens and user info in Config
            Config::setOAuthToken($data['access_token']);
            if (isset($data['refresh_token'])) {
                Config::setOAuthRefreshToken($data['refresh_token']);
            }
            if (isset($data['expires_in'])) {
                Config::setOAuthExpiresAt(time() + $data['expires_in']);
            }
            if (isset($data['user'])) {
                Config::setOAuthUserId($data['user']['id']);
                if (isset($data['user']['name'])) {
                    Config::setOAuthUserName($data['user']['name']);
                }
            }

            $logger->info('OAuth: Successfully exchanged authorization code for tokens', [
                'has_refresh_token' => isset($data['refresh_token']),
                'expires_in' => $data['expires_in'] ?? null,
                'user_id' => $data['user']['id'] ?? null,
                'scopes' => $data['scope'] ?? null,
            ]);

            return $data;
        } catch (RequestException $e) {
            $logger->error('OAuth: Failed to exchange authorization code', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $response = $e->getResponse();
            if ($response) {
                $error = $response->getBody()->getContents();
            } else {
                $error = $e->getMessage();
            }

            throw new CanvasApiException('OAuth code exchange failed: ' . $error);
        }
    }

    /**
     * Refresh the access token using refresh token
     *
     * Note: Canvas does not return a new refresh token
     *
     * @throws OAuthRefreshFailedException On refresh failure
     * @throws MissingOAuthTokenException If no refresh token is available
     *
     * @return array<string, mixed> Updated token data with new access_token and expires_in
     */
    public static function refreshToken(): array
    {
        $logger = Config::getLogger();
        $logger->info('OAuth: Starting token refresh');

        $refreshToken = Config::getOAuthRefreshToken();
        if (!$refreshToken) {
            $logger->error('OAuth: No refresh token available for refresh');

            throw new MissingOAuthTokenException('No refresh token available');
        }

        $clientId = Config::getOAuthClientId();
        $clientSecret = Config::getOAuthClientSecret();

        if (empty($clientId) || empty($clientSecret)) {
            throw new CanvasApiException('OAuth client credentials must be configured');
        }

        $baseUrl = Config::getBaseUrl();
        if (empty($baseUrl)) {
            throw new CanvasApiException('Base URL must be configured');
        }

        try {
            $client = self::getClient();
            // HttpClient now handles OAuth URLs properly - no need to manipulate URL
            $response = $client->request('POST', '/login/oauth2/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                ],
                'skipAuth' => true,
            ]);

            $body = $response->getBody()->getContents();

            if (!$body) {
                throw new OAuthRefreshFailedException('Empty response from token refresh');
            }

            $data = json_decode($body, true);

            if (!$data || !isset($data['access_token'])) {
                throw new OAuthRefreshFailedException('Invalid response from token refresh');
            }

            // Update access token and expiry (refresh token remains the same)
            Config::setOAuthToken($data['access_token']);
            if (isset($data['expires_in'])) {
                Config::setOAuthExpiresAt(time() + $data['expires_in']);
            }

            $logger->info('OAuth: Successfully refreshed access token', [
                'expires_in' => $data['expires_in'] ?? null,
                'scopes' => $data['scope'] ?? null,
            ]);

            return $data;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $error = $response->getBody()->getContents();
            } else {
                $error = $e->getMessage();
            }

            $logger->error('OAuth: Failed to refresh token', [
                'error' => $error,
                'code' => $e->getCode(),
            ]);

            throw new OAuthRefreshFailedException('Token refresh failed: ' . $error);
        }
    }

    /**
     * Revoke the current access token
     *
     * @param bool $expireSessions Set to true to end all Canvas web sessions
     *
     * @throws MissingOAuthTokenException If no token is available
     * @throws CanvasApiException On revocation failure
     *
     * @return array<string, mixed> Response data, may contain forward_url for SSO logout
     */
    public static function revokeToken(bool $expireSessions = false): array
    {
        $logger = Config::getLogger();
        $logger->info('OAuth: Starting token revocation', [
            'expire_sessions' => $expireSessions,
        ]);

        $token = Config::getOAuthToken();
        if (!$token) {
            $logger->error('OAuth: No token available to revoke');

            throw new MissingOAuthTokenException('No OAuth token to revoke');
        }

        $baseUrl = Config::getBaseUrl();
        if (empty($baseUrl)) {
            throw new CanvasApiException('Base URL must be configured');
        }

        try {
            $client = self::getClient();

            // Note: This endpoint requires authentication, so no skipAuth
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ];

            if ($expireSessions) {
                $options['query'] = ['expire_sessions' => 1];
            }

            // HttpClient now handles OAuth URLs properly
            $response = $client->request('DELETE', '/login/oauth2/token', $options);

            // Clear stored tokens
            Config::clearOAuthTokens();

            $logger->info('OAuth: Successfully revoked token', [
                'expire_sessions' => $expireSessions,
            ]);

            $body = $response->getBody()->getContents();

            return $body ? json_decode($body, true) ?? [] : [];
        } catch (RequestException $e) {
            $logger->error('OAuth: Failed to revoke token', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $response = $e->getResponse();
            if ($response) {
                $error = $response->getBody()->getContents();
            } else {
                $error = $e->getMessage();
            }

            throw new CanvasApiException('Token revocation failed: ' . $error);
        }
    }

    /**
     * Get a session token for web-based features not available via API
     *
     * @param string|null $returnTo Optional URL to begin the web session at
     *
     * @throws MissingOAuthTokenException If no token is available
     * @throws CanvasApiException On session creation failure
     *
     * @return string The session URL
     */
    public static function getSessionToken(?string $returnTo = null): string
    {
        $token = Config::getOAuthToken();
        if (!$token) {
            throw new MissingOAuthTokenException('OAuth token required for session creation');
        }

        $baseUrl = Config::getBaseUrl();
        if (empty($baseUrl)) {
            throw new CanvasApiException('Base URL must be configured');
        }

        try {
            $client = self::getClient();

            // Note: This endpoint requires authentication, so no skipAuth
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [],
            ];

            if ($returnTo) {
                $options['json']['return_to'] = $returnTo;
            }

            // Note: session_token is a regular API endpoint (gets /api/v1/ prefix)
            $response = $client->request('POST', '/login/session_token', $options);

            $body = $response->getBody()->getContents();

            if (!$body) {
                throw new CanvasApiException('Empty response from session token endpoint');
            }

            $data = json_decode($body, true);

            if (!$data || !isset($data['session_url'])) {
                throw new CanvasApiException('Invalid response from session token endpoint');
            }

            return $data['session_url'];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $error = $response->getBody()->getContents();
            } else {
                $error = $e->getMessage();
            }

            throw new CanvasApiException('Session token creation failed: ' . $error);
        }
    }

    /**
     * Set the HTTP client for OAuth operations
     *
     * @param HttpClientInterface|null $client The HTTP client to use, or null to reset
     */
    public static function setHttpClient(?HttpClientInterface $client): void
    {
        self::$httpClient = $client;
    }

    /**
     * Get the HTTP client for OAuth operations
     *
     * @return HttpClientInterface
     */
    private static function getClient(): HttpClientInterface
    {
        if (self::$httpClient === null) {
            // Create a basic HTTP client without authentication
            // OAuth endpoints handle their own authentication
            $guzzleClient = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);
            self::$httpClient = new HttpClient($guzzleClient);
        }

        return self::$httpClient;
    }
}
