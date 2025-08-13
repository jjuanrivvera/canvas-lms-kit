<?php

namespace CanvasLMS\Http;

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
     * @throws MissingBaseUrlException
     */
    private function prepareDefaultOptions(string &$url, array $options): array
    {
        $appKey = Config::getAppKey();
        if (!$appKey) {
            throw new MissingApiKeyException();
        }

        $baseUrl = Config::getBaseUrl();
        if (!$baseUrl) {
            throw new MissingBaseUrlException();
        }

        $fullUrl = $baseUrl .
            'api/' .
            rtrim(Config::getApiVersion(), '/') .
            '/' .
            ltrim($url, '/');

        $options['headers']['Authorization'] = 'Bearer ' . $appKey;
        $url = $fullUrl;

        return $options;
    }
}
