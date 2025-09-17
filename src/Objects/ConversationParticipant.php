<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Class ConversationParticipant
 *
 * Represents a participant in a Canvas conversation.
 * This is a data object with no API endpoints - returned as part of conversation responses.
 *
 * @package CanvasLMS\Objects
 *
 * @property int|null $id The user ID for the participant
 * @property string|null $name A short name the user has selected
 * @property string|null $fullName The full name of the user
 * @property string|null $avatarUrl URL to retrieve the user's avatar (if requested)
 */
class ConversationParticipant
{
    public ?int $id = null;

    public ?string $name = null;

    public ?string $fullName = null;

    public ?string $avatarUrl = null;

    /**
     * ConversationParticipant constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $value;
            }
        }
    }
}
