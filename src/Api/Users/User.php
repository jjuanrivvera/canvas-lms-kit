<?php

namespace CanvasLMS\Api\Users;

use Exception;
use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Users\UpdateUserDTO;
use CanvasLMS\Dto\Users\CreateUserDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * User Class
 *
 * Represents a user in the Canvas LMS. This class provides methods to create, update,
 * and find users from the Canvas LMS system. It utilizes Data Transfer Objects (DTOs)
 * for handling user creation and updates.
 *
 * Usage Examples:
 *
 * // Creating a new user
 * $userData = [
 *     'username' => 'john_doe',
 *     'email' => 'john.doe@example.com',
 *     // ... other user data ...
 * ];
 * $user = User::create($userData);
 *
 * // Updating an existing user
 * $updatedData = [
 *     'email' => 'new_john.doe@example.com',
 *     // ... other updated data ...
 * ];
 * $updatedUser = User::update(123, $updatedData); // where 123 is the user ID
 *
 * // Finding a user by ID
 * $user = User::find(123);
 *
 * @package CanvasLMS\Api
 */
class User extends AbstractBaseApi
{
    /**
     * The ID of the user.
     * @var int
     */
    public int $id;

    /**
     * The name of the user.
     * @var string
     */
    public string $name;

    /**
     * The name of the user that is should be used for sorting groups of users, such
     * as in the gradebook.
     * @var string
     */
    public string $sortableName;

    /**
     * The last name of the user.
     * @var string
     */
    public string $lastName;

    /**
     * The first name of the user.
     * @var string
     */
    public string $firstName;

    /**
     * A short name the user has selected, for use in conversations or other less
     * formal places through the site.
     * @var string
     */
    public string $shortName;

    /**
     * The SIS ID associated with the user. This field is only included if the user
     * came from a SIS import and has permissions to view SIS information.
     * @var string|null
     */
    public ?string $sisUserId;

    /**
     * The id of the SIS import. This field is only included if the user came from
     * a SIS import and has permissions to manage SIS information.
     * @var int|null
     */
    public ?int $sisImportId;

    /**
     * The integration_id associated with the user. This field is only included if
     * the user came from a SIS import and has permissions to view SIS information.
     * @var string|null
     */
    public ?string $integrationId;

    /**
     * The unique login id for the user. This is what the user uses to log in to
     * Canvas.
     * @var string
     */
    public string $loginId;

    /**
     * If avatars are enabled, this field will be included and contain a url to
     * retrieve the user's avatar.
     * @var string|null
     */
    public ?string $avatarUrl;

    /**
     * Optional: If avatars are enabled and caller is admin, this field can be
     * requested and will contain the current state of the user's avatar.
     * @var string|null
     */
    public ?string $avatarState;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * a list of the users active enrollments. See the List enrollments API for more
     * details about the format of these records.
     * @var mixed[]|null
     */
    public ?array $enrollments;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * the users primary email address.
     * @var string|null
     */
    public ?string $email;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * the users locale in RFC 5646 format.
     * @var string|null
     */
    public ?string $locale;

    /**
     * Optional: This field is only returned in certain API calls, and will return a
     * timestamp representing the last time the user logged in to canvas.
     * @var string|null
     */
    public ?string $lastLogin;

    /**
     * Optional: This field is only returned in certain API calls, and will return
     * the IANA time zone name of the user's preferred timezone.
     * @var string|null
     */
    public ?string $timeZone;

    /**
     * Optional: The user's bio.
     * @var string|null
     */
    public ?string $bio;

    /**
     * Create a new User instance.
     * @param mixed[] $userData
     * @return self
     * @throws Exception
     */
    public static function create(array $userData): self
    {
        self::checkApiClient();
        $userData = new CreateUserDTO($userData);
        return self::createFromDTO($userData);
    }

    /**
     * Create a User from a CreateUserDTO.
     * @param CreateUserDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function createFromDTO(CreateUserDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->post('/accounts/1/users', [
            'multipart' => $dto->toApiArray()
        ]);
        return new self(json_decode($response->getBody(), true));
    }

    /**
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/users/{$id}");
        return new self(json_decode($response->getBody(), true));
    }

    /**
     * Update an existing user.
     * @param int $id
     * @param UpdateUserDTO|mixed[] $userData
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $id, array | UpdateUserDTO $userData): self
    {
        $userData = is_array($userData) ? new UpdateUserDTO($userData) : $userData;

        return self::updateFromDTO($id, $userData);
    }

    /**
     * Update a user from a UpdateUserDTO.
     * @param int $id
     * @param UpdateUserDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function updateFromDTO(int $id, UpdateUserDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->put("/users/{$id}", [
            'multipart' => $dto->toApiArray()
        ]);

        return new self(json_decode($response->getBody(), true));
    }

    /**
     * Fetch all courses
     * @param mixed[] $params
     * @return User[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();

        $response = self::$apiClient->get("/accounts/{$accountId}/users", [
            'query' => $params
        ]);

        $users = json_decode($response->getBody(), true);

        return array_map(function ($user) {
            return new self($user);
        }, $users);
    }
}
