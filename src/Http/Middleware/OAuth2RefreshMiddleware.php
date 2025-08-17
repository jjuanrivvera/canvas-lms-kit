<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

use CanvasLMS\Auth\OAuth;
use CanvasLMS\Config;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for automatic OAuth token refresh on 401 responses
 */
class OAuth2RefreshMiddleware extends AbstractMiddleware
{
    /**
     * @var array{auto_refresh: bool, retry_on_401: bool}
     */
    protected array $config = [
        'auto_refresh' => true,
        'retry_on_401' => true,
    ];

    /**
     * Get the middleware name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'oauth2_refresh';
    }

    /**
     * Configure the middleware.
     *
     * @param array{auto_refresh?: bool, retry_on_401?: bool} $config
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Create the middleware handler.
     *
     * @return callable
     */
    public function __invoke(): callable
    {
        return function ($handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                // Only apply to OAuth mode
                if (Config::getAuthMode() !== 'oauth') {
                    return $handler($request, $options);
                }

                // Check token before request if auto_refresh is enabled
                if ($this->config['auto_refresh'] && Config::isOAuthTokenExpired()) {
                    try {
                        OAuth::refreshToken();

                        // Update request with new token
                        $request = $request->withHeader(
                            'Authorization',
                            'Bearer ' . Config::getOAuthToken()
                        );
                    } catch (\Exception) {
                        // Let the request proceed with potentially expired token
                        // It will fail with 401 and we can retry
                    }
                }

                // Execute request
                $promise = $handler($request, $options);

                // Handle 401 responses if retry_on_401 is enabled
                if ($this->config['retry_on_401']) {
                    return $promise->then(
                        function (ResponseInterface $response) {
                            return $response;
                        },
                        function ($reason) use ($handler, $request, $options) {
                            if ($reason instanceof RequestException) {
                                $response = $reason->getResponse();
                                if ($response && $response->getStatusCode() === 401) {
                                    // Try refreshing token
                                    try {
                                        OAuth::refreshToken();

                                        // Update request with new token
                                        $request = $request->withHeader(
                                            'Authorization',
                                            'Bearer ' . Config::getOAuthToken()
                                        );

                                        // Retry request once
                                        return $handler($request, $options);
                                    } catch (\Exception) {
                                        // Refresh failed, throw original error
                                        throw $reason;
                                    }
                                }
                            }

                            throw $reason;
                        }
                    );
                }

                return $promise;
            };
        };
    }
}
