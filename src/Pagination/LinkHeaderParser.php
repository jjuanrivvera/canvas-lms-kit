<?php

namespace CanvasLMS\Pagination;

/**
 * LinkHeaderParser Class
 *
 * Utility class for parsing RFC 5988 Link headers from Canvas API responses.
 * Canvas API uses Link headers to provide pagination information in the format:
 *
 * Link: <https://canvas.example.com/api/v1/courses?page=1&per_page=50>; rel="current",
 *       <https://canvas.example.com/api/v1/courses?page=2&per_page=50>; rel="next",
 *       <https://canvas.example.com/api/v1/courses?page=1&per_page=50>; rel="first",
 *       <https://canvas.example.com/api/v1/courses?page=10&per_page=50>; rel="last"
 *
 * Usage:
 * ```php
 * $parser = new LinkHeaderParser();
 * $links = $parser->parse($response->getHeader('Link')[0]);
 * $nextUrl = $links['next'] ?? null;
 * ```
 *
 * @package CanvasLMS\Pagination
 */
class LinkHeaderParser
{
    /**
     * Parse Link header string into associative array
     *
     * @param string $linkHeader The Link header string from HTTP response
     * @return string[] Associative array with rel values as keys and URLs as values
     */
    public function parse(string $linkHeader): array
    {
        $links = [];

        if (empty($linkHeader)) {
            return $links;
        }

        // Split by comma to get individual link entries
        $linkEntries = explode(',', $linkHeader);

        foreach ($linkEntries as $linkEntry) {
            $linkEntry = trim($linkEntry);

            // Parse each link entry: <URL>; rel="relation"
            if (preg_match('/<([^>]+)>;\s*rel="([^"]+)"/', $linkEntry, $matches)) {
                $url = trim($matches[1]);
                $rel = trim($matches[2]);

                if (!empty($url) && !empty($rel)) {
                    $links[$rel] = $url;
                }
            }
        }

        return $links;
    }

    /**
     * Extract specific relation URL from Link header
     *
     * @param string $linkHeader The Link header string
     * @param string $relation The relation to extract (next, prev, first, last, current)
     * @return string|null The URL for the relation or null if not found
     */
    public function extractRelation(string $linkHeader, string $relation): ?string
    {
        $links = $this->parse($linkHeader);
        return $links[$relation] ?? null;
    }

    /**
     * Check if Link header contains a specific relation
     *
     * @param string $linkHeader The Link header string
     * @param string $relation The relation to check for
     * @return bool True if relation exists, false otherwise
     */
    public function hasRelation(string $linkHeader, string $relation): bool
    {
        $links = $this->parse($linkHeader);
        return isset($links[$relation]);
    }

    /**
     * Get all available relations from Link header
     *
     * @param string $linkHeader The Link header string
     * @return string[] Array of relation names
     */
    public function getRelations(string $linkHeader): array
    {
        $links = $this->parse($linkHeader);
        return array_keys($links);
    }

    /**
     * Extract page number from a paginated URL
     *
     * @param string $url The paginated URL
     * @return int|null The page number or null if not found
     */
    public function extractPageNumber(string $url): ?int
    {
        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['query'])) {
            return null;
        }

        parse_str($parsedUrl['query'], $queryParams);

        if (isset($queryParams['page']) && is_numeric($queryParams['page'])) {
            return (int) $queryParams['page'];
        }

        return null;
    }

    /**
     * Extract per_page parameter from a paginated URL
     *
     * @param string $url The paginated URL
     * @return int|null The per_page value or null if not found
     */
    public function extractPerPage(string $url): ?int
    {
        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['query'])) {
            return null;
        }

        parse_str($parsedUrl['query'], $queryParams);

        if (isset($queryParams['per_page']) && is_numeric($queryParams['per_page'])) {
            return (int) $queryParams['per_page'];
        }

        return null;
    }
}
