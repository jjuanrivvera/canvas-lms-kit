<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for retrying failed requests with exponential backoff
 */
class RetryMiddleware extends AbstractMiddleware
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'retry';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultConfig(): array
    {
        return [
            'max_attempts' => 3,
            'delay' => 1000, // Initial delay in milliseconds
            'multiplier' => 2, // Exponential backoff multiplier
            'max_delay' => 16000, // Maximum delay in milliseconds
            'jitter' => true, // Add random jitter to delays
            'retry_on_status' => [500, 502, 503, 504, 403], // 403 for Canvas rate limits
            'retry_on_timeout' => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $options['retry_attempt'] = $options['retry_attempt'] ?? 0;

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($request, $handler, $options) {
                        if ($this->shouldRetry($options['retry_attempt'], $response, null)) {
                            return $this->doRetry($request, $handler, $options);
                        }

                        return $response;
                    },
                    function ($reason) use ($request, $handler, $options) {
                        if ($this->shouldRetry($options['retry_attempt'], null, $reason)) {
                            return $this->doRetry($request, $handler, $options);
                        }

                        return Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    /**
     * Determine if the request should be retried
     *
     * @param int $attempt
     * @param ResponseInterface|null $response
     * @param mixed $reason
     *
     * @return bool
     */
    private function shouldRetry(int $attempt, ?ResponseInterface $response, $reason): bool
    {
        if ($attempt >= $this->getConfig('max_attempts')) {
            return false;
        }

        // Check response status codes
        if ($response !== null) {
            $statusCode = $response->getStatusCode();
            $retryStatuses = $this->getConfig('retry_on_status', []);

            // Special handling for Canvas 403 rate limit
            if ($statusCode === 403 && $this->isCanvasRateLimit($response)) {
                return true;
            }

            return in_array($statusCode, $retryStatuses, true);
        }

        // Check for connection/timeout errors
        if ($reason instanceof ConnectException && $this->getConfig('retry_on_timeout')) {
            return true;
        }

        if ($reason instanceof RequestException) {
            $response = $reason->getResponse();
            if ($response !== null) {
                return $this->shouldRetry($attempt, $response, null);
            }
        }

        return false;
    }

    /**
     * Check if a 403 response is a Canvas rate limit error
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function isCanvasRateLimit(ResponseInterface $response): bool
    {
        // Canvas returns X-Rate-Limit-Remaining header when rate limited
        if ($response->hasHeader('X-Rate-Limit-Remaining')) {
            $remaining = (int) $response->getHeaderLine('X-Rate-Limit-Remaining');

            return $remaining <= 0;
        }

        // Check response body for rate limit message
        $body = (string) $response->getBody();
        // Rewind the body stream so it can be read again
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->seek(0);
        }

        return strpos($body, 'Rate Limit Exceeded') !== false;
    }

    /**
     * Perform the retry
     *
     * @param RequestInterface $request
     * @param callable $handler
     * @param array<string, mixed> $options
     *
     * @return PromiseInterface
     */
    private function doRetry(RequestInterface $request, callable $handler, array $options): PromiseInterface
    {
        $options['retry_attempt']++;
        $delay = $this->calculateDelay($options['retry_attempt']);

        // Sleep for the calculated delay
        usleep($delay * 1000);

        // Reset the request body if needed
        if ($request->getBody()->isSeekable()) {
            $request->getBody()->seek(0);
        }

        return $handler($request, $options);
    }

    /**
     * Calculate the delay before the next retry
     *
     * @param int $attempt
     *
     * @return int Delay in milliseconds
     */
    private function calculateDelay(int $attempt): int
    {
        $delay = $this->getConfig('delay', 1000);
        $multiplier = $this->getConfig('multiplier', 2);
        $maxDelay = $this->getConfig('max_delay', 16000);

        // Calculate exponential backoff
        $calculatedDelay = $delay * pow($multiplier, $attempt - 1);

        // Cap at max delay
        $calculatedDelay = min($calculatedDelay, $maxDelay);

        // Add jitter if enabled
        if ($this->getConfig('jitter', true)) {
            // Add 0-25% random jitter
            $jitter = $calculatedDelay * (rand(0, 25) / 100);
            $calculatedDelay += $jitter;
        }

        return (int) $calculatedDelay;
    }
}
