<?php

namespace CanvasLMS\Objects;

/**
 * User Profile object
 *
 * Represents a user's profile information in Canvas LMS.
 * Contains extended user information beyond the basic User object.
 */
class Profile
{
    /**
     * @var int User ID
     */
    public int $id;

    /**
     * @var string User's name
     */
    public string $name;

    /**
     * @var string User's short name
     */
    public string $shortName;

    /**
     * @var string User's sortable name
     */
    public string $sortableName;

    /**
     * @var string|null User's title
     */
    public ?string $title = null;

    /**
     * @var string|null User's bio
     */
    public ?string $bio = null;

    /**
     * @var string|null User's primary email
     */
    public ?string $primaryEmail = null;

    /**
     * @var string|null User's login ID
     */
    public ?string $loginId = null;

    /**
     * @var string|null User's SIS ID
     */
    public ?string $sisUserId = null;

    /**
     * @var string|null User's integration ID
     */
    public ?string $integrationId = null;

    /**
     * @var string|null Avatar URL
     */
    public ?string $avatarUrl = null;

    /**
     * @var array<string, mixed>|null Calendar data
     */
    public ?array $calendar = null;

    /**
     * @var string|null User's time zone
     */
    public ?string $timeZone = null;

    /**
     * @var string|null User's locale
     */
    public ?string $locale = null;

    /**
     * @var string|null User's effective locale
     */
    public ?string $effectiveLocale = null;

    /**
     * @var string|null Last login timestamp
     */
    public ?string $lastLogin = null;

    /**
     * @var bool|null Whether user can update their name
     */
    public ?bool $canUpdateName = null;

    /**
     * Constructor
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Check if user has a bio
     * @return bool
     */
    public function hasBio(): bool
    {
        return !empty($this->bio);
    }

    /**
     * Check if user has a title
     * @return bool
     */
    public function hasTitle(): bool
    {
        return !empty($this->title);
    }

    /**
     * Check if user has an avatar
     * @return bool
     */
    public function hasAvatar(): bool
    {
        return !empty($this->avatarUrl);
    }

    /**
     * Convert to array
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
