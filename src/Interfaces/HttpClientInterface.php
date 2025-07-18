<?php

namespace CanvasLMS\Interfaces;

use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    /**
     * Get request
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function get(string $url, array $options = []): ResponseInterface;

    /**
     * Post request
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function post(string $url, array $options = []): ResponseInterface;

    /**
     * Put request
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function put(string $url, array $options = []): ResponseInterface;

    /**
     * Patch request
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function patch(string $url, array $options = []): ResponseInterface;

    /**
     * Delete request
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function delete(string $url, array $options = []): ResponseInterface;

    /**
     * Make a request
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;

    /**
     * Get request with pagination support
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return PaginatedResponse
     */
    public function getPaginated(string $url, array $options = []): PaginatedResponse;

    /**
     * Make a request with pagination support
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @throws CanvasApiException
     * @return PaginatedResponse
     */
    public function requestPaginated(string $method, string $url, array $options = []): PaginatedResponse;
}
