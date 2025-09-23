<?php

declare(strict_types=1);

namespace CanvasLMS\Api\OutcomeGroups;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Dto\OutcomeGroups\CreateOutcomeGroupDTO;
use CanvasLMS\Dto\OutcomeGroups\UpdateOutcomeGroupDTO;
use CanvasLMS\Dto\Outcomes\CreateOutcomeDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\OutcomeLink;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * OutcomeGroup API class for managing hierarchical outcome groups in Canvas LMS.
 *
 * Outcome groups organize learning outcomes in a hierarchical structure,
 * allowing for categorization and management of related outcomes.
 *
 * @package CanvasLMS\Api\OutcomeGroups
 *
 * @see https://canvas.instructure.com/doc/api/outcome_groups.html
 */
class OutcomeGroup extends AbstractBaseApi
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $vendorGuid = null;

    public ?int $parentOutcomeGroupId = null;

    public ?string $contextType = null;

    public ?int $contextId = null;

    public ?string $url = null;

    public ?string $subgroupsUrl = null;

    public ?string $outcomesUrl = null;

    public ?string $importsUrl = null;

    public ?bool $canEdit = null;

    public ?int $outcomesCount = null;

    public ?int $subgroupsCount = null;

    /**
     * Get first page of outcome groups (defaults to Account context).
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeGroup> Array of OutcomeGroup objects
     */
    public static function get(array $params = []): array
    {
        return parent::get($params);
    }

    /**
     * Get paginated outcome groups (defaults to Account context).
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginationResult Paginated result with metadata
     */
    public static function paginate(array $params = []): PaginationResult
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to fetch outcome groups');
        }

        return self::fetchByContextPaginated('accounts', $accountId, $params);
    }

    /**
     * Fetch global outcome groups.
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeGroup> Array of OutcomeGroup objects
     */
    public static function fetchGlobal(array $params = []): array
    {
        $endpoint = 'global/outcome_groups';
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn ($data) => new self($data), $allData);
    }

    /**
     * Fetch outcome groups by specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeGroup> Array of OutcomeGroup objects
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/outcome_groups', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn ($data) => new self($data), $allData);
    }

    /**
     * Fetch paginated outcome groups by specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginationResult
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginationResult {
        $endpoint = sprintf('%s/%d/outcome_groups', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Find a specific outcome group by ID (defaults to Account context).
     *
     * @param int $id Outcome group ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to find outcome group');
        }

        return self::findByContext('accounts', $accountId, $id);
    }

    /**
     * Find a specific outcome group by ID within a context.
     *
     * @param string|null $contextType Context type (accounts, courses) or null for global
     * @param int|null $contextId Context ID or null for global
     * @param int $id Outcome group ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function findByContext(?string $contextType, ?int $contextId, int $id): self
    {
        if ($contextType === null || $contextId === null) {
            return self::findGlobal($id);
        }

        $endpoint = sprintf('%s/%d/outcome_groups/%d', $contextType, $contextId, $id);
        $response = self::getApiClient()->get($endpoint);

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Find a specific global outcome group by ID.
     *
     * @param int $id Outcome group ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function findGlobal(int $id): self
    {
        $endpoint = sprintf('global/outcome_groups/%d', $id);
        $response = self::getApiClient()->get($endpoint);

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Get the global/root outcome group for a context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function getRootGroup(string $contextType, int $contextId): self
    {
        $endpoint = sprintf('%s/%d/root_outcome_group', $contextType, $contextId);
        $response = self::getApiClient()->get($endpoint);

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Get the global root outcome group.
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function getGlobalRootGroup(): self
    {
        $response = self::getApiClient()->get('global/root_outcome_group');

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Create a new outcome group (defaults to Account context).
     *
     * @param array<string, mixed>|CreateOutcomeGroupDTO $data Outcome group data
     * @param int|null $parentGroupId Parent group ID (null for root level)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function create(array|CreateOutcomeGroupDTO $data, ?int $parentGroupId = null): self
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to create outcome groups');
        }

        return self::createInContext('accounts', $accountId, $data, $parentGroupId);
    }

    /**
     * Create a new global outcome group.
     *
     * @param array<string, mixed>|CreateOutcomeGroupDTO $data Outcome group data
     * @param int|null $parentGroupId Parent group ID (null for root level)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function createGlobal(array|CreateOutcomeGroupDTO $data, ?int $parentGroupId = null): self
    {
        if (is_array($data)) {
            $data = new CreateOutcomeGroupDTO($data);
        }

        $parentPath = $parentGroupId ? (string) $parentGroupId : 'global';
        $endpoint = sprintf('global/outcome_groups/%s/subgroups', $parentPath);

        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Create a new outcome group in a specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed>|CreateOutcomeGroupDTO $data Outcome group data
     * @param int|null $parentGroupId Parent group ID (null for root level)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function createInContext(
        string $contextType,
        int $contextId,
        array|CreateOutcomeGroupDTO $data,
        ?int $parentGroupId = null
    ): self {
        if (is_array($data)) {
            $data = new CreateOutcomeGroupDTO($data);
        }

        $parentPath = $parentGroupId ? (string) $parentGroupId : 'global';
        $endpoint = sprintf('%s/%d/outcome_groups/%s/subgroups', $contextType, $contextId, $parentPath);

        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);

        return new self(self::parseJsonResponse($response));
    }

    /**
     * Update an existing outcome group.
     *
     * @param array<string, mixed>|UpdateOutcomeGroupDTO $data Update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function update(array|UpdateOutcomeGroupDTO $data): self
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to update');
        }

        if (is_array($data)) {
            $data = new UpdateOutcomeGroupDTO($data);
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        $response = self::getApiClient()->put($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);

        $responseData = self::parseJsonResponse($response);

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
     * Delete an outcome group.
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to delete');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        self::getApiClient()->delete($endpoint);

        return $this;
    }

    /**
     * Get subgroups of this outcome group.
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeGroup> Array of OutcomeGroup objects
     */
    public function subgroups(array $params = []): array
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to fetch subgroups');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/subgroups',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn ($data) => new self($data), $allData);
    }

    /**
     * Get outcomes in this outcome group.
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeLink> Array of OutcomeLink objects
     */
    public function outcomes(array $params = []): array
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to fetch outcomes');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/outcomes',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        // Add outcome_style parameter if not provided
        if (!isset($params['outcome_style'])) {
            $params['outcome_style'] = 'abbrev';
        }

        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        // Canvas returns OutcomeLink objects from this endpoint
        return array_map(fn ($data) => new OutcomeLink($data), $allData);
    }

    /**
     * Link an existing outcome to this group.
     *
     * @param int $outcomeId Outcome ID to link
     * @param int|null $moveFrom Optional group ID to move the outcome from
     *
     * @throws CanvasApiException
     *
     * @return OutcomeLink
     */
    public function linkOutcome(int $outcomeId, ?int $moveFrom = null): OutcomeLink
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to link outcomes');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/outcomes/%d',
            $this->contextType,
            $this->contextId,
            $this->id,
            $outcomeId
        );

        $options = [];
        if ($moveFrom !== null) {
            $options['query'] = ['move_from' => $moveFrom];
        }

        $response = self::getApiClient()->put($endpoint, $options);

        $responseData = self::parseJsonResponse($response);

        // Canvas returns an OutcomeLink object
        return new OutcomeLink($responseData);
    }

    /**
     * Create a new outcome in this group.
     *
     * @param array<string, mixed>|CreateOutcomeDTO $data Outcome data
     *
     * @throws CanvasApiException
     *
     * @return OutcomeLink
     */
    public function createOutcome(array|CreateOutcomeDTO $data): OutcomeLink
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to create outcomes');
        }

        if (is_array($data)) {
            $data = new CreateOutcomeDTO($data);
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/outcomes',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $data->toApiArray(),
        ]);

        $responseData = self::parseJsonResponse($response);

        // Canvas returns an OutcomeLink object
        return new OutcomeLink($responseData);
    }

    /**
     * Unlink an outcome from this group.
     *
     * @param int $outcomeId Outcome ID to unlink
     *
     * @throws CanvasApiException
     *
     * @return bool
     */
    public function unlinkOutcome(int $outcomeId): bool
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to unlink outcomes');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/outcomes/%d',
            $this->contextType,
            $this->contextId,
            $this->id,
            $outcomeId
        );

        $response = self::getApiClient()->delete($endpoint);

        return $response->getStatusCode() === 200 || $response->getStatusCode() === 204;
    }

    /**
     * Import outcomes from external source.
     *
     * @param array<string, mixed> $importData Import configuration
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Import status
     */
    public function importOutcomes(array $importData): array
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to import outcomes');
        }

        $endpoint = sprintf(
            '%s/%d/outcome_groups/%d/import',
            $this->contextType,
            $this->contextId,
            $this->id
        );

        // Convert import data to multipart format
        $multipartData = [];
        foreach ($importData as $key => $value) {
            $multipartData[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }

        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $multipartData,
        ]);

        return self::parseJsonResponse($response);
    }

    /**
     * Create a subgroup within this group.
     *
     * @param array<string, mixed>|CreateOutcomeGroupDTO $data Subgroup data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function createSubgroup(array|CreateOutcomeGroupDTO $data): self
    {
        if (!$this->id || !$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Group ID and context are required to create subgroups');
        }

        return self::createInContext($this->contextType, $this->contextId, $data, $this->id);
    }

    /**
     * Check if this group can be edited.
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return $this->canEdit ?? false;
    }

    /**
     * Fetch all outcome links for a context (defaults to Account).
     * Returns ALL outcome links across ALL groups in the context.
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeLink> Array of OutcomeLink objects
     */
    public static function fetchAllLinks(array $params = []): array
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to fetch outcome links');
        }

        return self::fetchAllLinksByContext('accounts', $accountId, $params);
    }

    /**
     * Fetch all outcome links for a specific context.
     * Returns ALL outcome links across ALL groups in the context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeLink> Array of OutcomeLink objects
     */
    public static function fetchAllLinksByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/outcome_group_links', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        return array_map(fn ($data) => new OutcomeLink($data), $allData);
    }

    /**
     * Fetch paginated outcome links (defaults to Account context).
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginationResult
     */
    public static function fetchAllLinksPaginated(array $params = []): PaginationResult
    {
        $accountId = Config::getAccountId();

        if (!$accountId) {
            throw new CanvasApiException('Account ID must be configured to fetch outcome links');
        }

        $paginatedResponse = self::fetchAllLinksByContextPaginated('accounts', $accountId, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch paginated outcome links for a specific context.
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginatedResponse
     */
    private static function fetchAllLinksByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        $endpoint = sprintf('%s/%d/outcome_group_links', $contextType, $contextId);

        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get the full hierarchy path from root to this group.
     *
     * @throws CanvasApiException
     *
     * @return array<int, OutcomeGroup> Array of parent groups
     */
    public function getHierarchyPath(): array
    {
        if (!$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Context is required to get hierarchy path');
        }

        $path = [];
        $currentGroup = $this;

        while ($currentGroup->parentOutcomeGroupId) {
            $parent = self::findByContext($this->contextType, $this->contextId, $currentGroup->parentOutcomeGroupId);
            array_unshift($path, $parent);
            $currentGroup = $parent;
        }

        return $path;
    }

    /**
     * Get the API endpoint for this resource
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();
        if (empty($accountId)) {
            throw new CanvasApiException('Account ID must be set in Config for OutcomeGroup operations');
        }

        return "accounts/{$accountId}/outcome_groups";
    }
}
