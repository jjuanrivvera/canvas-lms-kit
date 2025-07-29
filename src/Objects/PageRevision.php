<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * PageRevision Object
 *
 * Represents a revision of a Canvas Page. Each revision captures the state
 * of a page at a specific point in time, including who edited it and when.
 *
 * @package CanvasLMS\Objects
 */
class PageRevision
{
    /**
     * An identifier for this revision of the page
     */
    public ?int $revisionId = null;

    /**
     * The time when this revision was saved
     */
    public ?string $updatedAt = null;

    /**
     * Whether this is the latest revision or not
     */
    public ?bool $latest = null;

    /**
     * The User who saved this revision
     * @var array<string, mixed>|null
     */
    public ?array $editedBy = null;

    /**
     * The historic URL of the page
     */
    public ?string $url = null;

    /**
     * The historic page title
     */
    public ?string $title = null;

    /**
     * The historic page contents
     */
    public ?string $body = null;

    /**
     * Constructor
     *
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
     * Get revision ID
     *
     * @return int|null
     */
    public function getRevisionId(): ?int
    {
        return $this->revisionId;
    }

    /**
     * Set revision ID
     *
     * @param int|null $revisionId
     * @return void
     */
    public function setRevisionId(?int $revisionId): void
    {
        $this->revisionId = $revisionId;
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at timestamp
     *
     * @param string|null $updatedAt
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get latest status
     *
     * @return bool|null
     */
    public function getLatest(): ?bool
    {
        return $this->latest;
    }

    /**
     * Set latest status
     *
     * @param bool|null $latest
     * @return void
     */
    public function setLatest(?bool $latest): void
    {
        $this->latest = $latest;
    }

    /**
     * Get edited by user
     *
     * @return array<string, mixed>|null
     */
    public function getEditedBy(): ?array
    {
        return $this->editedBy;
    }

    /**
     * Set edited by user
     *
     * @param array<string, mixed>|null $editedBy
     * @return void
     */
    public function setEditedBy(?array $editedBy): void
    {
        $this->editedBy = $editedBy;
    }

    /**
     * Get URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set URL
     *
     * @param string|null $url
     * @return void
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string|null $title
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get body
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set body
     *
     * @param string|null $body
     * @return void
     */
    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    /**
     * Check if this is the latest revision
     *
     * @return bool
     */
    public function isLatest(): bool
    {
        return $this->latest === true;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'revision_id' => $this->revisionId,
            'updated_at' => $this->updatedAt,
            'latest' => $this->latest,
            'edited_by' => $this->editedBy,
            'url' => $this->url,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
