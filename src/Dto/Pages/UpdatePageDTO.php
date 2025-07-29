<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Pages;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating pages in Canvas LMS
 *
 * This DTO handles the updating of existing pages with all the necessary
 * fields supported by the Canvas API. All fields are nullable to support
 * partial updates.
 *
 * @package CanvasLMS\Dto\Pages
 */
class UpdatePageDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'wiki_page';

    /**
     * Page title
     */
    public ?string $title = null;

    /**
     * Page body content (HTML)
     */
    public ?string $body = null;

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
     * Whether to notify users of the page update
     */
    public ?bool $notifyOfUpdate = null;

    /**
     * Scheduled publication date
     */
    public ?string $publishAt = null;

    /**
     * Get page title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set page title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get page body
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set page body
     */
    public function setBody(?string $body): void
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
