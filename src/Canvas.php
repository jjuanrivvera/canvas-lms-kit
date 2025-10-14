<?php

declare(strict_types=1);

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
     *
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     *
     * @return mixed Decoded JSON response or raw response based on content type
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
     *
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
     *
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
     * This method recursively flattens nested arrays into Canvas API's expected
     * multipart format. It handles:
     * - Scalar values: converted to strings
     * - Resources: preserved for file uploads
     * - Sequential arrays: appended with [] suffix (e.g., field[]=value1, field[]=value2)
     * - Associative arrays: nested with [key] notation (e.g., field[subfield]=value)
     * - Deeply nested structures: recursively processed to arbitrary depth
     *
     * @example
     * ```php
     * // Sequential array
     * ['colors' => ['red', 'blue']]
     * // Produces: colors[]=red, colors[]=blue
     *
     * // Associative array
     * ['user' => ['name' => 'John', 'age' => 30]]
     * // Produces: user[name]=John, user[age]=30
     *
     * // Deeply nested
     * ['appointment_group' => ['new_appointments' => [['2024-01-01'], ['2024-01-02']]]]
     * // Produces: appointment_group[new_appointments][0][]=2024-01-01,
     * //           appointment_group[new_appointments][1][]=2024-01-02
     * ```
     *
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    protected static function prepareMultipartData(array $data): array
    {
        $multipart = [];
        self::flattenArray($data, '', $multipart);

        return $multipart;
    }

    /**
     * Recursively flatten nested arrays into multipart format
     *
     * This helper method is called by prepareMultipartData() to recursively
     * process nested data structures and convert them into the flat multipart
     * format expected by Canvas API endpoints.
     *
     * Sequential arrays of scalars get [] suffix: colors[]=red, colors[]=blue
     * Sequential arrays of arrays get numeric indices: items[0][name]=A, items[1][name]=B
     *
     * @param mixed $data The data to flatten (can be scalar, array, or resource)
     * @param string $prefix The current field name prefix (e.g., "user[address]")
     * @param mixed[] $result Reference to the result array being built
     *
     * @return void
     */
    private static function flattenArray(mixed $data, string $prefix, array &$result): void
    {
        // Handle resources (file uploads) - preserve as-is
        if (is_resource($data)) {
            $result[] = [
                'name' => $prefix,
                'contents' => $data,
            ];

            return;
        }

        // Handle non-array values (scalars: string, int, float, bool, null)
        if (!is_array($data)) {
            $result[] = [
                'name' => $prefix,
                'contents' => (string) $data,
            ];

            return;
        }

        // Handle arrays - distinguish between sequential and associative
        $isSequential = array_is_list($data);

        foreach ($data as $key => $value) {
            if ($isSequential) {
                // Sequential array: check if value is scalar or array
                if (is_array($value) && !empty($value)) {
                    // Sequential array of arrays: use numeric index
                    // Example: items[0][name]=A, items[1][name]=B
                    $fieldName = $prefix === '' ? (string) $key : "{$prefix}[{$key}]";
                } else {
                    // Sequential array of scalars: use [] suffix
                    // Example: colors[]=red, colors[]=blue
                    $fieldName = $prefix . '[]';
                }
            } else {
                // Associative array: use [key] suffix
                // Example: user[name]=John, user[age]=30
                $fieldName = $prefix === '' ? (string) $key : "{$prefix}[{$key}]";
            }

            // Recurse for nested structures
            self::flattenArray($value, $fieldName, $result);
        }
    }
}
