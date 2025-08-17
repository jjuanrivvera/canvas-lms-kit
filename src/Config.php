<?php

namespace CanvasLMS;

use CanvasLMS\Exceptions\ConfigurationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Config
{
    /**
     * @var array<string, array{
     *     api_version?: string,
     *     timeout?: int,
     *     app_key?: string,
     *     base_url?: string,
     *     account_id?: int,
     *     middleware?: array<string, array<string, mixed>>,
     *     oauth_client_id?: string,
     *     oauth_client_secret?: string,
     *     oauth_redirect_uri?: string,
     *     oauth_token?: string,
     *     oauth_refresh_token?: string,
     *     oauth_expires_at?: int,
     *     oauth_user_id?: int,
     *     oauth_user_name?: string,
     *     oauth_scopes?: array<string>,
     *     auth_mode?: string,
     *     logger?: LoggerInterface
     * }>
     */
    private static array $contexts = [];

    /**
     * @var string
     */
    private static string $activeContext = 'default';

    /**
     * @var string|null
     */
    private static ?string $apiVersion = 'v1';

    /**
     * @var int|null
     */
    private static ?int $timeout = 30;

    /**
     * @var string|null
     */
    private static ?string $appKey = null;

    /**
     * @var string|null
     */
    private static ?string $baseUrl = null;

    /**
     * @var int
     */
    private static int $accountId = 1;

    /**
     * @var string|null OAuth client ID
     */
    private static ?string $oauthClientId = null;

    /**
     * @var string|null OAuth client secret
     */
    private static ?string $oauthClientSecret = null;

    /**
     * @var string|null OAuth redirect URI
     */
    private static ?string $oauthRedirectUri = null;

    /**
     * @var string|null OAuth access token
     */
    private static ?string $oauthToken = null;

    /**
     * @var string|null OAuth refresh token
     */
    private static ?string $oauthRefreshToken = null;

    /**
     * @var int|null OAuth token expiration timestamp
     */
    private static ?int $oauthExpiresAt = null;

    /**
     * @var int|null OAuth user ID
     */
    private static ?int $oauthUserId = null;

    /**
     * @var string|null OAuth user name
     */
    private static ?string $oauthUserName = null;

    /**
     * @var array<string>|null OAuth scopes
     */
    private static ?array $oauthScopes = null;

    /**
     * @var string Authentication mode ('api_key' or 'oauth')
     */
    private static string $authMode = 'api_key';

    /**
     * @var LoggerInterface|null PSR-3 logger instance
     */
    private static ?LoggerInterface $logger = null;

    /**
     * Set the application key.
     *
     * @param string $key The application key.
     * @param string|null $context The context to set the key for. If null, uses active context.
     */
    public static function setAppKey(string $key, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$appKey = $key;
        }

        self::$contexts[$context]['app_key'] = $key;
    }

    /**
     * Get the application key.
     *
     * @param string|null $context The context to get the key from. If null, uses active context.
     * @return string|null The application key or null if not set.
     */
    public static function getAppKey(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['app_key'])) {
            return self::$contexts[$context]['app_key'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext) {
            return self::$appKey;
        }

        return null;
    }

    /**
     * Set the base URL for API requests.
     *
     * @param string $url The base URL.
     * @param string|null $context The context to set the URL for. If null, uses active context.
     * @throws \InvalidArgumentException If the URL is not valid.
     */
    public static function setBaseUrl(string $url, ?string $context = null): void
    {
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL provided for base URL: {$url}");
        }

        // Parse URL components for Canvas-specific validation
        $parsedUrl = parse_url($url);

        // Require HTTPS for security (allow HTTP only for localhost/development)
        if (isset($parsedUrl['scheme']) && $parsedUrl['scheme'] !== 'https') {
            if (
                !isset($parsedUrl['host']) ||
                (!str_contains($parsedUrl['host'], 'localhost') &&
                 !str_contains($parsedUrl['host'], '127.0.0.1') &&
                 !str_contains($parsedUrl['host'], '.local'))
            ) {
                throw new \InvalidArgumentException("Canvas URL must use HTTPS for security: {$url}");
            }
        }

        // Ensure host is present
        if (empty($parsedUrl['host'])) {
            throw new \InvalidArgumentException("URL must include a valid host: {$url}");
        }

        $context ??= self::$activeContext;
        $url = rtrim($url, '/') . '/'; // Ensure trailing slash

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$baseUrl = $url;
        }

        self::$contexts[$context]['base_url'] = $url;
    }

    /**
     * Get the base URL for API requests.
     *
     * @param string|null $context The context to get the URL from. If null, uses active context.
     * @return string|null The base URL or null if not set.
     */
    public static function getBaseUrl(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['base_url'])) {
            return self::$contexts[$context]['base_url'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext) {
            return self::$baseUrl;
        }

        return null;
    }

    /**
     * Set the API version.
     *
     * @param string $version The API version.
     * @param string|null $context The context to set the version for. If null, uses active context.
     */
    public static function setApiVersion(string $version, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$apiVersion = $version;
        }

        self::$contexts[$context]['api_version'] = $version;
    }

    /**
     * Get the API version.
     *
     * @param string|null $context The context to get the version from. If null, uses active context.
     * @return string|null The API version or null if not set.
     */
    public static function getApiVersion(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['api_version'])) {
            return self::$contexts[$context]['api_version'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext) {
            return self::$apiVersion;
        }

        return null;
    }

    /**
     * Set the timeout for API requests.
     *
     * @param int $timeout The timeout in seconds.
     * @param string|null $context The context to set the timeout for. If null, uses active context.
     */
    public static function setTimeout(int $timeout, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$timeout = $timeout;
        }

        self::$contexts[$context]['timeout'] = $timeout;
    }

    /**
     * Get the timeout for API requests.
     *
     * @param string|null $context The context to get the timeout from. If null, uses active context.
     * @return int|null The timeout in seconds or null if not set.
     */
    public static function getTimeout(?string $context = null): ?int
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['timeout'])) {
            return self::$contexts[$context]['timeout'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext) {
            return self::$timeout;
        }

        return null;
    }

    /**
     * Set the account ID.
     *
     * @param int $accountId The account ID.
     * @param string|null $context The context to set the account ID for. If null, uses active context.
     */
    public static function setAccountId(int $accountId, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$accountId = $accountId;
        }

        self::$contexts[$context]['account_id'] = $accountId;
    }

    /**
     * Get the account ID.
     *
     * @param string|null $context The context to get the account ID from. If null, uses active context.
     * @return int The account ID.
     */
    public static function getAccountId(?string $context = null): int
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['account_id'])) {
            return self::$contexts[$context]['account_id'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext) {
            return self::$accountId;
        }

        // Warn about using default value if no configuration was explicitly set
        if (!self::hasAccountIdConfigured($context)) {
            $logger = self::getLogger($context);
            $logger->notice(
                "No account ID configured for context '{$context}', using default value 1. " .
                "Consider setting explicitly with Config::setAccountId().",
                ['context' => $context, 'default_value' => 1]
            );
        }

        // Return default if nothing else found
        return 1;
    }

    /**
     * Check if an account ID has been explicitly configured for a context.
     *
     * @param string|null $context The context to check. If null, uses active context.
     * @return bool True if account ID is explicitly configured, false if using default.
     */
    public static function hasAccountIdConfigured(?string $context = null): bool
    {
        $context ??= self::$activeContext;

        // Check if explicitly set in context
        if (isset(self::$contexts[$context]['account_id'])) {
            return true;
        }

        // Check if explicitly set in legacy (for active context only)
        if ($context === self::$activeContext && self::$accountId !== 1) {
            return true;
        }

        return false;
    }

    /**
     * Set the active context for configuration.
     *
     * @param string $context The context name to make active.
     */
    public static function setContext(string $context): void
    {
        self::$activeContext = $context;

        // Sync legacy values with the new active context for backward compatibility
        self::syncLegacyValues();
    }

    /**
     * Synchronize legacy static properties with active context values.
     * This ensures backward compatibility when switching contexts.
     */
    private static function syncLegacyValues(): void
    {
        $context = self::$activeContext;

        // Reset to defaults first to ensure proper context isolation
        self::$appKey = null;
        self::$baseUrl = null;
        self::$apiVersion = 'v1';
        self::$timeout = 30;
        self::$accountId = 1;
        self::$oauthClientId = null;
        self::$oauthClientSecret = null;
        self::$oauthRedirectUri = null;
        self::$oauthToken = null;
        self::$oauthRefreshToken = null;
        self::$oauthExpiresAt = null;
        self::$oauthUserId = null;
        self::$oauthUserName = null;
        self::$oauthScopes = null;
        self::$authMode = 'api_key';
        self::$logger = null;

        // Then apply context-specific values if they exist
        if (isset(self::$contexts[$context]['app_key'])) {
            self::$appKey = self::$contexts[$context]['app_key'];
        }
        if (isset(self::$contexts[$context]['base_url'])) {
            self::$baseUrl = self::$contexts[$context]['base_url'];
        }
        if (isset(self::$contexts[$context]['api_version'])) {
            self::$apiVersion = self::$contexts[$context]['api_version'];
        }
        if (isset(self::$contexts[$context]['timeout'])) {
            self::$timeout = self::$contexts[$context]['timeout'];
        }
        if (isset(self::$contexts[$context]['account_id'])) {
            self::$accountId = self::$contexts[$context]['account_id'];
        }
        if (isset(self::$contexts[$context]['oauth_client_id'])) {
            self::$oauthClientId = self::$contexts[$context]['oauth_client_id'];
        }
        if (isset(self::$contexts[$context]['oauth_client_secret'])) {
            self::$oauthClientSecret = self::$contexts[$context]['oauth_client_secret'];
        }
        if (isset(self::$contexts[$context]['oauth_redirect_uri'])) {
            self::$oauthRedirectUri = self::$contexts[$context]['oauth_redirect_uri'];
        }
        if (isset(self::$contexts[$context]['oauth_token'])) {
            self::$oauthToken = self::$contexts[$context]['oauth_token'];
        }
        if (isset(self::$contexts[$context]['oauth_refresh_token'])) {
            self::$oauthRefreshToken = self::$contexts[$context]['oauth_refresh_token'];
        }
        if (isset(self::$contexts[$context]['oauth_expires_at'])) {
            self::$oauthExpiresAt = self::$contexts[$context]['oauth_expires_at'];
        }
        if (isset(self::$contexts[$context]['oauth_user_id'])) {
            self::$oauthUserId = self::$contexts[$context]['oauth_user_id'];
        }
        if (isset(self::$contexts[$context]['oauth_user_name'])) {
            self::$oauthUserName = self::$contexts[$context]['oauth_user_name'];
        }
        if (isset(self::$contexts[$context]['oauth_scopes'])) {
            self::$oauthScopes = self::$contexts[$context]['oauth_scopes'];
        }
        if (isset(self::$contexts[$context]['auth_mode'])) {
            self::$authMode = self::$contexts[$context]['auth_mode'];
        }
        if (isset(self::$contexts[$context]['logger'])) {
            self::$logger = self::$contexts[$context]['logger'];
        }
    }

    /**
     * Get the currently active context.
     *
     * @return string The active context name.
     */
    public static function getContext(): string
    {
        return self::$activeContext;
    }

    /**
     * Set middleware configuration.
     *
     * @param array<string, array<string, mixed>> $middleware Middleware configuration
     * @param string|null $context The context to set the middleware for (null for active context)
     * @return void
     */
    public static function setMiddleware(array $middleware, ?string $context = null): void
    {
        $context ??= self::$activeContext;
        self::$contexts[$context]['middleware'] = $middleware;
    }

    /**
     * Get middleware configuration.
     *
     * @param string|null $context The context to get the middleware for (null for active context)
     * @return array<string, array<string, mixed>> The middleware configuration
     */
    public static function getMiddleware(?string $context = null): array
    {
        $context ??= self::$activeContext;
        return self::$contexts[$context]['middleware'] ?? [];
    }

    /**
     * Set a PSR-3 logger instance for the SDK.
     *
     * @param LoggerInterface $logger The PSR-3 logger instance
     * @param string|null $context The context to set the logger for. If null, uses active context.
     */
    public static function setLogger(LoggerInterface $logger, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Maintain backward compatibility - set both legacy and context
        if ($context === self::$activeContext) {
            self::$logger = $logger;
        }

        self::$contexts[$context]['logger'] = $logger;
    }

    /**
     * Get the configured logger instance.
     *
     * @param string|null $context The context to get the logger from. If null, uses active context.
     * @return LoggerInterface The logger instance (returns NullLogger if not configured)
     */
    public static function getLogger(?string $context = null): LoggerInterface
    {
        $context ??= self::$activeContext;

        // Check context first, then fall back to legacy for backward compatibility
        if (isset(self::$contexts[$context]['logger'])) {
            return self::$contexts[$context]['logger'];
        }

        // For backward compatibility with existing code
        if ($context === self::$activeContext && self::$logger !== null) {
            return self::$logger;
        }

        // Return NullLogger by default to maintain backward compatibility
        return new NullLogger();
    }

    /**
     * Check if a logger has been configured.
     *
     * @param string|null $context The context to check. If null, uses active context.
     * @return bool True if a logger is configured, false otherwise
     */
    public static function hasLogger(?string $context = null): bool
    {
        $context ??= self::$activeContext;

        // Check if explicitly set in context
        if (isset(self::$contexts[$context]['logger'])) {
            return true;
        }

        // Check if explicitly set in legacy (for active context only)
        if ($context === self::$activeContext && self::$logger !== null) {
            return true;
        }

        return false;
    }

    /**
     * Reset a specific context, removing all its configuration.
     *
     * @param string $context The context to reset.
     */
    public static function resetContext(string $context): void
    {
        unset(self::$contexts[$context]);

        // If resetting the active context, also clear legacy values
        if ($context === self::$activeContext) {
            self::$appKey = null;
            self::$baseUrl = null;
            self::$apiVersion = 'v1';
            self::$timeout = 30;
            self::$accountId = 1;
            self::$logger = null;
        }
    }

    /**
     * Get all configured contexts.
     *
     * @return array<string> List of context names.
     */
    public static function getAllContexts(): array
    {
        return array_keys(self::$contexts);
    }

    /**
     * Auto-detect configuration from environment variables.
     *
     * @param string|null $context The context to set configuration for. If null, uses active context.
     * @throws ConfigurationException If environment variables contain invalid values.
     */
    public static function autoDetect(?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Check for Canvas-specific environment variables with validation
        if (isset($_ENV['CANVAS_API_KEY'])) {
            $apiKey = trim($_ENV['CANVAS_API_KEY']);
            if (empty($apiKey)) {
                throw new ConfigurationException("CANVAS_API_KEY environment variable is empty");
            }
            self::setAppKey($apiKey, $context);
        }

        if (isset($_ENV['CANVAS_BASE_URL'])) {
            $baseUrl = trim($_ENV['CANVAS_BASE_URL']);
            if (empty($baseUrl)) {
                throw new ConfigurationException("CANVAS_BASE_URL environment variable is empty");
            }
            self::setBaseUrl($baseUrl, $context);
        }

        if (isset($_ENV['CANVAS_ACCOUNT_ID'])) {
            $accountId = trim($_ENV['CANVAS_ACCOUNT_ID']);
            if (!is_numeric($accountId) || (int) $accountId < 1) {
                throw new ConfigurationException("CANVAS_ACCOUNT_ID must be a positive integer, got: {$accountId}");
            }
            self::setAccountId((int) $accountId, $context);
        }

        if (isset($_ENV['CANVAS_API_VERSION'])) {
            $apiVersion = trim($_ENV['CANVAS_API_VERSION']);
            if (empty($apiVersion)) {
                throw new ConfigurationException("CANVAS_API_VERSION environment variable is empty");
            }
            self::setApiVersion($apiVersion, $context);
        }

        if (isset($_ENV['CANVAS_TIMEOUT'])) {
            $timeout = trim($_ENV['CANVAS_TIMEOUT']);
            if (!is_numeric($timeout) || (int) $timeout < 1) {
                throw new ConfigurationException("CANVAS_TIMEOUT must be a positive integer, got: {$timeout}");
            }
            self::setTimeout((int) $timeout, $context);
        }

        // OAuth-specific environment variables
        if (isset($_ENV['CANVAS_OAUTH_CLIENT_ID'])) {
            $clientId = trim($_ENV['CANVAS_OAUTH_CLIENT_ID']);
            if (!empty($clientId)) {
                self::setOAuthClientId($clientId, $context);
            }
        }

        if (isset($_ENV['CANVAS_OAUTH_CLIENT_SECRET'])) {
            $clientSecret = trim($_ENV['CANVAS_OAUTH_CLIENT_SECRET']);
            if (!empty($clientSecret)) {
                self::setOAuthClientSecret($clientSecret, $context);
            }
        }

        if (isset($_ENV['CANVAS_OAUTH_REDIRECT_URI'])) {
            $redirectUri = trim($_ENV['CANVAS_OAUTH_REDIRECT_URI']);
            if (!empty($redirectUri)) {
                self::setOAuthRedirectUri($redirectUri, $context);
            }
        }

        if (isset($_ENV['CANVAS_OAUTH_TOKEN'])) {
            $token = trim($_ENV['CANVAS_OAUTH_TOKEN']);
            if (!empty($token)) {
                self::setOAuthToken($token, $context);
            }
        }

        if (isset($_ENV['CANVAS_OAUTH_REFRESH_TOKEN'])) {
            $refreshToken = trim($_ENV['CANVAS_OAUTH_REFRESH_TOKEN']);
            if (!empty($refreshToken)) {
                self::setOAuthRefreshToken($refreshToken, $context);
            }
        }

        if (isset($_ENV['CANVAS_AUTH_MODE'])) {
            $authMode = trim(strtolower($_ENV['CANVAS_AUTH_MODE']));
            if (in_array($authMode, ['oauth', 'api_key'], true)) {
                if ($authMode === 'oauth') {
                    self::useOAuth($context);
                } else {
                    self::useApiKey($context);
                }
            }
        }
    }

    /**
     * Debug the configuration for a specific context.
     *
     * @param string|null $context The context to debug. If null, uses active context.
     * @return array<string, mixed> Configuration details for debugging.
     */
    public static function debugConfig(?string $context = null): array
    {
        $context ??= self::$activeContext;

        return [
            'active_context' => self::$activeContext,
            'requested_context' => $context,
            'app_key' => self::getAppKey($context) ? '***' . substr(self::getAppKey($context), -4) : null,
            'base_url' => self::getBaseUrl($context),
            'api_version' => self::getApiVersion($context),
            'timeout' => self::getTimeout($context),
            'account_id' => self::getAccountId($context),
            'all_contexts' => self::getAllContexts(),
        ];
    }

    /**
     * Validate the configuration for a specific context.
     *
     * @param string|null $context The context to validate. If null, uses active context.
     * @throws ConfigurationException If the configuration is invalid.
     */
    public static function validate(?string $context = null): void
    {
        $context ??= self::$activeContext;

        $appKey = self::getAppKey($context);
        if (empty($appKey)) {
            throw new ConfigurationException("API key not set for context: {$context}");
        }

        $baseUrl = self::getBaseUrl($context);
        if (empty($baseUrl)) {
            throw new ConfigurationException("Base URL not set for context: {$context}");
        }

        $apiVersion = self::getApiVersion($context);
        if (empty($apiVersion)) {
            throw new ConfigurationException("API version not set for context: {$context}");
        }
    }

    /**
     * Set the API key (alias for setAppKey for common naming).
     *
     * @param string $key The API key.
     * @param string|null $context The context to set the key for. If null, uses active context.
     */
    public static function setApiKey(string $key, ?string $context = null): void
    {
        self::setAppKey($key, $context);
    }

    /**
     * Get the API key (alias for getAppKey for common naming).
     *
     * @param string|null $context The context to get the key from. If null, uses active context.
     * @return string|null The API key or null if not set.
     */
    public static function getApiKey(?string $context = null): ?string
    {
        return self::getAppKey($context);
    }

    /**
     * Set the OAuth client ID.
     *
     * @param string $clientId The OAuth client ID.
     * @param string|null $context The context to set the client ID for. If null, uses active context.
     */
    public static function setOAuthClientId(string $clientId, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthClientId = $clientId;
        }

        self::$contexts[$context]['oauth_client_id'] = $clientId;
    }

    /**
     * Get the OAuth client ID.
     *
     * @param string|null $context The context to get the client ID from. If null, uses active context.
     * @return string|null The OAuth client ID or null if not set.
     */
    public static function getOAuthClientId(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_client_id'])) {
            return self::$contexts[$context]['oauth_client_id'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthClientId;
        }

        return null;
    }

    /**
     * Set the OAuth client secret.
     *
     * @param string $clientSecret The OAuth client secret.
     * @param string|null $context The context to set the client secret for. If null, uses active context.
     */
    public static function setOAuthClientSecret(string $clientSecret, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthClientSecret = $clientSecret;
        }

        self::$contexts[$context]['oauth_client_secret'] = $clientSecret;
    }

    /**
     * Get the OAuth client secret.
     *
     * @param string|null $context The context to get the client secret from. If null, uses active context.
     * @return string|null The OAuth client secret or null if not set.
     */
    public static function getOAuthClientSecret(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_client_secret'])) {
            return self::$contexts[$context]['oauth_client_secret'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthClientSecret;
        }

        return null;
    }

    /**
     * Set the OAuth redirect URI.
     *
     * @param string $redirectUri The OAuth redirect URI.
     * @param string|null $context The context to set the redirect URI for. If null, uses active context.
     */
    public static function setOAuthRedirectUri(string $redirectUri, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthRedirectUri = $redirectUri;
        }

        self::$contexts[$context]['oauth_redirect_uri'] = $redirectUri;
    }

    /**
     * Get the OAuth redirect URI.
     *
     * @param string|null $context The context to get the redirect URI from. If null, uses active context.
     * @return string|null The OAuth redirect URI or null if not set.
     */
    public static function getOAuthRedirectUri(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_redirect_uri'])) {
            return self::$contexts[$context]['oauth_redirect_uri'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthRedirectUri;
        }

        return null;
    }

    /**
     * Set the OAuth access token.
     *
     * @param string $token The OAuth access token.
     * @param string|null $context The context to set the token for. If null, uses active context.
     */
    public static function setOAuthToken(string $token, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthToken = $token;
        }

        self::$contexts[$context]['oauth_token'] = $token;
    }

    /**
     * Get the OAuth access token.
     *
     * @param string|null $context The context to get the token from. If null, uses active context.
     * @return string|null The OAuth access token or null if not set.
     */
    public static function getOAuthToken(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_token'])) {
            return self::$contexts[$context]['oauth_token'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthToken;
        }

        return null;
    }

    /**
     * Set the OAuth refresh token.
     *
     * @param string $refreshToken The OAuth refresh token.
     * @param string|null $context The context to set the refresh token for. If null, uses active context.
     */
    public static function setOAuthRefreshToken(string $refreshToken, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthRefreshToken = $refreshToken;
        }

        self::$contexts[$context]['oauth_refresh_token'] = $refreshToken;
    }

    /**
     * Get the OAuth refresh token.
     *
     * @param string|null $context The context to get the refresh token from. If null, uses active context.
     * @return string|null The OAuth refresh token or null if not set.
     */
    public static function getOAuthRefreshToken(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_refresh_token'])) {
            return self::$contexts[$context]['oauth_refresh_token'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthRefreshToken;
        }

        return null;
    }

    /**
     * Set the OAuth token expiration timestamp.
     *
     * @param int $expiresAt The Unix timestamp when the token expires.
     * @param string|null $context The context to set the expiration for. If null, uses active context.
     */
    public static function setOAuthExpiresAt(int $expiresAt, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthExpiresAt = $expiresAt;
        }

        self::$contexts[$context]['oauth_expires_at'] = $expiresAt;
    }

    /**
     * Get the OAuth token expiration timestamp.
     *
     * @param string|null $context The context to get the expiration from. If null, uses active context.
     * @return int|null The Unix timestamp when the token expires or null if not set.
     */
    public static function getOAuthExpiresAt(?string $context = null): ?int
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_expires_at'])) {
            return self::$contexts[$context]['oauth_expires_at'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthExpiresAt;
        }

        return null;
    }

    /**
     * Set the OAuth user ID.
     *
     * @param int $userId The OAuth user ID.
     * @param string|null $context The context to set the user ID for. If null, uses active context.
     */
    public static function setOAuthUserId(int $userId, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthUserId = $userId;
        }

        self::$contexts[$context]['oauth_user_id'] = $userId;
    }

    /**
     * Get the OAuth user ID.
     *
     * @param string|null $context The context to get the user ID from. If null, uses active context.
     * @return int|null The OAuth user ID or null if not set.
     */
    public static function getOAuthUserId(?string $context = null): ?int
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_user_id'])) {
            return self::$contexts[$context]['oauth_user_id'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthUserId;
        }

        return null;
    }

    /**
     * Set the OAuth user name.
     *
     * @param string $userName The OAuth user name.
     * @param string|null $context The context to set the user name for. If null, uses active context.
     */
    public static function setOAuthUserName(string $userName, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthUserName = $userName;
        }

        self::$contexts[$context]['oauth_user_name'] = $userName;
    }

    /**
     * Get the OAuth user name.
     *
     * @param string|null $context The context to get the user name from. If null, uses active context.
     * @return string|null The OAuth user name or null if not set.
     */
    public static function getOAuthUserName(?string $context = null): ?string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_user_name'])) {
            return self::$contexts[$context]['oauth_user_name'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthUserName;
        }

        return null;
    }

    /**
     * Set the OAuth scopes.
     *
     * @param array<string> $scopes The OAuth scopes.
     * @param string|null $context The context to set the scopes for. If null, uses active context.
     */
    public static function setOAuthScopes(array $scopes, ?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthScopes = $scopes;
        }

        self::$contexts[$context]['oauth_scopes'] = $scopes;
    }

    /**
     * Get the OAuth scopes.
     *
     * @param string|null $context The context to get the scopes from. If null, uses active context.
     * @return array<string>|null The OAuth scopes or null if not set.
     */
    public static function getOAuthScopes(?string $context = null): ?array
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['oauth_scopes'])) {
            return self::$contexts[$context]['oauth_scopes'];
        }

        if ($context === self::$activeContext) {
            return self::$oauthScopes;
        }

        return null;
    }

    /**
     * Switch to OAuth authentication mode.
     *
     * @param string|null $context The context to switch to OAuth for. If null, uses active context.
     */
    public static function useOAuth(?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$authMode = 'oauth';
        }

        self::$contexts[$context]['auth_mode'] = 'oauth';
    }

    /**
     * Switch to API key authentication mode.
     *
     * @param string|null $context The context to switch to API key for. If null, uses active context.
     */
    public static function useApiKey(?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$authMode = 'api_key';
        }

        self::$contexts[$context]['auth_mode'] = 'api_key';
    }

    /**
     * Get the current authentication mode.
     *
     * @param string|null $context The context to get the auth mode from. If null, uses active context.
     * @return string The authentication mode ('api_key' or 'oauth').
     */
    public static function getAuthMode(?string $context = null): string
    {
        $context ??= self::$activeContext;

        if (isset(self::$contexts[$context]['auth_mode'])) {
            return self::$contexts[$context]['auth_mode'];
        }

        if ($context === self::$activeContext) {
            return self::$authMode;
        }

        return 'api_key';
    }

    /**
     * Check if the OAuth token is expired or about to expire.
     *
     * @param string|null $context The context to check. If null, uses active context.
     * @return bool True if token is expired or will expire within 5 minutes.
     */
    public static function isOAuthTokenExpired(?string $context = null): bool
    {
        $context ??= self::$activeContext;
        $expiresAt = self::getOAuthExpiresAt($context);

        if ($expiresAt === null) {
            return false; // No expiration set, assume valid
        }

        // Check if expired or will expire within 5 minutes (300 seconds)
        return $expiresAt <= time() + 300;
    }

    /**
     * Clear all OAuth tokens and related data.
     *
     * @param string|null $context The context to clear OAuth data for. If null, uses active context.
     */
    public static function clearOAuthTokens(?string $context = null): void
    {
        $context ??= self::$activeContext;

        if ($context === self::$activeContext) {
            self::$oauthToken = null;
            self::$oauthRefreshToken = null;
            self::$oauthExpiresAt = null;
            self::$oauthUserId = null;
            self::$oauthUserName = null;
            self::$oauthScopes = null;
        }

        unset(
            self::$contexts[$context]['oauth_token'],
            self::$contexts[$context]['oauth_refresh_token'],
            self::$contexts[$context]['oauth_expires_at'],
            self::$contexts[$context]['oauth_user_id'],
            self::$contexts[$context]['oauth_user_name'],
            self::$contexts[$context]['oauth_scopes']
        );
    }
}
