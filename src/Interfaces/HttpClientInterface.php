<?php

namespace CanvasLMS\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    /**
     * Get request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function get(string $url, array $options = []): ResponseInterface;

    /**
     * Post request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function post(string $url, array $options = []): ResponseInterface;

    /**
     * Put request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function put(string $url, array $options = []): ResponseInterface;

    /**
     * Patch request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function patch(string $url, array $options = []): ResponseInterface;

    /**
     * Delete request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function delete(string $url, array $options = []): ResponseInterface;

    /**
     * Make a request
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
