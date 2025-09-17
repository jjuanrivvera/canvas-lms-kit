<?php

declare(strict_types=1);

namespace CanvasLMS\Pagination;

/**
 * PaginationResult Class
 *
 * Wrapper class for paginated API responses that provides access to data and pagination metadata.
 * This class encapsulates the results of a paginated API call along with navigation links and
 * metadata extracted from Canvas API Link headers.
 *
 * Usage:
 * ```php
 * $result = Course::fetchAllPaginated(['per_page' => 50]);
 * $courses = $result->getData();
 *
 * if ($result->hasNext()) {
 *     $nextResult = $result->getNext();
 * }
 *
 * echo "Page {$result->getCurrentPage()} of {$result->getTotalPages()}";
 * ```
 *
 * @package CanvasLMS\Pagination
 */
class PaginationResult
{
    /**
     * The actual data from the API response
     *
     * @var mixed[]
     */
    private array $data;

    /**
     * URL for the next page of results
     *
     * @var string|null
     */
    private ?string $nextUrl;

    /**
     * URL for the previous page of results
     *
     * @var string|null
     */
    private ?string $prevUrl;

    /**
     * URL for the first page of results
     *
     * @var string|null
     */
    private ?string $firstUrl;

    /**
     * URL for the last page of results
     *
     * @var string|null
     */
    private ?string $lastUrl;

    /**
     * URL for the current page of results
     *
     * @var string|null
     */
    private ?string $currentUrl;

    /**
     * Current page number
     *
     * @var int
     */
    private int $currentPage;

    /**
     * Total number of pages (if determinable)
     *
     * @var int|null
     */
    private ?int $totalPages;

    /**
     * Number of items per page
     *
     * @var int|null
     */
    private ?int $perPage;

    /**
     * PaginationResult constructor
     *
     * @param mixed[] $data The API response data
     * @param string[] $navigationLinks Associative array of navigation URLs
     * @param int $currentPage Current page number
     * @param int|null $totalPages Total number of pages
     * @param int|null $perPage Items per page
     */
    public function __construct(
        array $data,
        array $navigationLinks = [],
        int $currentPage = 1,
        ?int $totalPages = null,
        ?int $perPage = null
    ) {
        $this->data = $data;
        $this->nextUrl = $navigationLinks['next'] ?? null;
        $this->prevUrl = $navigationLinks['prev'] ?? null;
        $this->firstUrl = $navigationLinks['first'] ?? null;
        $this->lastUrl = $navigationLinks['last'] ?? null;
        $this->currentUrl = $navigationLinks['current'] ?? null;
        $this->currentPage = $currentPage;
        $this->totalPages = $totalPages;
        $this->perPage = $perPage;
    }

    /**
     * Create PaginationResult from Link header
     *
     * @param mixed[] $data The API response data
     * @param string $linkHeader The Link header string
     *
     * @return self
     */
    public static function fromLinkHeader(array $data, string $linkHeader): self
    {
        $parser = new LinkHeaderParser();
        $links = $parser->parse($linkHeader);

        // Extract current page from current URL or default to 1
        $currentPage = 1;
        if (isset($links['current'])) {
            $currentPage = $parser->extractPageNumber($links['current']) ?? 1;
        }

        // Extract total pages from last URL if available
        $totalPages = null;
        if (isset($links['last'])) {
            $totalPages = $parser->extractPageNumber($links['last']);
        }

        // Extract per_page from any available URL
        $perPage = null;
        foreach ($links as $url) {
            $perPage = $parser->extractPerPage($url);
            if ($perPage !== null) {
                break;
            }
        }

        return new self($data, $links, $currentPage, $totalPages, $perPage);
    }

    /**
     * Get the data from the API response
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the URL for the next page
     *
     * @return string|null
     */
    public function getNextUrl(): ?string
    {
        return $this->nextUrl;
    }

    /**
     * Get the URL for the previous page
     *
     * @return string|null
     */
    public function getPrevUrl(): ?string
    {
        return $this->prevUrl;
    }

    /**
     * Get the URL for the first page
     *
     * @return string|null
     */
    public function getFirstUrl(): ?string
    {
        return $this->firstUrl;
    }

    /**
     * Get the URL for the last page
     *
     * @return string|null
     */
    public function getLastUrl(): ?string
    {
        return $this->lastUrl;
    }

    /**
     * Get the URL for the current page
     *
     * @return string|null
     */
    public function getCurrentUrl(): ?string
    {
        return $this->currentUrl;
    }

    /**
     * Get the current page number
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get the total number of pages
     *
     * @return int|null
     */
    public function getTotalPages(): ?int
    {
        return $this->totalPages;
    }

    /**
     * Get the number of items per page
     *
     * @return int|null
     */
    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    /**
     * Check if there are more pages available
     *
     * @return bool
     */
    public function hasMore(): bool
    {
        return $this->hasNext();
    }

    /**
     * Check if there is a next page
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->nextUrl !== null;
    }

    /**
     * Check if there is a previous page
     *
     * @return bool
     */
    public function hasPrev(): bool
    {
        return $this->prevUrl !== null;
    }

    /**
     * Check if this is the first page
     *
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    /**
     * Check if this is the last page
     *
     * @return bool
     */
    public function isLastPage(): bool
    {
        if ($this->totalPages === null) {
            return !$this->hasNext();
        }

        return $this->currentPage === $this->totalPages;
    }

    /**
     * Get the number of items in the current page
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->data);
    }

    /**
     * Check if the current page is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Get pagination summary string
     *
     * @return string
     */
    public function getSummary(): string
    {
        $count = $this->getCount();
        $page = $this->getCurrentPage();

        if ($this->totalPages !== null) {
            return "Page {$page} of {$this->totalPages} ({$count} items)";
        }

        return "Page {$page} ({$count} items)";
    }

    /**
     * Get all navigation URLs
     *
     * @return string[]
     */
    public function getNavigationUrls(): array
    {
        return array_filter([
            'first' => $this->firstUrl,
            'prev' => $this->prevUrl,
            'current' => $this->currentUrl,
            'next' => $this->nextUrl,
            'last' => $this->lastUrl,
        ]);
    }

    /**
     * Convert to array representation
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'current_page' => $this->currentPage,
                'total_pages' => $this->totalPages,
                'per_page' => $this->perPage,
                'count' => $this->getCount(),
                'has_next' => $this->hasNext(),
                'has_prev' => $this->hasPrev(),
                'is_first_page' => $this->isFirstPage(),
                'is_last_page' => $this->isLastPage(),
                'navigation_urls' => $this->getNavigationUrls(),
            ],
        ];
    }
}
