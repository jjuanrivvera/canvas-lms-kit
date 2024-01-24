<?php

namespace CanvasLMS;

class Config
{
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
     */
    public static function setAppKey(string $key): void
    {
        self::$appKey = $key;
    }

    /**
     * Get the application key.
     *
     * @return string|null The application key or null if not set.
     */
    public static function getAppKey(): ?string
    {
        return self::$appKey;
    }

    /**
     * Set the base URL for API requests.
     *
     * @param string $url The base URL.
     * @throws \InvalidArgumentException If the URL is not valid.
     */
    public static function setBaseUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL provided for base URL.");
        }
        self::$baseUrl = rtrim($url, '/') . '/'; // Ensure trailing slash
    }

    /**
     * Get the base URL for API requests.
     *
     * @return string|null The base URL or null if not set.
     */
    public static function getBaseUrl(): ?string
    {
        return self::$baseUrl;
    }

    /**
     * Set the API version.
     *
     * @param string $version The API version.
     */
    public static function setApiVersion(string $version): void
    {
        self::$apiVersion = $version;
    }

    /**
     * Get the API version.
     *
     * @return string|null The API version or null if not set.
     */
    public static function getApiVersion(): ?string
    {
        return self::$apiVersion;
    }

    /**
     * Set the timeout for API requests.
     *
     * @param int $timeout The timeout in seconds.
     */
    public static function setTimeout(int $timeout): void
    {
        self::$timeout = $timeout;
    }

    /**
     * Get the timeout for API requests.
     *
     * @return int|null The timeout in seconds or null if not set.
     */
    public static function getTimeout(): ?int
    {
        return self::$timeout;
    }

    /**
     * Set the account ID.
     *
     * @param int $accountId The account ID.
     */
    public static function setAccountId(int $accountId): void
    {
        self::$accountId = $accountId;
    }

    /**
     * Get the account ID.
     *
     * @return int The account ID.
     */
    public static function getAccountId(): int
    {
        return self::$accountId;
    }
}