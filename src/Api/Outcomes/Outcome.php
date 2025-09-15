<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Outcomes;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Dto\Outcomes\CreateOutcomeDTO;
use CanvasLMS\Dto\Outcomes\UpdateOutcomeDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Outcome API class for managing learning outcomes in Canvas LMS.
 *
 * Outcomes allow instructors to track student mastery of specific learning objectives
 * across assignments and courses. This class follows the Account-as-Default convention
 * for multi-context resources.
 *
 * @package CanvasLMS\Api\Outcomes
 * @see https://canvas.instructure.com/doc/api/outcomes.html
 */
class Outcome extends AbstractBaseApi
{
    public ?int $id = null;
    public ?string $contextId = null;
    public ?string $contextType = null;
    public ?string $url = null;
    public ?string $title = null;
    public ?string $displayName = null;
    public ?string $description = null;
    public ?string $vendorGuid = null;
    public ?float $pointsPossible = null;
    public ?float $masteryPoints = null;
    /** @var array<int, mixed>|null */
    public ?array $ratings = null;
    public ?string $calculationMethod = null;
    public ?int $calculationInt = null;
    public ?bool $assessed = null;
    public ?bool $hasUpdateableRubrics = null;
    public ?bool $canEdit = null;
    public ?bool $canUnlink = null;
    public ?string $friendlyDescription = null;
    /** @var array<int, mixed>|null */
    public ?array $alignments = null;

    /**
     * Get the endpoint for this resource.
     *
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured');
        }

        return sprintf('accounts/%d/outcome_groups/global/outcomes', $accountId);
    }

    /**
     * Get first page of outcomes (defaults to Account context).
     *
     * @param array<string, mixed> $params Optional query parameters
     * @return array<int, static> Array of Outcome objects
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();
        $endpoint = self::getEndpoint();
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn(array $item) => new static($item), $data);
    }


    /**
     * Get paginated outcomes (defaults to Account context).
     *
     * @param array<string, mixed> $params Optional query parameters
     * @return PaginationResult Paginated result with metadata
     * @throws CanvasApiException
     */
    public static function paginate(array $params = []): PaginationResult
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to fetch outcomes');
        }

        return self::fetchByContextPaginated('accounts', $accountId, $params);
    }


    /**
     * Fetch outcomes by specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     * @return array<int, static> Array of Outcome objects
     * @throws CanvasApiException
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/outcome_groups/global/outcomes', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn($data) => new static($data), $allData);
    }

    /**
     * Fetch paginated outcomes by specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginationResult {
        $endpoint = sprintf('%s/%d/outcome_groups/global/outcomes', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Find a specific outcome by ID.
     *
     * @param int $id Outcome ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        $response = self::$apiClient->get(sprintf('outcomes/%d', $id));
        return new self(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Create a new outcome (defaults to Account context).
     *
     * @param array<string, mixed>|CreateOutcomeDTO $data Outcome data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateOutcomeDTO $data): self
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to create outcomes');
        }

        return self::createInContext('accounts', $accountId, null, $data);
    }

    /**
     * Create or link an outcome in a specific context and group.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int|null $groupId Outcome group ID (null for global group)
     * @param array<string, mixed>|CreateOutcomeDTO $data Outcome data
     * @return self
     * @throws CanvasApiException
     */
    public static function createInContext(
        string $contextType,
        int $contextId,
        ?int $groupId,
        array|CreateOutcomeDTO $data
    ): self {
        if (is_array($data)) {
            $data = new CreateOutcomeDTO($data);
        }

        $groupPath = $groupId ? (string)$groupId : 'global';
        $endpoint = sprintf('%s/%d/outcome_groups/%s/outcomes', $contextType, $contextId, $groupPath);

        $response = self::$apiClient->post($endpoint, [
            'multipart' => $data->toApiArray()
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if (isset($responseData['outcome'])) {
            return new self($responseData['outcome']);
        }

        return new self($responseData);
    }

    /**
     * Update an existing outcome.
     *
     * @param array<string, mixed>|UpdateOutcomeDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public function update(array|UpdateOutcomeDTO $data): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Outcome ID is required to update');
        }

        if (is_array($data)) {
            $data = new UpdateOutcomeDTO($data);
        }

        $response = self::$apiClient->put(sprintf('outcomes/%d', $this->id), [
            'multipart' => $data->toApiArray()
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        // Update current instance with response data
        foreach ($responseData as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Save the current outcome (create or update).
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'vendor_guid' => $this->vendorGuid,
            'display_name' => $this->displayName,
            'mastery_points' => $this->masteryPoints,
            'ratings' => $this->ratings,
            'calculation_method' => $this->calculationMethod,
            'calculation_int' => $this->calculationInt,
        ];

        if ($this->id) {
            return $this->update($data);
        }

        $newOutcome = self::create($data);

        // Update current instance with new outcome data
        foreach (get_object_vars($newOutcome) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Delete/unlink an outcome from a specific context and group.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int|null $groupId Outcome group ID (null for global group)
     * @return bool
     * @throws CanvasApiException
     */
    public function deleteFromContext(string $contextType, int $contextId, ?int $groupId = null): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Outcome ID is required to delete');
        }

        $groupPath = $groupId ? (string)$groupId : 'global';
        $endpoint = sprintf(
            '%s/%d/outcome_groups/%s/outcomes/%d',
            $contextType,
            $contextId,
            $groupPath,
            $this->id
        );

        $response = self::$apiClient->delete($endpoint);

        return $response->getStatusCode() === 200 || $response->getStatusCode() === 204;
    }

    /**
     * Get alignments for this outcome.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     * @return array<int, mixed> Array of alignments
     * @throws CanvasApiException
     */
    public function getAlignments(string $contextType, int $contextId, array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Outcome ID is required to get alignments');
        }

        $endpoint = sprintf('%s/%d/outcomes/%d/alignments', $contextType, $contextId, $this->id);

        $response = self::$apiClient->get($endpoint, [
            'query' => $params
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Check if outcome can be edited.
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return $this->canEdit ?? false;
    }

    /**
     * Check if outcome can be unlinked.
     *
     * @return bool
     */
    public function canUnlink(): bool
    {
        return $this->canUnlink ?? false;
    }

    /**
     * Check if outcome has been assessed.
     *
     * @return bool
     */
    public function isAssessed(): bool
    {
        return $this->assessed ?? false;
    }

    /**
     * Get the calculation method display name.
     *
     * @return string
     */
    public function getCalculationMethodDisplayName(): string
    {
        $methods = [
            'decaying_average' => 'Decaying Average',
            'n_mastery' => 'N Number of Times',
            'latest' => 'Most Recent Score',
            'highest' => 'Highest Score',
            'average' => 'Average'
        ];

        return $methods[$this->calculationMethod] ?? 'Unknown';
    }

    /**
     * Validate rating scale.
     *
     * @return bool
     */
    public function validateRatings(): bool
    {
        if (empty($this->ratings)) {
            return false;
        }

        foreach ($this->ratings as $rating) {
            if (!isset($rating['description']) || !isset($rating['points'])) {
                return false;
            }
        }

        if ($this->masteryPoints !== null) {
            $masteryFound = false;
            foreach ($this->ratings as $rating) {
                if ($rating['points'] == $this->masteryPoints) {
                    $masteryFound = true;
                    break;
                }
            }

            if (!$masteryFound) {
                return false;
            }
        }

        return true;
    }

    /**
     * Import an outcome from external vendor.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param string $vendorGuid Vendor GUID
     * @param int|null $groupId Outcome group ID
     * @return self
     * @throws CanvasApiException
     */
    public static function importFromVendor(
        string $contextType,
        int $contextId,
        string $vendorGuid,
        ?int $groupId = null
    ): self {
        $groupPath = $groupId ? (string)$groupId : 'global';
        $endpoint = sprintf('%s/%d/outcome_groups/%s/import', $contextType, $contextId, $groupPath);

        $response = self::$apiClient->post($endpoint, [
            'multipart' => [
                [
                    'name' => 'source_outcome_id',
                    'contents' => $vendorGuid
                ]
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if (isset($responseData['outcome'])) {
            return new self($responseData['outcome']);
        }

        return new self($responseData);
    }
}
