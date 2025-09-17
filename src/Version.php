<?php

declare(strict_types=1);

namespace CanvasLMS;

/**
 * Version information for Canvas LMS Kit
 */
class Version
{
    /**
     * Current version of Canvas LMS Kit
     *
     * @var string
     */
    public const VERSION = '1.5.2';

    /**
     * Release date of current version
     *
     * @var string
     */
    public const RELEASE_DATE = '2025-09-15';

    /**
     * Minimum PHP version required
     *
     * @var string
     */
    public const MIN_PHP_VERSION = '8.1.0';

    /**
     * Canvas API version compatibility
     *
     * @var string
     */
    public const CANVAS_API_VERSION = 'v1';

    /**
     * Get the current version
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get the release date
     *
     * @return string
     */
    public static function getReleaseDate(): string
    {
        return self::RELEASE_DATE;
    }

    /**
     * Get full version string with all details
     *
     * @return string
     */
    public static function getFullVersion(): string
    {
        return sprintf(
            'Canvas LMS Kit %s (%s) - PHP %s+ - Canvas API %s',
            self::VERSION,
            self::RELEASE_DATE,
            self::MIN_PHP_VERSION,
            self::CANVAS_API_VERSION
        );
    }

    /**
     * Check if current PHP version is supported
     *
     * @return bool
     */
    public static function isPhpVersionSupported(): bool
    {
        return version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');
    }

    /**
     * Get minimum PHP version required
     *
     * @return string
     */
    public static function getMinPhpVersion(): string
    {
        return self::MIN_PHP_VERSION;
    }

    /**
     * Check if a version is pre-release
     *
     * @param string|null $version Version to check (defaults to current)
     *
     * @return bool
     */
    public static function isPreRelease(?string $version = null): bool
    {
        $version = $version ?? self::VERSION;

        return str_contains($version, '-alpha')
            || str_contains($version, '-beta')
            || str_contains($version, '-rc');
    }
}
