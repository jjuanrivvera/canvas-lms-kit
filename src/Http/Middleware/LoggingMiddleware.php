<?php

declare(strict_types=1);

namespace CanvasLMS\Http\Middleware;

use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Middleware for logging HTTP requests and responses with sensitive data sanitization
 */
class LoggingMiddleware extends AbstractMiddleware
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     * @param array<string, mixed> $config
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'logging';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'log_requests' => true,
            'log_responses' => true,
            'log_errors' => true,
            'log_timing' => true,
            'log_level' => LogLevel::INFO,
            'error_log_level' => LogLevel::ERROR,
            'sanitize_fields' => ['password', 'token', 'api_key', 'secret', 'authorization'],
            'max_body_length' => 1000, // Maximum body length to log
        ];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$this->getConfig('enabled', true)) {
                    return $handler($request, $options);
                }

                $start = microtime(true);
                $requestId = uniqid('req_');

                // Log the request
                if ($this->getConfig('log_requests', true)) {
                    $this->logRequest($request, $requestId, $options);
                }

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($start, $requestId) {
                        $elapsed = microtime(true) - $start;

                        // Log the response
                        if ($this->getConfig('log_responses', true)) {
                            $this->logResponse($response, $requestId, $elapsed);
                        }

                        return $response;
                    },
                    function ($reason) use ($request, $start, $requestId) {
                        $elapsed = microtime(true) - $start;

                        // Log the error
                        if ($this->getConfig('log_errors', true)) {
                            $this->logError($reason, $request, $requestId, $elapsed);
                        }

                        return Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    /**
     * Log the HTTP request
     *
     * @param RequestInterface $request
     * @param string $requestId
     * @param array<string, mixed> $options
     *
     * @return void
     */
    private function logRequest(RequestInterface $request, string $requestId, array $options): void
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
        ];

        // Add body if present and not too large
        $body = (string) $request->getBody();
        if ($body) {
            $maxLength = $this->getConfig('max_body_length', 1000);
            if (strlen($body) > $maxLength) {
                $context['body'] = substr($body, 0, $maxLength) . '... (truncated)';
                $context['body_length'] = strlen($body);
            } else {
                $context['body'] = $this->sanitizeBody($body);
            }
        }

        // Add request options if relevant
        if (isset($options['rate_limit_bucket'])) {
            $context['rate_limit_bucket'] = $options['rate_limit_bucket'];
        }

        $this->logger->log(
            $this->getConfig('log_level', LogLevel::INFO),
            'HTTP Request: {method} {uri}',
            $context
        );
    }

    /**
     * Log the HTTP response
     *
     * @param ResponseInterface $response
     * @param string $requestId
     * @param float $elapsed
     *
     * @return void
     */
    private function logResponse(ResponseInterface $response, string $requestId, float $elapsed): void
    {
        $context = [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'reason_phrase' => $response->getReasonPhrase(),
            'headers' => $this->sanitizeHeaders($response->getHeaders()),
        ];

        if ($this->getConfig('log_timing', true)) {
            $context['elapsed_time'] = round($elapsed * 1000, 2) . 'ms';
        }

        // Add Canvas-specific headers if present
        if ($response->hasHeader('X-Rate-Limit-Remaining')) {
            $context['rate_limit_remaining'] = $response->getHeaderLine('X-Rate-Limit-Remaining');
        }
        if ($response->hasHeader('X-Request-Cost')) {
            $context['request_cost'] = $response->getHeaderLine('X-Request-Cost');
        }

        // Add body for non-success responses
        if ($response->getStatusCode() >= 400) {
            $body = (string) $response->getBody();
            if ($body) {
                $maxLength = $this->getConfig('max_body_length', 1000);
                if (strlen($body) > $maxLength) {
                    $context['body'] = substr($body, 0, $maxLength) . '... (truncated)';
                } else {
                    $context['body'] = $body;
                }
            }
            // Rewind the body stream
            if ($response->getBody()->isSeekable()) {
                $response->getBody()->seek(0);
            }
        }

        $level = $response->getStatusCode() >= 400
            ? $this->getConfig('error_log_level', LogLevel::ERROR)
            : $this->getConfig('log_level', LogLevel::INFO);

        $this->logger->log(
            $level,
            'HTTP Response: {status_code} {reason_phrase}',
            $context
        );
    }

    /**
     * Log request error
     *
     * @param mixed $reason
     * @param RequestInterface $request
     * @param string $requestId
     * @param float $elapsed
     *
     * @return void
     */
    private function logError($reason, RequestInterface $request, string $requestId, float $elapsed): void
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'error_type' => get_class($reason),
            'error_message' => $reason instanceof \Exception ? $reason->getMessage() : (string) $reason,
        ];

        if ($this->getConfig('log_timing', true)) {
            $context['elapsed_time'] = round($elapsed * 1000, 2) . 'ms';
        }

        // Add response details if available
        if ($reason instanceof \GuzzleHttp\Exception\RequestException && $reason->hasResponse()) {
            $response = $reason->getResponse();
            $context['status_code'] = $response->getStatusCode();
            $context['headers'] = $this->sanitizeHeaders($response->getHeaders());

            $body = (string) $response->getBody();
            if ($body) {
                $maxLength = $this->getConfig('max_body_length', 1000);
                if (strlen($body) > $maxLength) {
                    $context['response_body'] = substr($body, 0, $maxLength) . '... (truncated)';
                } else {
                    $context['response_body'] = $body;
                }
            }
        }

        $this->logger->log(
            $this->getConfig('error_log_level', LogLevel::ERROR),
            'HTTP Error: {error_type} - {error_message}',
            $context
        );
    }

    /**
     * Sanitize headers to remove sensitive information
     *
     * @param array<string, array<string>> $headers
     *
     * @return array<string, array<string>>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitizeFields = array_map('strtolower', $this->getConfig('sanitize_fields', []));
        $sanitized = [];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower($name);

            // Check if this header should be sanitized
            $shouldSanitize = false;
            foreach ($sanitizeFields as $field) {
                if (strpos($lowerName, $field) !== false) {
                    $shouldSanitize = true;
                    break;
                }
            }

            if ($shouldSanitize) {
                $sanitized[$name] = ['***REDACTED***'];
            } else {
                $sanitized[$name] = $values;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize body content to remove sensitive information
     *
     * @param string $body
     *
     * @return string
     */
    private function sanitizeBody(string $body): string
    {
        // Try to decode as JSON
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $sanitized = $this->sanitizeArray($decoded);

            return json_encode($sanitized, JSON_PRETTY_PRINT);
        }

        // For non-JSON bodies, do basic pattern matching
        $patterns = [];
        $sanitizeFields = $this->getConfig('sanitize_fields', []);

        foreach ($sanitizeFields as $field) {
            // Look for field=value patterns
            $patterns[] = '/(' . preg_quote($field, '/') . '\s*[=:]\s*)([^\s&,}"\']+)/i';
        }

        foreach ($patterns as $pattern) {
            $body = preg_replace($pattern, '$1***REDACTED***', $body);
        }

        return $body;
    }

    /**
     * Recursively sanitize an array
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        $sanitizeFields = array_map('strtolower', $this->getConfig('sanitize_fields', []));
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if this field should be sanitized
            $shouldSanitize = false;
            foreach ($sanitizeFields as $field) {
                if (strpos($lowerKey, $field) !== false) {
                    $shouldSanitize = true;
                    break;
                }
            }

            if ($shouldSanitize) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
