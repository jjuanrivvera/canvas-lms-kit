<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\FeatureFlags;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating feature flags in Canvas LMS.
 *
 * This DTO handles the formatting of feature flag update requests,
 * including state changes and flag settings.
 *
 * @see https://canvas.instructure.com/doc/api/feature_flags.html
 */
class UpdateFeatureFlagDTO extends AbstractBaseDto
{
    /**
     * The state of the feature flag
     * Valid values: 'off', 'allowed', 'on'
     *
     * @var string|null
     */
    public ?string $state = null;

    /**
     * Whether the feature flag is locked at this level
     *
     * @var bool|null
     */
    public ?bool $locked = null;

    /**
     * Whether the feature flag is hidden from the UI
     *
     * @var bool|null
     */
    public ?bool $hidden = null;

    /**
     * Constructor to initialize the DTO with data
     *
     * @param array<string, mixed> $data Initial data for the DTO
     */
    public function __construct(array $data = [])
    {
        $this->state = $data['state'] ?? null;
        $this->locked = $data['locked'] ?? null;
        $this->hidden = $data['hidden'] ?? null;

        // Validate state if provided
        if ($this->state !== null) {
            $this->validateState($this->state);
        }
    }

    /**
     * Convert the DTO to a multipart array for API requests
     *
     * @return array<int, array<string, string>>
     */
    public function toMultipart(): array
    {
        $multipart = [];

        if ($this->state !== null) {
            $multipart[] = [
                'name' => 'state',
                'contents' => $this->state,
            ];
        }

        if ($this->locked !== null) {
            $multipart[] = [
                'name' => 'locked',
                'contents' => $this->locked ? 'true' : 'false',
            ];
        }

        if ($this->hidden !== null) {
            $multipart[] = [
                'name' => 'hidden',
                'contents' => $this->hidden ? 'true' : 'false',
            ];
        }

        return $multipart;
    }

    /**
     * Set the state of the feature flag
     *
     * @param string $state The state to set ('off', 'allowed', 'on')
     *
     * @throws \InvalidArgumentException If the state is invalid
     *
     * @return self
     */
    public function setState(string $state): self
    {
        $this->validateState($state);
        $this->state = $state;

        return $this;
    }

    /**
     * Set the feature flag to 'on' state
     *
     * @return self
     */
    public function enable(): self
    {
        $this->state = 'on';

        return $this;
    }

    /**
     * Set the feature flag to 'off' state
     *
     * @return self
     */
    public function disable(): self
    {
        $this->state = 'off';

        return $this;
    }

    /**
     * Set the feature flag to 'allowed' state
     *
     * @return self
     */
    public function allow(): self
    {
        $this->state = 'allowed';

        return $this;
    }

    /**
     * Set whether the feature flag is locked
     *
     * @param bool $locked Whether to lock the flag
     *
     * @return self
     */
    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Lock the feature flag
     *
     * @return self
     */
    public function lock(): self
    {
        $this->locked = true;

        return $this;
    }

    /**
     * Unlock the feature flag
     *
     * @return self
     */
    public function unlock(): self
    {
        $this->locked = false;

        return $this;
    }

    /**
     * Set whether the feature flag is hidden
     *
     * @param bool $hidden Whether to hide the flag
     *
     * @return self
     */
    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Hide the feature flag from the UI
     *
     * @return self
     */
    public function hide(): self
    {
        $this->hidden = true;

        return $this;
    }

    /**
     * Show the feature flag in the UI
     *
     * @return self
     */
    public function show(): self
    {
        $this->hidden = false;

        return $this;
    }

    /**
     * Validate the state value
     *
     * @param string $state The state to validate
     *
     * @throws \InvalidArgumentException If the state is invalid
     */
    protected function validateState(string $state): void
    {
        $validStates = ['off', 'allowed', 'on'];

        if (!in_array($state, $validStates, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid feature flag state "%s". Valid states are: %s',
                    $state,
                    implode(', ', $validStates)
                )
            );
        }
    }

    /**
     * Get the current state
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Check if the flag is locked
     *
     * @return bool|null
     */
    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * Check if the flag is hidden
     *
     * @return bool|null
     */
    public function isHidden(): ?bool
    {
        return $this->hidden;
    }

    /**
     * Convert the DTO to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->state !== null) {
            $data['state'] = $this->state;
        }

        if ($this->locked !== null) {
            $data['locked'] = $this->locked;
        }

        if ($this->hidden !== null) {
            $data['hidden'] = $this->hidden;
        }

        return $data;
    }

    /**
     * Validate that the DTO has at least one field set
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->state !== null || $this->locked !== null || $this->hidden !== null;
    }
}
