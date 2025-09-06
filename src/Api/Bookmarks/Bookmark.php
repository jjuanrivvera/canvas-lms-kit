<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Bookmarks;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Bookmarks\CreateBookmarkDTO;
use CanvasLMS\Dto\Bookmarks\UpdateBookmarkDTO;

/**
 * Canvas LMS Bookmarks API
 *
 * The Bookmarks API allows users to create and manage bookmarks for various Canvas resources
 * including courses, groups, users, and other Canvas entities. Bookmarks are user-specific
 * and always operate in the context of the current user.
 *
 * @see https://canvas.instructure.com/doc/api/bookmarks.html
 */
class Bookmark extends AbstractBaseApi
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $url = null;
    public ?int $position = null;
    public ?string $data = null;

    /**
     * Get the API endpoint
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        return '/users/self/bookmarks';
    }

    /**
     * Get the API property name for this resource
     *
     * @return string
     */
    protected static function getApiPropertyName(): string
    {
        return 'bookmark';
    }

    /**
     * Create a new bookmark
     *
     * @param array<string, mixed>|CreateBookmarkDTO $data Bookmark data
     * @return self
     */
    public static function create(array|CreateBookmarkDTO $data): self
    {
        if (is_array($data)) {
            $data = CreateBookmarkDTO::fromArray($data);
        }

        $response = self::$apiClient->post(
            self::getEndpoint(),
            $data->toApiArray()
        );

        $bookmarkData = json_decode($response->getBody()->getContents(), true);
        return new self($bookmarkData);
    }

    /**
     * Fetch all bookmarks (alias for get)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<self>
     */
    public static function fetchAll(array $params = []): array
    {
        return self::get($params);
    }

    /**
     * Find a bookmark by ID
     *
     * @param int $id Bookmark ID
     * @return self
     */
    public static function find(int $id): self
    {
        $response = self::$apiClient->get(
            self::getEndpoint() . '/' . $id
        );

        $bookmarkData = json_decode($response->getBody()->getContents(), true);
        return new self($bookmarkData);
    }

    /**
     * Update a bookmark
     *
     * @param int $id Bookmark ID
     * @param array<string, mixed>|UpdateBookmarkDTO $data Update data
     * @return self
     */
    public static function update(int $id, array|UpdateBookmarkDTO $data): self
    {
        if (is_array($data)) {
            $data = UpdateBookmarkDTO::fromArray($data);
        }

        $response = self::$apiClient->put(
            self::getEndpoint() . '/' . $id,
            $data->toApiArray()
        );

        $bookmarkData = json_decode($response->getBody()->getContents(), true);
        return new self($bookmarkData);
    }

    /**
     * Save the bookmark (create or update)
     *
     * @return self
     */
    public function save(): self
    {
        $data = [
            'name' => $this->name,
            'url' => $this->url,
            'position' => $this->position,
            'data' => $this->data,
        ];

        $data = array_filter($data, fn($value) => $value !== null);

        if ($this->id === null) {
            $dto = CreateBookmarkDTO::fromArray($data);
            $response = self::$apiClient->post(
                self::getEndpoint(),
                $dto->toApiArray()
            );
        } else {
            $dto = UpdateBookmarkDTO::fromArray($data);
            $response = self::$apiClient->put(
                self::getEndpoint() . '/' . $this->id,
                $dto->toApiArray()
            );
        }

        $bookmarkData = json_decode($response->getBody()->getContents(), true);

        // Update instance properties with response data
        foreach ($bookmarkData as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Delete the bookmark
     *
     * @return self
     */
    public function delete(): self
    {
        if ($this->id === null) {
            throw new \RuntimeException('Cannot delete bookmark without ID');
        }

        $response = self::$apiClient->delete(
            self::getEndpoint() . '/' . $this->id
        );

        json_decode($response->getBody()->getContents(), true);

        return $this;
    }
}
