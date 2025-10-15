<?php

declare(strict_types=1);

namespace CanvasLMS\Pagination;

use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * PaginatedResponse Class
 *
 * Wrapper class for HTTP responses that provides pagination functionality.
 * This class encapsulates an HTTP response and provides methods to navigate
 * through paginated results using Canvas API Link headers.
 *
 * The response body is cached on first read to prevent PSR-7 stream exhaustion,
 * making it safe to call getBody() and getJsonData() multiple times.
 *
 * Usage:
 * ```php
 * $response = $httpClient->get('/api/v1/courses');
 * $paginatedResponse = new PaginatedResponse($response, $httpClient);
 *
 * $result = $paginatedResponse->toPaginationResult($courses);
 *
 * if ($paginatedResponse->hasNext()) {
 *     $nextResponse = $paginatedResponse->getNext();
 * }
 * ```
 *
 * @package CanvasLMS\Pagination
 */
class PaginatedResponse
{
    /**
     * The HTTP response
     *
     * @var ResponseInterface
     */
    private ResponseInterface $response;

    /**
     * HTTP client for making additional requests
     *
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    /**
     * Link header parser instance
     *
     * @var LinkHeaderParser
     */
    private LinkHeaderParser $linkParser;

    /**
     * Parsed Link header navigation URLs
     *
     * @var string[]
     */
    private array $navigationUrls;

    /**
     * Cached Link header string
     *
     * @var string|null
     */
    private ?string $linkHeader = null;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Cached response body content
     *
     * This cache prevents multiple reads of the PSR-7 stream, which can only
     * be read once unless rewound (and not all streams support rewinding).
     *
     * @var string|null
     */
    private ?string $bodyCache = null;

    /**
     * PaginatedResponse constructor
     *
     * @param ResponseInterface $response The HTTP response
     * @param HttpClientInterface $httpClient HTTP client for additional requests
     */
    public function __construct(ResponseInterface $response, HttpClientInterface $httpClient)
    {
        $this->response = $response;
        $this->httpClient = $httpClient;
        $this->linkParser = new LinkHeaderParser();
        $this->navigationUrls = $this->parseLinkHeader();
        $this->logger = Config::getLogger();

        // Log pagination information if Link header exists
        if ($this->linkHeader) {
            $this->logger->debug('Pagination: Response contains Link header', [
                'has_next' => $this->hasNext(),
                'has_prev' => $this->hasPrev(),
                'current_page' => $this->getCurrentPage(),
                'per_page' => $this->getPerPage(),
            ]);
        }
    }

    /**
     * Get the underlying HTTP response
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the response body as string
     *
     * This method caches the body content on first read to prevent PSR-7 stream
     * exhaustion. Subsequent calls return the cached value, making it safe to
     * call this method multiple times or intermix calls with getJsonData().
     *
     * @return string
     */
    public function getBody(): string
    {
        if ($this->bodyCache === null) {
            $this->bodyCache = $this->response->getBody()->getContents();
        }

        return $this->bodyCache;
    }

    /**
     * Get the response body as decoded JSON array
     *
     * @return mixed[]
     */
    public function getJsonData(): array
    {
        $body = $this->getBody();
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Get the Link header from the response
     *
     * @return string
     */
    public function getLinkHeader(): string
    {
        if ($this->linkHeader === null) {
            $linkHeaders = $this->response->getHeader('Link');
            $this->linkHeader = $linkHeaders[0] ?? '';
        }

        return $this->linkHeader;
    }

    /**
     * Parse Link header and extract navigation URLs
     *
     * @return string[]
     */
    private function parseLinkHeader(): array
    {
        $linkHeader = $this->getLinkHeader();

        return $this->linkParser->parse($linkHeader);
    }

    /**
     * Get all navigation URLs
     *
     * @return string[]
     */
    public function getNavigationUrls(): array
    {
        return $this->navigationUrls;
    }

    /**
     * Get URL for specific relation
     *
     * @param string $relation The relation (next, prev, first, last, current)
     *
     * @return string|null
     */
    public function getUrl(string $relation): ?string
    {
        return $this->navigationUrls[$relation] ?? null;
    }

    /**
     * Get the next page URL
     *
     * @return string|null
     */
    public function getNextUrl(): ?string
    {
        return $this->getUrl('next');
    }

    /**
     * Get the previous page URL
     *
     * @return string|null
     */
    public function getPrevUrl(): ?string
    {
        return $this->getUrl('prev');
    }

    /**
     * Get the first page URL
     *
     * @return string|null
     */
    public function getFirstUrl(): ?string
    {
        return $this->getUrl('first');
    }

    /**
     * Get the last page URL
     *
     * @return string|null
     */
    public function getLastUrl(): ?string
    {
        return $this->getUrl('last');
    }

    /**
     * Get the current page URL
     *
     * @return string|null
     */
    public function getCurrentUrl(): ?string
    {
        return $this->getUrl('current');
    }

    /**
     * Check if there is a next page
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->getNextUrl() !== null;
    }

    /**
     * Check if there is a previous page
     *
     * @return bool
     */
    public function hasPrev(): bool
    {
        return $this->getPrevUrl() !== null;
    }

    /**
     * Check if relation exists in Link header
     *
     * @param string $relation The relation to check
     *
     * @return bool
     */
    public function hasRelation(string $relation): bool
    {
        return isset($this->navigationUrls[$relation]);
    }

    /**
     * Get the current page number
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        $currentUrl = $this->getCurrentUrl();
        if ($currentUrl) {
            return $this->linkParser->extractPageNumber($currentUrl) ?? 1;
        }

        return 1;
    }

    /**
     * Get the total number of pages
     *
     * @return int|null
     */
    public function getTotalPages(): ?int
    {
        $lastUrl = $this->getLastUrl();
        if ($lastUrl) {
            return $this->linkParser->extractPageNumber($lastUrl);
        }

        return null;
    }

    /**
     * Get the per_page parameter
     *
     * @return int|null
     */
    public function getPerPage(): ?int
    {
        // Try to extract from any available URL
        foreach ($this->navigationUrls as $url) {
            $perPage = $this->linkParser->extractPerPage($url);
            if ($perPage !== null) {
                return $perPage;
            }
        }

        return null;
    }

    /**
     * Fetch the next page
     *
     * @return self|null
     */
    public function getNext(): ?self
    {
        $nextUrl = $this->getNextUrl();
        if (!$nextUrl) {
            return null;
        }

        return $this->fetchUrl($nextUrl);
    }

    /**
     * Fetch the previous page
     *
     * @return self|null
     */
    public function getPrev(): ?self
    {
        $prevUrl = $this->getPrevUrl();
        if (!$prevUrl) {
            return null;
        }

        return $this->fetchUrl($prevUrl);
    }

    /**
     * Fetch the first page
     *
     * @return self|null
     */
    public function getFirst(): ?self
    {
        $firstUrl = $this->getFirstUrl();
        if (!$firstUrl) {
            return null;
        }

        return $this->fetchUrl($firstUrl);
    }

    /**
     * Fetch the last page
     *
     * @return self|null
     */
    public function getLast(): ?self
    {
        $lastUrl = $this->getLastUrl();
        if (!$lastUrl) {
            return null;
        }

        return $this->fetchUrl($lastUrl);
    }

    /**
     * Fetch a specific page by URL
     *
     * @param string $url The URL to fetch
     *
     * @return self|null
     */
    private function fetchUrl(string $url): ?self
    {
        try {
            // Extract path from full URL for the HTTP client
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';

            // Remove API base path if present
            $path = preg_replace('/^\/api\/v\d+/', '', $path) ?? '';

            // Add query parameters
            $options = [];
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                $options['query'] = $queryParams;
            }

            $this->logger->debug('Pagination: Fetching page', [
                'path' => $path,
                'has_query' => isset($parsedUrl['query']),
            ]);

            $startTime = microtime(true);
            $response = $this->httpClient->get($path, $options);
            $duration = microtime(true) - $startTime;

            $this->logger->info('Pagination: Page fetched successfully', [
                'path' => $path,
                'duration_ms' => round($duration * 1000, 2),
            ]);

            return new self($response, $this->httpClient);
        } catch (\Exception $e) {
            $this->logger->error('Pagination: Failed to fetch page', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert to PaginationResult
     *
     * @param mixed[] $data The decoded response data
     *
     * @return PaginationResult
     */
    public function toPaginationResult(array $data): PaginationResult
    {
        return PaginationResult::fromLinkHeader($data, $this->getLinkHeader());
    }

    /**
     * Get all data from all pages (new simplified method)
     *
     * @return mixed[] Array containing all data from all pages
     */
    public function all(): array
    {
        return $this->fetchAllPages();
    }

    /**
     * Fetch all pages starting from current page
     *
     * @return mixed[] Array containing all data from all pages
     *
     * @deprecated Use all() instead
     */
    public function fetchAllPages(): array
    {
        $this->logger->info('Pagination: Starting to fetch all pages');

        $allData = [];
        $currentResponse = $this;
        $pageCount = 0;
        $startTime = microtime(true);

        do {
            $pageCount++;
            $data = $currentResponse->getJsonData();
            $itemCount = count((array) $data);

            $this->logger->debug('Pagination: Processing page', [
                'page_number' => $pageCount,
                'items_on_page' => $itemCount,
                'total_items_so_far' => count($allData) + $itemCount,
            ]);

            $allData = array_merge($allData, $data);

            $currentResponse = $currentResponse->getNext();
        } while ($currentResponse !== null);

        $duration = microtime(true) - $startTime;

        $this->logger->info('Pagination: Completed fetching all pages', [
            'total_pages' => $pageCount,
            'total_items' => count($allData),
            'duration_s' => round($duration, 2),
            'avg_time_per_page_ms' => round(($duration / $pageCount) * 1000, 2),
        ]);

        return $allData;
    }

    /**
     * Get pagination summary information
     *
     * @return mixed[]
     */
    public function getPaginationInfo(): array
    {
        return [
            'current_page' => $this->getCurrentPage(),
            'total_pages' => $this->getTotalPages(),
            'per_page' => $this->getPerPage(),
            'has_next' => $this->hasNext(),
            'has_prev' => $this->hasPrev(),
            'navigation_urls' => $this->getNavigationUrls(),
        ];
    }
}
