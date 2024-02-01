<?php

namespace CanvasLMS\Dto\Users;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class UpdateUserDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The full name of the user. This name will be used by teacher for grading.
     * @var string|null $name
     */
    public ?string $name;

    /**
     * User’s name as it will be displayed in discussions, messages, and comments.
     * @var string|null $shortName
     */
    public ?string $shortName;

    /**
     * User’s name as used to sort alphabetically in lists.
     * @var string|null $sortableName
     */
    public ?string $sortableName;

    /**
     * The time zone for the user. Allowed time zones are IANA time zones or friendlier Ruby on Rails time zones.
     * @var string|null $timeZone
     */
    public ?string $timeZone;

    /**
     * The default email address of the user.
     * @var string|null $email
     */
    public ?string $email;

    /**
     * The user’s preferred language, from the list of languages Canvas supports. This is in RFC-5646 format.
     * @var string|null $locale
     */
    public ?string $locale;

    /**
     * A unique representation of the avatar record to assign as the user’s current avatar.
     * This token can be obtained from the user avatars endpoint. This supersedes the user [avatar] [url] argument,
     * and if both are included the url will be ignored. Note: this is an internal representation and is subject to
     * change without notice. It should be consumed with this api endpoint and used in the user update endpoint,
     * and should not be constructed by the client.
     * @var string|null $avatarToken
     */
    public ?string $avatarToken;

    /**
     * To set the user’s avatar to point to an external url, do not include a token and instead pass the url here.
     * Warning: For maximum compatibility, please use 128 px square images.
     * @var string|null $avatarUrl
     */
    public ?string $avatarUrl;

    /**
     * To set the state of user’s avatar. Only valid for account administrator.
     * @var string|null $avatarState
     * Allowed values: none, submitted, approved, locked, reported, re_reported
     */
    public ?string $avatarState;

    /**
     * Sets a title on the user profile. (See Get user profile.) Profiles must be enabled on the root account.
     * @var string|null $title
     */
    public ?string $title;

    /**
     * Sets a bio on the user profile. (See Get user profile.) Profiles must be enabled on the root account.
     * @var string|null $bio
     */
    public ?string $bio;

    /**
     * Sets pronouns on the user profile. Passing an empty string will empty the user’s pronouns
     * Only Available Pronouns set on the root account are allowed Adding and changing pronouns
     * must be enabled on the root account.
     * @var string|null $pronouns
     */
    public ?string $pronouns;

    /**
     * Suspends or unsuspends all logins for this user that the calling user has permission to
     * @var string|null $event
     * Allowed values: suspend, unsuspend
     */
    public ?string $event;

    /**
     * Default is true. If false, any fields containing “sticky” changes will not be updated.
     * See SIS CSV Format documentation for information on which fields can have SIS stickiness
     * @var bool|null $overrideSisStickiness
     */
    public ?bool $overrideSisStickiness;

    /**
     * Convert the DTO to an array for API requests
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            $apiKeyName = 'user[' . str_to_snake_case($key) . ']';

            // For the avatar, since it's a nested array
            if (in_array($key, ['avatarToken', 'avatarUrl', 'avatarState'])) {
                $avatarKey = str_replace('avatar', '', $key);
                $avatarKey = lcfirst($avatarKey); // make sure the first letter is lowercase
                $apiKeyName = 'user[avatar][' . str_to_snake_case($avatarKey) . ']';
            }

            $modifiedProperties[$apiKeyName] = $value;
        }

        return $modifiedProperties;
    }
}
