<?php

declare(strict_types=1);

namespace CanvasLMS\Cache\Strategies;

use Psr\Http\Message\RequestInterface;

/**
 * Determines appropriate TTL (Time To Live) values for different Canvas API resources.
 *
 * Provides intelligent TTL defaults based on resource type and allows
 * per-request overrides.
 */
class TtlStrategy
{
    /**
     * @var array<string, int> TTL rules for different endpoint patterns
     */
    private array $rules = [
        // Static resources - 1 hour
        '/courses$' => 3600,
        '/accounts' => 3600,
        '/terms' => 3600,
        '/roles' => 3600,

        // Semi-static resources - 15 minutes
        '/enrollments' => 900,
        '/sections' => 900,
        '/users$' => 900,
        '/groups$' => 900,

        // Dynamic resources - 5 minutes
        '/assignments' => 300,
        '/modules' => 300,
        '/pages' => 300,
        '/discussions' => 300,
        '/announcements' => 300,
        '/files' => 300,
        '/folders' => 300,

        // Real-time resources - 1 minute or no cache
        '/submissions' => 60,
        '/grades' => 0,
        '/quiz_submissions' => 0,
        '/progress' => 0,
        '/live_assessments' => 0,

        // Specific course resources
        '/courses/\d+$' => 900,  // Individual course - 15 minutes
        '/courses/\d+/students' => 300,  // Course students - 5 minutes
        '/courses/\d+/activity_stream' => 60,  // Activity stream - 1 minute
    ];

    /**
     * @var int Default TTL in seconds
     */
    private int $defaultTtl;

    /**
     * Constructor.
     *
     * @param int $defaultTtl Default TTL in seconds (default: 300)
     */
    public function __construct(int $defaultTtl = 300)
    {
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Get the TTL for a request.
     *
     * @param RequestInterface     $request The HTTP request
     * @param array<string, mixed> $options Request options
     *
     * @return int The TTL in seconds (0 = no cache)
     */
    public function getTtl(RequestInterface $request, array $options = []): int
    {
        // Check for explicit TTL in options
        if (isset($options['cache_ttl'])) {
            return (int) $options['cache_ttl'];
        }

        // Check if caching is disabled
        if (isset($options['cache']) && $options['cache'] === false) {
            return 0;
        }

        // Get TTL based on URL pattern
        $path = $request->getUri()->getPath();

        return $this->getTtlForPath($path);
    }

    /**
     * Get the TTL for a specific path.
     *
     * @param string $path The URL path
     *
     * @return int The TTL in seconds
     */
    private function getTtlForPath(string $path): int
    {
        foreach ($this->rules as $pattern => $ttl) {
            if (preg_match('#' . $pattern . '#i', $path)) {
                return $ttl;
            }
        }

        return $this->defaultTtl;
    }

    /**
     * Add or update a TTL rule.
     *
     * @param string $pattern The URL pattern (regex)
     * @param int    $ttl     The TTL in seconds
     *
     * @return void
     */
    public function addRule(string $pattern, int $ttl): void
    {
        $this->rules[$pattern] = $ttl;
    }

    /**
     * Remove a TTL rule.
     *
     * @param string $pattern The URL pattern to remove
     *
     * @return void
     */
    public function removeRule(string $pattern): void
    {
        unset($this->rules[$pattern]);
    }

    /**
     * Get all TTL rules.
     *
     * @return array<string, int> The TTL rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Set the default TTL.
     *
     * @param int $ttl The default TTL in seconds
     *
     * @return void
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Get the default TTL.
     *
     * @return int The default TTL in seconds
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}
