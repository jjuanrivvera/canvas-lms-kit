<?php

namespace CanvasLMS;

use CanvasLMS\Exceptions\ConfigurationException;

class Config
{
    /**
     * @var array<string, array{
     *     api_version?: string,
     *     timeout?: int,
     *     app_key?: string,
     *     base_url?: string,
     *     account_id?: int
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
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL provided for base URL.");
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

        // Return default if nothing else found
        return 1;
    }

    /**
     * Set the active context for configuration.
     *
     * @param string $context The context name to make active.
     */
    public static function setContext(string $context): void
    {
        self::$activeContext = $context;
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
     */
    public static function autoDetect(?string $context = null): void
    {
        $context ??= self::$activeContext;

        // Check for Canvas-specific environment variables
        if (isset($_ENV['CANVAS_API_KEY'])) {
            self::setAppKey($_ENV['CANVAS_API_KEY'], $context);
        }

        if (isset($_ENV['CANVAS_BASE_URL'])) {
            self::setBaseUrl($_ENV['CANVAS_BASE_URL'], $context);
        }

        if (isset($_ENV['CANVAS_ACCOUNT_ID'])) {
            self::setAccountId((int) $_ENV['CANVAS_ACCOUNT_ID'], $context);
        }

        if (isset($_ENV['CANVAS_API_VERSION'])) {
            self::setApiVersion($_ENV['CANVAS_API_VERSION'], $context);
        }

        if (isset($_ENV['CANVAS_TIMEOUT'])) {
            self::setTimeout((int) $_ENV['CANVAS_TIMEOUT'], $context);
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
}
