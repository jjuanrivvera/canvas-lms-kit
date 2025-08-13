<?php

declare(strict_types=1);

namespace CanvasLMS\Api\OutcomeImports;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * OutcomeImport API class for importing outcome data in bulk.
 *
 * Outcome imports allow bulk importing of learning outcomes from CSV files
 * or other supported formats into Canvas LMS.
 *
 * @package CanvasLMS\Api\OutcomeImports
 * @see https://canvas.instructure.com/doc/api/outcome_imports.html
 */
class OutcomeImport extends AbstractBaseApi
{
    public ?int $id = null;
    public ?int $learningOutcomeGroupId = null;
    public ?string $createdAt = null;
    public ?string $endedAt = null;
    public ?string $updatedAt = null;
    public ?string $workflowState = null;
    /** @var array<string, mixed>|null */
    public ?array $data = null;
    public ?string $progress = null;
    /** @var array<string, mixed>|null */
    public ?array $user = null;
    /** @var array<int, array{0: int, 1: string}>|null */
    public ?array $processingErrors = null;

    /**
     * Import outcomes from a file (defaults to Account context).
     *
     * @param string $filePath Path to the CSV file
     * @param int|null $groupId Optional outcome group ID to import into
     * @param string $importType Import type (default: 'instructure_csv')
     * @return self
     * @throws CanvasApiException
     */
    public static function import(
        string $filePath,
        ?int $groupId = null,
        string $importType = 'instructure_csv'
    ): self {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to import outcomes');
        }

        return self::importToContext('accounts', $accountId, $filePath, $groupId, $importType);
    }

    /**
     * Import outcomes to a specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param string $filePath Path to the CSV file
     * @param int|null $groupId Optional outcome group ID to import into
     * @param string $importType Import type (default: 'instructure_csv')
     * @return self
     * @throws CanvasApiException
     */
    public static function importToContext(
        string $contextType,
        int $contextId,
        string $filePath,
        ?int $groupId = null,
        string $importType = 'instructure_csv'
    ): self {
        if (!file_exists($filePath)) {
            throw new CanvasApiException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new CanvasApiException("File not readable: {$filePath}");
        }

        $endpoint = $groupId
            ? sprintf('%s/%d/outcome_imports/group/%d', $contextType, $contextId, $groupId)
            : sprintf('%s/%d/outcome_imports', $contextType, $contextId);

        try {
            $fileResource = fopen($filePath, 'r');

            if ($fileResource === false) {
                throw new CanvasApiException("Failed to open file: {$filePath}");
            }

            $multipart = [
                [
                    'name' => 'import_type',
                    'contents' => $importType
                ],
                [
                    'name' => 'attachment',
                    'contents' => $fileResource,
                    'filename' => basename($filePath)
                ]
            ];

            $response = self::$apiClient->post($endpoint, [
                'multipart' => $multipart
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            return new self($responseData);
        } finally {
            if (isset($fileResource) && is_resource($fileResource)) {
                fclose($fileResource);
            }
        }
    }

    /**
     * Import outcomes from raw CSV data (defaults to Account context).
     *
     * @param string $csvData Raw CSV data as string
     * @param int|null $groupId Optional outcome group ID to import into
     * @param string $importType Import type (default: 'instructure_csv')
     * @return self
     * @throws CanvasApiException
     */
    public static function importFromData(
        string $csvData,
        ?int $groupId = null,
        string $importType = 'instructure_csv'
    ): self {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to import outcomes');
        }

        return self::importDataToContext('accounts', $accountId, $csvData, $groupId, $importType);
    }

    /**
     * Import outcomes from raw CSV data to a specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param string $csvData Raw CSV data as string
     * @param int|null $groupId Optional outcome group ID to import into
     * @param string $importType Import type (default: 'instructure_csv')
     * @return self
     * @throws CanvasApiException
     */
    public static function importDataToContext(
        string $contextType,
        int $contextId,
        string $csvData,
        ?int $groupId = null,
        string $importType = 'instructure_csv'
    ): self {
        $endpoint = $groupId
            ? sprintf('%s/%d/outcome_imports/group/%d', $contextType, $contextId, $groupId)
            : sprintf('%s/%d/outcome_imports', $contextType, $contextId);

        $queryParams = ['import_type' => $importType];

        $response = self::$apiClient->post($endpoint, [
            'query' => $queryParams,
            'headers' => [
                'Content-Type' => 'text/csv'
            ],
            'body' => $csvData
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);
        return new self($responseData);
    }

    /**
     * Get the status of an outcome import.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int|string $importId Import ID or 'latest' for the most recent import
     * @return self
     * @throws CanvasApiException
     */
    public static function getStatus(
        string $contextType,
        int $contextId,
        int|string $importId
    ): self {
        $endpoint = sprintf('%s/%d/outcome_imports/%s', $contextType, $contextId, $importId);

        $response = self::$apiClient->get($endpoint);
        $responseData = json_decode($response->getBody()->getContents(), true);

        return new self($responseData);
    }

    /**
     * Get the latest import status for account context.
     *
     * @return self
     * @throws CanvasApiException
     */
    public static function getLatestStatus(): self
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to get import status');
        }

        return self::getStatus('accounts', $accountId, 'latest');
    }

    /**
     * Check if the import is complete.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return in_array($this->workflowState, ['succeeded', 'failed'], true);
    }

    /**
     * Check if the import succeeded.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->workflowState === 'succeeded';
    }

    /**
     * Check if the import failed.
     *
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->workflowState === 'failed';
    }

    /**
     * Check if the import is currently processing.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->workflowState === 'importing';
    }

    /**
     * Get the progress percentage.
     *
     * @return float
     */
    public function getProgressPercentage(): float
    {
        if ($this->progress === null) {
            return 0.0;
        }

        return (float) $this->progress;
    }

    /**
     * Get processing errors if any.
     *
     * @return array<int, mixed> Array of [row_number, error_message] pairs
     */
    public function getErrors(): array
    {
        return $this->processingErrors ?? [];
    }

    /**
     * Check if there are any processing errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->processingErrors);
    }

    /**
     * Get formatted error messages.
     *
     * @return array<int, string> Array of formatted error strings
     */
    public function getFormattedErrors(): array
    {
        $errors = [];

        foreach ($this->getErrors() as $error) {
            if (is_array($error) && count($error) >= 2) {
                $errors[] = sprintf('Row %d: %s', $error[0], $error[1]);
            }
        }

        return $errors;
    }

    /**
     * Wait for import to complete with polling.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int $maxAttempts Maximum polling attempts (default: 60)
     * @param int $delaySeconds Delay between polls in seconds (default: 2)
     * @return self
     * @throws CanvasApiException
     */
    public function waitForCompletion(
        string $contextType,
        int $contextId,
        int $maxAttempts = 60,
        int $delaySeconds = 2
    ): self {
        if (!$this->id) {
            throw new CanvasApiException('Import ID is required to check status');
        }

        $attempts = 0;

        while ($attempts < $maxAttempts) {
            if ($this->isComplete()) {
                return $this;
            }

            sleep($delaySeconds);

            $status = self::getStatus($contextType, $contextId, $this->id);

            // Update current instance with new status data
            foreach (get_object_vars($status) as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }

            $attempts++;
        }

        throw new CanvasApiException(
            "Import did not complete after {$maxAttempts} attempts"
        );
    }

    /**
     * Generate CSV template for outcome import.
     *
     * @return string CSV template with headers
     */
    public static function generateCsvTemplate(): string
    {
        $headers = [
            'vendor_guid',
            'outcome_group_vendor_guid',
            'parent_guids',
            'title',
            'description',
            'display_name',
            'calculation_method',
            'calculation_int',
            'mastery_points',
            'ratings',
            'object_type'
        ];

        $exampleRow = [
            'GUID_001',
            'GROUP_001',
            '',
            'Critical Thinking',
            'Student demonstrates critical thinking skills',
            'Critical Thinking',
            'decaying_average',
            '75',
            '3',
            '4:Exceeds|3:Mastery|2:Near Mastery|1:Below Mastery',
            'outcome'
        ];

        $csv = implode(',', $headers) . "\n";
        $csv .= implode(',', array_map(fn($v) => '"' . $v . '"', $exampleRow)) . "\n";

        return $csv;
    }

    /**
     * Build CSV data from an array of outcome definitions.
     *
     * @param array<int, array<string, mixed>> $outcomes Array of outcome data
     * @return string CSV formatted string
     */
    public static function buildCsvFromArray(array $outcomes): string
    {
        $headers = [
            'vendor_guid',
            'outcome_group_vendor_guid',
            'parent_guids',
            'title',
            'description',
            'display_name',
            'calculation_method',
            'calculation_int',
            'mastery_points',
            'ratings',
            'object_type'
        ];

        $csv = implode(',', $headers) . "\n";

        foreach ($outcomes as $outcome) {
            $row = [
                $outcome['vendor_guid'] ?? '',
                $outcome['outcome_group_vendor_guid'] ?? '',
                $outcome['parent_guids'] ?? '',
                $outcome['title'] ?? '',
                $outcome['description'] ?? '',
                $outcome['display_name'] ?? $outcome['title'] ?? '',
                $outcome['calculation_method'] ?? '',
                $outcome['calculation_int'] ?? '',
                $outcome['mastery_points'] ?? '',
                self::formatRatings($outcome['ratings'] ?? []),
                $outcome['object_type'] ?? 'outcome'
            ];

            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Format ratings array for CSV import.
     *
     * @param array<int, mixed> $ratings Array of rating definitions
     * @return string Formatted ratings string
     */
    protected static function formatRatings(array $ratings): string
    {
        if (empty($ratings)) {
            return '';
        }

        $formatted = [];

        foreach ($ratings as $rating) {
            if (is_array($rating) && isset($rating['points']) && isset($rating['description'])) {
                $formatted[] = $rating['points'] . ':' . $rating['description'];
            }
        }

        return implode('|', $formatted);
    }

    /**
     * Find method - not applicable for imports.
     *
     * @param int $id
     * @return static
     * @throws CanvasApiException
     */
    public static function find(int $id): static
    {
        throw new CanvasApiException(
            'The find() method is not applicable for OutcomeImport. Use getStatus() instead.'
        );
    }

    /**
     * FetchAll method - not applicable for imports.
     *
     * @param array<string, mixed> $params
     * @return array<int, static>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        throw new CanvasApiException(
            'The fetchAll() method is not applicable for OutcomeImport. Use import() or importFromData() instead.'
        );
    }
}
