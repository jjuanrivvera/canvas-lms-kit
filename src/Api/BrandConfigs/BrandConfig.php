<?php

declare(strict_types=1);

namespace CanvasLMS\Api\BrandConfigs;

use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;

/**
 * BrandConfig API
 *
 * Provides access to brand configuration variables for the current domain.
 * This is a read-only API with a single endpoint that returns brand variables.
 *
 * Canvas API Documentation: https://canvas.instructure.com/doc/api/brand_configs.html
 *
 * Note: This class does not extend AbstractBaseApi as it only provides
 * read-only access to brand variables with no CRUD operations.
 */
class BrandConfig
{
    /**
     * Get brand config variables for the current domain
     *
     * Retrieves all brand variables used by this account. The endpoint redirects
     * to a static JSON file containing the brand configuration. No authentication
     * is required for this endpoint.
     *
     * API Endpoint: GET /api/v1/brand_variables
     *
     * @return array<string, mixed> The brand configuration variables including colors, fonts, and logos
     * @throws CanvasApiException If the API request fails
     *
     * @example
     * ```php
     * $brandVariables = BrandConfig::getBrandVariables();
     * echo $brandVariables['primary_color'];
     * echo $brandVariables['font_family'];
     * ```
     */
    public static function getBrandVariables(): array
    {
        $httpClient = new HttpClient();

        try {
            // This endpoint redirects to a static JSON file
            // The HTTP client should handle the redirect automatically
            $response = $httpClient->get('/brand_variables');

            // HttpClient returns ResponseInterface, get body content
            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CanvasApiException(
                    'Failed to decode brand variables JSON: ' . json_last_error_msg()
                );
            }

            return $decoded;
        } catch (\Exception $e) {
            if ($e instanceof CanvasApiException) {
                throw $e;
            }

            throw new CanvasApiException(
                'Failed to retrieve brand variables: ' . $e->getMessage(),
                0,
                []
            );
        }
    }
}
