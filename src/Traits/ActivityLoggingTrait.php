<?php

namespace CanvasLMS\Traits;

use CanvasLMS\Config;
use Psr\Log\LoggerInterface;

/**
 * Trait for standardized activity logging across Canvas LMS API classes.
 *
 * This trait provides consistent logging methods for API operations,
 * including activity tracking, performance metrics, and error handling.
 */
trait ActivityLoggingTrait
{
    /**
     * Log an API activity with enriched context.
     *
     * @param string $action The action being performed (e.g., 'fetch', 'create', 'update', 'delete')
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logActivity(string $action, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'timestamp' => time(),
            'class' => static::class,
            'action' => $action,
            'context' => Config::getContext(),
        ], $context);

        $logger->info("Canvas API Activity: {$action}", $enrichedContext);
    }

    /**
     * Log performance metrics for an operation.
     *
     * @param string $operation The operation being measured
     * @param float $duration The duration in seconds
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'duration_s' => round($duration, 3),
            'class' => static::class,
            'context' => Config::getContext(),
        ], $context);

        // Log as info if under 1 second, warning if 1-5 seconds, error if over 5 seconds
        if ($duration < 1.0) {
            $logger->info(
                "Performance: {$operation} completed in {$enrichedContext['duration_ms']}ms",
                $enrichedContext
            );
        } elseif ($duration < 5.0) {
            $logger->warning(
                "Performance: {$operation} took {$enrichedContext['duration_s']}s",
                $enrichedContext
            );
        } else {
            $logger->error(
                "Performance: {$operation} exceeded 5s threshold ({$enrichedContext['duration_s']}s)",
                $enrichedContext
            );
        }
    }

    /**
     * Log an API error with context.
     *
     * @param string $operation The operation that failed
     * @param \Throwable $exception The exception that was thrown
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logError(string $operation, \Throwable $exception, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'operation' => $operation,
            'error_class' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'class' => static::class,
            'context' => Config::getContext(),
        ], $context);

        // Add stack trace for non-production environments (can be configured)
        if (Config::getLogger() !== null) {
            $enrichedContext['stack_trace'] = $exception->getTraceAsString();
        }

        $logger->error("API Error in {$operation}: {$exception->getMessage()}", $enrichedContext);
    }

    /**
     * Log a successful API operation.
     *
     * @param string $operation The operation that succeeded
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logSuccess(string $operation, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'operation' => $operation,
            'status' => 'success',
            'class' => static::class,
            'context' => Config::getContext(),
        ], $context);

        $logger->info("API Success: {$operation} completed successfully", $enrichedContext);
    }

    /**
     * Log pagination information.
     *
     * @param string $operation The pagination operation
     * @param int $page The current page number
     * @param int $perPage The number of items per page
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logPagination(string $operation, int $page, int $perPage, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'operation' => $operation,
            'page' => $page,
            'per_page' => $perPage,
            'class' => static::class,
            'context' => Config::getContext(),
        ], $context);

        $logger->debug("Pagination: {$operation} - Page {$page} ({$perPage} items)", $enrichedContext);
    }

    /**
     * Log OAuth token operations.
     *
     * @param string $operation The OAuth operation (e.g., 'refresh', 'validate', 'revoke')
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logOAuthOperation(string $operation, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        // Sanitize any sensitive data from context
        $sanitizedContext = $this->sanitizeOAuthContext($context);

        $enrichedContext = array_merge([
            'operation' => "oauth_{$operation}",
            'auth_mode' => Config::getAuthMode(),
            'class' => static::class,
            'context' => Config::getContext(),
        ], $sanitizedContext);

        $logger->info("OAuth Operation: {$operation}", $enrichedContext);
    }

    /**
     * Log file upload operations.
     *
     * @param string $step The upload step (e.g., 'initiate', 'upload', 'confirm')
     * @param array<string, mixed> $context Additional context for the log entry
     * @return void
     */
    protected function logFileUpload(string $step, array $context = []): void
    {
        $logger = $this->getActivityLogger();

        $enrichedContext = array_merge([
            'operation' => 'file_upload',
            'step' => $step,
            'class' => static::class,
            'context' => Config::getContext(),
        ], $context);

        $logger->info("File Upload: Step '{$step}'", $enrichedContext);
    }

    /**
     * Get the logger instance for activity logging.
     *
     * @return LoggerInterface
     */
    protected function getActivityLogger(): LoggerInterface
    {
        return Config::getLogger();
    }

    /**
     * Sanitize OAuth context to remove sensitive data.
     *
     * @param array<string, mixed> $context The context to sanitize
     * @return array<string, mixed> The sanitized context
     */
    private function sanitizeOAuthContext(array $context): array
    {
        $sensitiveKeys = [
            'token', 'access_token', 'refresh_token',
            'client_secret', 'password', 'api_key'
        ];

        $sanitized = [];
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                // Mask sensitive values
                if (is_string($value) && strlen($value) > 4) {
                    $sanitized[$key] = '***' . substr($value, -4);
                } else {
                    $sanitized[$key] = '***';
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Start a timed operation for performance logging.
     *
     * @return float The start time
     */
    protected function startTimer(): float
    {
        return microtime(true);
    }

    /**
     * End a timed operation and log the performance.
     *
     * @param float $startTime The start time from startTimer()
     * @param string $operation The operation being measured
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    protected function endTimer(float $startTime, string $operation, array $context = []): void
    {
        $duration = microtime(true) - $startTime;
        $this->logPerformance($operation, $duration, $context);
    }
}
