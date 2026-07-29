<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Class UserDisplay
 *
 * Represents Canvas's abbreviated "UserDisplay" user representation. Canvas embeds this
 * compact object (rather than a full User resource) on other objects when the API is asked
 * to include user information alongside them, e.g. the uploader of a file when a course's
 * file listing is requested with `include[]=user`.
 *
 * This is a read-only data object with no API endpoints of its own - it is only ever
 * returned nested inside another resource's response.
 *
 * @see https://canvas.instructure.com/doc/api/all_resources.html#UserDisplay
 *
 * @package CanvasLMS\Objects
 */
class UserDisplay
{
    /**
     * The user's ID
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * A short name the user has selected, for use in conversations or other less formal
     * contexts
     *
     * @var string|null
     */
    public ?string $displayName = null;

    /**
     * If avatars are enabled, this will be a URL to the user's avatar
     *
     * @var string|null
     */
    public ?string $avatarImageUrl = null;

    /**
     * URL to access user, either nested to a context or directly
     *
     * @var string|null
     */
    public ?string $htmlUrl = null;

    /**
     * Optional: This user's pronouns
     *
     * @var string|null
     */
    public ?string $pronouns = null;

    /**
     * Optional: This user's anonymous id, for use in an assignment with anonymous grading
     * or peer review
     *
     * @var string|null
     */
    public ?string $anonymousId = null;

    /**
     * UserDisplay constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $property) && !is_null($value)) {
                $this->{$property} = $value;
            }
        }
    }
}
