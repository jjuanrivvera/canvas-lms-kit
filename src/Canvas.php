<?php

namespace CanvasLMS;

use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Exceptions\MissingApiKeyException;
use CanvasLMS\Exceptions\MissingBaseUrlException;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Canvas facade class for making raw API calls to arbitrary Canvas URLs.
 *
 * This class provides a clean, intuitive interface for making direct API calls
 * to Canvas URLs. It's particularly useful for:
 * - Following pagination URLs returned by Canvas
 * - Calling custom or undocumented endpoints
 * - Processing webhook callbacks with embedded URLs
 * - Following URLs in API responses (e.g., file downloads)
 * - Accessing beta or experimental Canvas features
 *
 * @example
 * ```php
 * // Following pagination
 * $nextPage = Canvas::get($courses->getNextUrl());
 *
 * // Custom endpoint
 * $analytics = Canvas::get('/api/v1/custom/analytics');
 *
 * // Create resource
 * $result = Canvas::post('/api/v1/courses/123/custom_resources', [
 *     'name' => 'My Resource'
 * ]);
 * ```
 */
class Canvas
{
    /**
     * @var HttpClientInterface|null The HTTP client instance
     */
    protected static ?HttpClientInterface $httpClient = null;

    /**
     * Get the HTTP client instance, creating one if necessary
     *
     * @return HttpClientInterface
     */
    protected static function getHttpClient(): HttpClientInterface
    {
        if (self::$httpClient === null) {
            self::$httpClient = new HttpClient();
        }
        return self::$httpClient;
    }

    /**
     * Set a custom HTTP client instance
     *
     * @param HttpClientInterface|null $client Pass null to reset to default
     * @return void
     */
    public static function setHttpClient(?HttpClientInterface $client): void
    {
        self::$httpClient = $client;
    }

    /**
     * Make a GET request to a Canvas URL
     *
     * @param string $url Full URL or relative path
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function get(string $url, array $options = []): mixed
    {
        return self::request($url, 'GET', $options);
    }

    /**
     * Make a POST request to a Canvas URL
     *
     * @param string $url Full URL or relative path
     * @param mixed[]|null $data Data to send in request body
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function post(string $url, ?array $data = null, array $options = []): mixed
    {
        if ($data !== null) {
            // Check if data should be sent as JSON or multipart
            if (self::isMultipartData($data)) {
                $options['multipart'] = self::prepareMultipartData($data);
            } else {
                $options['json'] = $data;
            }
        }
        return self::request($url, 'POST', $options);
    }

    /**
     * Make a PUT request to a Canvas URL
     *
     * @param string $url Full URL or relative path
     * @param mixed[]|null $data Data to send in request body
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function put(string $url, ?array $data = null, array $options = []): mixed
    {
        if ($data !== null) {
            // Check if data should be sent as JSON or multipart
            if (self::isMultipartData($data)) {
                $options['multipart'] = self::prepareMultipartData($data);
            } else {
                $options['json'] = $data;
            }
        }
        return self::request($url, 'PUT', $options);
    }

    /**
     * Make a DELETE request to a Canvas URL
     *
     * @param string $url Full URL or relative path
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function delete(string $url, array $options = []): mixed
    {
        return self::request($url, 'DELETE', $options);
    }

    /**
     * Make a PATCH request to a Canvas URL
     *
     * @param string $url Full URL or relative path
     * @param mixed[]|null $data Data to send in request body
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function patch(string $url, ?array $data = null, array $options = []): mixed
    {
        if ($data !== null) {
            // Check if data should be sent as JSON or multipart
            if (self::isMultipartData($data)) {
                $options['multipart'] = self::prepareMultipartData($data);
            } else {
                $options['json'] = $data;
            }
        }
        return self::request($url, 'PATCH', $options);
    }

    /**
     * Make a request to a Canvas URL with any HTTP method
     *
     * @param string $url Full URL or relative path
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     * @param mixed[] $options Optional Guzzle request options
     * @return mixed Decoded JSON response or raw response based on content type
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public static function request(string $url, string $method = 'GET', array $options = []): mixed
    {
        $client = self::getHttpClient();
        $response = $client->rawRequest($url, $method, $options);

        return self::parseResponse($response);
    }

    /**
     * Parse the response based on content type
     *
     * @param ResponseInterface $response
     * @return mixed
     */
    protected static function parseResponse(ResponseInterface $response): mixed
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $body = $response->getBody()->getContents();

        // If the response is JSON, decode it
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($body, true);

            // Check for JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Return raw body if JSON decode fails
                return $body;
            }

            return $decoded;
        }

        // Return raw body for non-JSON responses
        return $body;
    }

    /**
     * Check if data should be sent as multipart
     *
     * Canvas API uses multipart for certain types of data, particularly
     * when arrays are nested or when file uploads are involved.
     *
     * @param mixed[] $data
     * @return bool
     */
    protected static function isMultipartData(array $data): bool
    {
        foreach ($data as $value) {
            // If any value is an array or resource, use multipart
            if (is_array($value) || is_resource($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare data for multipart encoding
     *
     * @param mixed[] $data
     * @return mixed[]
     */
    protected static function prepareMultipartData(array $data): array
    {
        $multipart = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle nested arrays (like assignment[name])
                foreach ($value as $subKey => $subValue) {
                    $multipart[] = [
                        'name' => "{$key}[{$subKey}]",
                        'contents' => (string) $subValue
                    ];
                }
            } elseif (is_resource($value)) {
                // Handle file uploads
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            } else {
                // Handle simple values
                $multipart[] = [
                    'name' => $key,
                    'contents' => (string) $value
                ];
            }
        }

        return $multipart;
    }
}
