<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Bookmarks;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating Canvas bookmarks
 *
 * This DTO handles the transformation of bookmark update data
 * into the format expected by the Canvas API. All fields are optional
 * to support partial updates.
 */
class UpdateBookmarkDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart format
     *
     * @var string
     */
    protected string $apiPropertyName = 'bookmark';

    /**
     * The name/label of the bookmark
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The URL being bookmarked
     *
     * @var string|null
     */
    public ?string $url = null;

    /**
     * Position for ordering bookmarks
     *
     * @var int|null
     */
    public ?int $position = null;

    /**
     * Additional metadata about the bookmarked item (JSON string)
     *
     * @var string|null
     */
    public ?string $data = null;

    /**
     * Create DTO from array
     *
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
