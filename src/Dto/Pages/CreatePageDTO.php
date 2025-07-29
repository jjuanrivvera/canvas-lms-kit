<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Pages;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating pages in Canvas LMS
 *
 * This DTO handles the creation of new pages with all the necessary
 * fields supported by the Canvas API.
 *
 * @package CanvasLMS\Dto\Pages
 */
class CreatePageDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'wiki_page';

    /**
     * Page title (required)
     */
    public string $title = '';

    /**
     * Page body content (HTML)
     */
    public string $body = '';

    /**
     * Whether the page is published
     */
    public ?bool $published = null;

    /**
     * Whether this page is the front page for the course
     */
    public ?bool $frontPage = null;

    /**
     * Who can edit the page (teachers, students, members, public)
     */
    public ?string $editingRoles = null;

    /**
     * Whether to notify users of the page creation
     */
    public ?bool $notifyOfUpdate = null;

    /**
     * Scheduled publication date
     */
    public ?string $publishAt = null;

    /**
     * Constructor with validation
     *
     * @param array<string, mixed> $data Initial data
     * @throws \InvalidArgumentException If required fields are missing
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Validate required fields
        if (empty($this->title)) {
            throw new \InvalidArgumentException('Page title is required');
        }
    }

    /**
     * Get page title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set page title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get page body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set page body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Get published status
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * Set published status
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get front page status
     */
    public function getFrontPage(): ?bool
    {
        return $this->frontPage;
    }

    /**
     * Set front page status
     */
    public function setFrontPage(?bool $frontPage): void
    {
        $this->frontPage = $frontPage;
    }

    /**
     * Get editing roles
     */
    public function getEditingRoles(): ?string
    {
        return $this->editingRoles;
    }

    /**
     * Set editing roles
     */
    public function setEditingRoles(?string $editingRoles): void
    {
        $this->editingRoles = $editingRoles;
    }

    /**
     * Get notify of update status
     */
    public function getNotifyOfUpdate(): ?bool
    {
        return $this->notifyOfUpdate;
    }

    /**
     * Set notify of update status
     */
    public function setNotifyOfUpdate(?bool $notifyOfUpdate): void
    {
        $this->notifyOfUpdate = $notifyOfUpdate;
    }

    /**
     * Get publish at date
     */
    public function getPublishAt(): ?string
    {
        return $this->publishAt;
    }

    /**
     * Set publish at date
     */
    public function setPublishAt(?string $publishAt): void
    {
        $this->publishAt = $publishAt;
    }
}
