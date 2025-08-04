<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\ContentMigrations;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating migration issues in Canvas LMS
 *
 * This DTO handles updating the workflow state of migration issues.
 * The only updateable field is the workflow_state.
 *
 * @package CanvasLMS\Dto\ContentMigrations
 */
class UpdateMigrationIssueDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = '';

    /**
     * Set the workflow_state of the issue (required)
     * Allowed values: active, resolved
     */
    public ?string $workflowState = null;

    /**
     * Constructor
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Transform the DTO to the API-expected array format
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $result = [];

        if ($this->workflowState !== null) {
            $result[] = [
                'name' => 'workflow_state',
                'contents' => $this->workflowState
            ];
        }

        return $result;
    }

    /**
     * Validate the DTO
     *
     * @return bool
     */
    public function validate(): bool
    {
        // Workflow state is required
        if (empty($this->workflowState)) {
            return false;
        }

        // Validate workflow state value
        $validStates = ['active', 'resolved'];
        return in_array($this->workflowState, $validStates);
    }

    // Getters and setters

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Set state to resolved
     */
    public function setResolved(): void
    {
        $this->workflowState = 'resolved';
    }

    /**
     * Set state to active
     */
    public function setActive(): void
    {
        $this->workflowState = 'active';
    }
}
