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
 * ```php
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
 * ```
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
     * The name of the user that it should be used for sorting groups of users, such
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
     * @param mixed[]|CreateUserDTO $userData
     * @return self
     * @throws Exception
     */
    public static function create(array | CreateUserDTO $userData): self
    {
        self::checkApiClient();

        $userData = is_array($userData) ? new CreateUserDTO($userData) : $userData;

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
     * Find a single user by ID.
     * @param int $id
     * @return self
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

    /**
     * Save the user to the Canvas LMS.
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        self::checkApiClient();

        $data = $this->toDtoArray();
        $accountId = Config::getAccountId();

        // If the user has an ID, update it. Otherwise, create a new user.
        $dto = $this->id ? new UpdateUserDTO($data) : new CreateUserDTO($data);
        $path = $this->id ? "/users/{$this->id}" : "/accounts/{$accountId}/users";
        $method = $this->id ? 'PUT' : 'POST';

        try {
            $response = self::$apiClient->request($method, $path, [
                'multipart' => $dto->toApiArray()
            ]);

            $updatedUserData = json_decode($response->getBody(), true);
            $this->populate($updatedUserData);
        } catch (CanvasApiException $e) {
            return false;
        }

        return true;
    }

    /**
     * Merge current user into another one
     * @param int $destinationUserId The ID of the user to merge into
     * @return bool
     * @throws Exception
     */
    public function mergeInto(int $destinationUserId): bool
    {
        self::checkApiClient();

        try {
            $response = self::$apiClient->put("/users/{$this->id}/merge_into/{$destinationUserId}");

            $this->populate(json_decode($response->getBody(), true));
        } catch (CanvasApiException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSortableName(): string
    {
        return $this->sortableName;
    }

    /**
     * @param string $sortableName
     */
    public function setSortableName(string $sortableName): void
    {
        $this->sortableName = $sortableName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return $this->shortName;
    }

    /**
     * @param string $shortName
     */
    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    /**
     * @return string|null
     */
    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    /**
     * @param string|null $sisUserId
     */
    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
    }

    /**
     * @return int|null
     */
    public function getSisImportId(): ?int
    {
        return $this->sisImportId;
    }

    /**
     * @param int|null $sisImportId
     */
    public function setSisImportId(?int $sisImportId): void
    {
        $this->sisImportId = $sisImportId;
    }

    /**
     * @return string|null
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * @param string|null $integrationId
     */
    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    /**
     * @return string
     */
    public function getLoginId(): string
    {
        return $this->loginId;
    }

    /**
     * @param string $loginId
     */
    public function setLoginId(string $loginId): void
    {
        $this->loginId = $loginId;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @param string|null $avatarUrl
     */
    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    /**
     * @return string|null
     */
    public function getAvatarState(): ?string
    {
        return $this->avatarState;
    }

    /**
     * @param string|null $avatarState
     */
    public function setAvatarState(?string $avatarState): void
    {
        $this->avatarState = $avatarState;
    }

    /**
     * @return mixed[]|null
     */
    public function getEnrollments(): ?array
    {
        return $this->enrollments;
    }

    /**
     * @param mixed[]|null $enrollments
     */
    public function setEnrollments(?array $enrollments): void
    {
        $this->enrollments = $enrollments;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string|null
     */
    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    /**
     * @param string|null $lastLogin
     */
    public function setLastLogin(?string $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string|null
     */
    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    /**
     * @param string|null $timeZone
     */
    public function setTimeZone(?string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return string|null
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @param string|null $bio
     */
    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }
}
