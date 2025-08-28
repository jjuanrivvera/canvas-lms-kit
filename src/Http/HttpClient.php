<?php

namespace CanvasLMS\Http;

use CanvasLMS\Auth\OAuth;
use CanvasLMS\Config;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use CanvasLMS\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\MissingApiKeyException;
use CanvasLMS\Exceptions\MissingBaseUrlException;
use CanvasLMS\Exceptions\MissingOAuthTokenException;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * HTTP client for Canvas LMS API communication.
 *
 * Handles all HTTP requests to the Canvas API including authentication via Bearer tokens,
 * response pagination, error handling, and middleware support. Implements the HttpClientInterface
 * to provide a consistent API for making requests to Canvas LMS endpoints.
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var HandlerStack
     */
    private HandlerStack $handlerStack;

    /**
     * @var array<string, MiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * @param ClientInterface|null $client
     * @param LoggerInterface|null $logger
     * @param array<MiddlewareInterface> $middleware
     */
    public function __construct(
        ClientInterface $client = null,
        LoggerInterface $logger = null,
        array $middleware = []
    ) {
        $this->logger = $logger ?? new \Psr\Log\NullLogger();
        $this->handlerStack = HandlerStack::create();

        // If no middleware provided and no client provided, add sensible defaults
        if (empty($middleware) && $client === null) {
            $middleware = $this->getDefaultMiddleware();
        }

        // Register middleware
        foreach ($middleware as $mw) {
            $this->addMiddleware($mw);
        }

        // If a client is provided, use it directly (for backward compatibility)
        // Otherwise create a new client with our handler stack
        if ($client !== null) {
            $this->client = $client;
        } else {
            $this->client = new \GuzzleHttp\Client(['handler' => $this->handlerStack]);
        }
    }

    /**
     * Get request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * Post request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * Put request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function put(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $url, $options);
    }

    /**
     * Patch request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function patch(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * delete request
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Get request with pagination support
     * @param string $url
     * @param mixed[] $options
     * @return PaginatedResponse
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     */
    public function getPaginated(string $url, array $options = []): PaginatedResponse
    {
        $response = $this->get($url, $options);
        return new PaginatedResponse($response, $this);
    }

    /**
     * Make an HTTP request with pagination support
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @return PaginatedResponse
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     * @throws CanvasApiException
     */
    public function requestPaginated(string $method, string $url, array $options = []): PaginatedResponse
    {
        $response = $this->request($method, $url, $options);
        return new PaginatedResponse($response, $this);
    }

    /**
     * Add middleware to the stack
     *
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[$middleware->getName()] = $middleware;
        $this->handlerStack->push($middleware(), $middleware->getName());
    }

    /**
     * Remove middleware from the stack
     *
     * @param string $name
     * @return void
     */
    public function removeMiddleware(string $name): void
    {
        if (isset($this->middleware[$name])) {
            unset($this->middleware[$name]);
            $this->handlerStack->remove($name);
        }
    }

    /**
     * Get registered middleware
     *
     * @return array<string, MiddlewareInterface>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get default middleware stack
     *
     * @return array<MiddlewareInterface>
     */
    private function getDefaultMiddleware(): array
    {
        $middleware = [];

        // Add retry middleware with sensible defaults
        $middleware[] = new Middleware\RetryMiddleware([
            'max_attempts' => 3,
            'delay' => 1000,
            'multiplier' => 2,
            'jitter' => true,
        ]);

        // Add rate limit middleware with Canvas defaults
        $middleware[] = new Middleware\RateLimitMiddleware([
            'enabled' => true,
            'wait_on_limit' => true,
            'max_wait_time' => 30,
        ]);

        // Add logging middleware if a logger is available
        if (!($this->logger instanceof \Psr\Log\NullLogger)) {
            $middleware[] = new Middleware\LoggingMiddleware($this->logger, [
                'log_level' => \Psr\Log\LogLevel::INFO,
                'log_errors' => true,
                'log_timing' => true,
            ]);
        }

        return $middleware;
    }

    /**
     * Make an HTTP request
     * @param string $method
     * @param string $url
     * @param mixed[] $options
     * @return ResponseInterface
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     * @throws CanvasApiException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $requestOptions = $this->prepareDefaultOptions($url, $options);

            return $this->client->request($method, $url, $requestOptions);
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $errors = [];
            if ($e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                if ($body) {
                    $decoded = json_decode($body, true);
                    if (is_array($decoded) && isset($decoded['errors'])) {
                        $errors = $decoded['errors'];
                    }
                }
            }
            throw new CanvasApiException($e->getMessage(), $e->getCode(), $errors);
        } catch (GuzzleException $e) {
            throw new CanvasApiException($e->getMessage(), $e->getCode(), []);
        }
    }

    /**
     * @param string $url
     * @param mixed[] $options
     * @return mixed[]
     * @throws MissingApiKeyException
     * @throws MissingOAuthTokenException
     * @throws MissingBaseUrlException
     */
    private function prepareDefaultOptions(string &$url, array $options): array
    {
        // Check if this is an OAuth2-specific endpoint that shouldn't have /api/v1/ added
        // Only OAuth2 token endpoints should bypass the API prefix
        $isOAuthEndpoint = preg_match('#^/?login/oauth2/#', $url);

        // Skip authentication if explicitly requested (e.g., for OAuth token exchange)
        if (!isset($options['skipAuth']) || $options['skipAuth'] !== true) {
            $authMode = Config::getAuthMode();

            // Handle authentication based on mode
            if ($authMode === 'oauth') {
                $token = $this->getValidOAuthToken();
                if (!$token) {
                    throw new MissingOAuthTokenException();
                }
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            } else {
                // Default to API key authentication
                $appKey = Config::getAppKey();
                if (!$appKey) {
                    throw new MissingApiKeyException();
                }
                $options['headers']['Authorization'] = 'Bearer ' . $appKey;
            }
        }

        // Remove skipAuth from options as it's not a valid HTTP client option
        unset($options['skipAuth']);

        $baseUrl = Config::getBaseUrl();
        if (!$baseUrl) {
            throw new MissingBaseUrlException();
        }

        // Only add /api/v1/ prefix for non-OAuth endpoints
        if (!$isOAuthEndpoint) {
            $fullUrl = $baseUrl .
                'api/' .
                rtrim(Config::getApiVersion(), '/') .
                '/' .
                ltrim($url, '/');
            $url = $fullUrl;
        } else {
            // For OAuth endpoints, just combine base URL with path
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        // Add masquerading parameter if active
        $masqueradeUserId = Config::getMasqueradeUserId();
        if ($masqueradeUserId !== null) {
            if (!isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['as_user_id'] = $masqueradeUserId;
        }

        return $options;
    }

    /**
     * Get a valid OAuth token, refreshing if necessary.
     *
     * @return string|null The valid OAuth token or null if not available
     */
    private function getValidOAuthToken(): ?string
    {
        // Check if token is expired and refresh if needed
        if (Config::isOAuthTokenExpired()) {
            try {
                OAuth::refreshToken();
            } catch (\Exception $e) {
                // If refresh fails, return null to trigger MissingOAuthTokenException
                return null;
            }
        }

        return Config::getOAuthToken();
    }

    /**
     * Make a raw request to any Canvas URL
     *
     * This method allows direct API calls to arbitrary Canvas URLs, useful for:
     * - Following pagination URLs returned by Canvas
     * - Calling custom or undocumented endpoints
     * - Handling webhook callbacks with URLs
     * - Following URLs provided in Canvas API responses
     * - Accessing beta/experimental endpoints
     *
     * @param string $url Full URL or relative path
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     * @param mixed[] $options Guzzle request options
     * @return ResponseInterface
     * @throws CanvasApiException
     * @throws MissingApiKeyException
     * @throws MissingBaseUrlException
     * @throws MissingOAuthTokenException
     */
    public function rawRequest(string $url, string $method = 'GET', array $options = []): ResponseInterface
    {
        try {
            $requestUrl = $url;
            $requestOptions = $this->prepareRawRequestOptions($requestUrl, $options);

            return $this->client->request($method, $requestUrl, $requestOptions);
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            $errors = [];
            if ($e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
                if ($body) {
                    $decoded = json_decode($body, true);
                    if (is_array($decoded) && isset($decoded['errors'])) {
                        $errors = $decoded['errors'];
                    }
                }
            }
            throw new CanvasApiException($e->getMessage(), $e->getCode(), $errors);
        } catch (GuzzleException $e) {
            throw new CanvasApiException($e->getMessage(), $e->getCode(), []);
        }
    }

    /**
     * Prepare options for raw requests
     *
     * @param string $url The URL to process (passed by reference, may be modified)
     * @param mixed[] $options The request options
     * @return mixed[]
     * @throws MissingApiKeyException
     * @throws MissingOAuthTokenException
     * @throws MissingBaseUrlException
     * @throws CanvasApiException
     */
    private function prepareRawRequestOptions(string &$url, array $options): array
    {
        // Detect if URL is absolute (contains scheme) or relative
        $isAbsoluteUrl = filter_var($url, FILTER_VALIDATE_URL) !== false;

        if ($isAbsoluteUrl) {
            // Validate that the URL points to the configured Canvas domain
            $this->validateCanvasUrl($url);
        } else {
            // For relative URLs, prepend base URL (without API version)
            $baseUrl = Config::getBaseUrl();
            if (!$baseUrl) {
                throw new MissingBaseUrlException();
            }

            // If the relative URL already starts with /api/v1, use it as is
            // Otherwise, just append to base URL
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        // Add authentication headers unless explicitly skipped
        if (!isset($options['skipAuth']) || $options['skipAuth'] !== true) {
            $authMode = Config::getAuthMode();

            if ($authMode === 'oauth') {
                $token = $this->getValidOAuthToken();
                if (!$token) {
                    throw new MissingOAuthTokenException();
                }
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            } else {
                $appKey = Config::getAppKey();
                if (!$appKey) {
                    throw new MissingApiKeyException();
                }
                $options['headers']['Authorization'] = 'Bearer ' . $appKey;
            }
        }

        // Remove skipAuth from options as it's not a valid HTTP client option
        unset($options['skipAuth']);

        // Add masquerading parameter if active
        $masqueradeUserId = Config::getMasqueradeUserId();
        if ($masqueradeUserId !== null) {
            if (!isset($options['query'])) {
                $options['query'] = [];
            }
            $options['query']['as_user_id'] = $masqueradeUserId;
        }

        return $options;
    }

    /**
     * Validate that a URL points to the configured Canvas domain
     *
     * @param string $url The URL to validate
     * @return void
     * @throws CanvasApiException
     */
    private function validateCanvasUrl(string $url): void
    {
        $baseUrl = Config::getBaseUrl();
        if (!$baseUrl) {
            throw new MissingBaseUrlException();
        }

        $parsedUrl = parse_url($url);
        $parsedBaseUrl = parse_url($baseUrl);

        if (!isset($parsedUrl['host']) || !isset($parsedBaseUrl['host'])) {
            throw new CanvasApiException('Invalid URL format', 0, ['error' => 'Could not parse URL']);
        }

        // Allow the same host or subdomains of the configured host
        if ($parsedUrl['host'] !== $parsedBaseUrl['host']) {
            // Check if it's a subdomain of the configured host
            $baseHost = $parsedBaseUrl['host'];
            $requestHost = $parsedUrl['host'];

            // Remove potential port numbers for comparison
            $baseHost = explode(':', $baseHost)[0];
            $requestHost = explode(':', $requestHost)[0];

            if (!str_ends_with($requestHost, '.' . $baseHost) && $requestHost !== $baseHost) {
                throw new CanvasApiException(
                    'URL domain does not match configured Canvas instance',
                    0,
                    ['error' => 'Invalid domain: ' . $parsedUrl['host']]
                );
            }
        }

        // Validate scheme (only allow HTTP for localhost, HTTPS otherwise)
        $scheme = $parsedUrl['scheme'] ?? '';
        $isLocalhost = in_array($parsedUrl['host'], ['localhost', '127.0.0.1', '::1']);

        if (!$isLocalhost && $scheme !== 'https') {
            throw new CanvasApiException(
                'Only HTTPS URLs are allowed for production Canvas instances',
                0,
                ['error' => 'Invalid scheme: ' . $scheme]
            );
        }
    }
}
