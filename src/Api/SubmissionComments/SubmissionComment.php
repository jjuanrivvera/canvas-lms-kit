<?php

declare(strict_types=1);

namespace CanvasLMS\Api\SubmissionComments;

use Exception;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Dto\SubmissionComments\CreateSubmissionCommentDTO;
use CanvasLMS\Dto\SubmissionComments\UpdateSubmissionCommentDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Submission Comments API
 *
 * Provides functionality to manage submission comments in Canvas LMS.
 * This class handles updating and deleting submission comments for specific submissions.
 * Requires Course, Assignment, and User ID context for all operations.
 *
 * Note: Creating submission comments is typically done through the Submission API
 * when creating or updating submissions. This class handles standalone comment operations.
 *
 * Usage Examples:
 *
 * ```php
 * // Set triple context (required for all operations)
 * $course = Course::find(123);
 * $assignment = Assignment::find(456);
 * SubmissionComment::setCourse($course);
 * SubmissionComment::setAssignment($assignment);
 * SubmissionComment::setUserId(789); // The submission user ID
 *
 * // Update an existing comment
 * $updatedComment = SubmissionComment::update(101, [
 *     'text_comment' => 'Updated comment text'
 * ]);
 *
 * // Delete a comment
 * $success = SubmissionComment::delete(101);
 *
 * // Upload a file to a comment
 * $fileData = [
 *     'name' => 'feedback.pdf',
 *     'size' => 1024000,
 *     'content_type' => 'application/pdf',
 *     'parent_folder_path' => '/submission_comments'
 * ];
 * $fileInfo = SubmissionComment::uploadFile($fileData);
 * ```
 *
 * @package CanvasLMS\Api\SubmissionComments
 */
class SubmissionComment extends AbstractBaseApi
{
    /**
     * Course context (required)
     * @var Course
     */
    protected static ?Course $course = null;

    /**
     * Assignment context (required)
     * @var Assignment
     */
    protected static ?Assignment $assignment = null;

    /**
     * User ID context (required) - the user whose submission this comment belongs to
     * @var int
     */
    protected static ?int $userId = null;

    /**
     * Comment unique identifier
     */
    public ?int $id = null;

    /**
     * ID of the user who authored this comment
     */
    public ?int $authorId = null;

    /**
     * Name of the user who authored this comment
     */
    public ?string $authorName = null;

    /**
     * The comment text content
     */
    public ?string $comment = null;

    /**
     * Date and time when comment was created
     */
    public ?string $createdAt = null;

    /**
     * Date and time when comment was last edited
     */
    public ?string $editedAt = null;

    /**
     * Author user object (contains display information)
     * @var object|null
     */
    public ?object $author = null;

    /**
     * Media comment data (audio/video comment information)
     * @var array<mixed>|null
     */
    public ?array $mediaComment = null;

    /**
     * Set the course context
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Set the assignment context
     */
    public static function setAssignment(Assignment $assignment): void
    {
        self::$assignment = $assignment;
    }

    /**
     * Set the user ID context (the submission owner)
     */
    public static function setUserId(int $userId): void
    {
        self::$userId = $userId;
    }

    /**
     * Check if course context is set
     * @throws Exception
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course)) {
            throw new Exception(
                'Course context must be set before calling SubmissionComment methods. ' .
                'Use SubmissionComment::setCourse($course)'
            );
        }
        return true;
    }

    /**
     * Check if assignment context is set
     * @throws Exception
     */
    public static function checkAssignment(): bool
    {
        if (!isset(self::$assignment)) {
            throw new Exception(
                'Assignment context must be set before calling SubmissionComment methods. ' .
                'Use SubmissionComment::setAssignment($assignment)'
            );
        }
        return true;
    }

    /**
     * Check if user ID context is set
     * @throws Exception
     */
    public static function checkUserId(): bool
    {
        if (!isset(self::$userId)) {
            throw new Exception(
                'User ID context must be set before calling SubmissionComment methods. ' .
                'Use SubmissionComment::setUserId($userId)'
            );
        }
        return true;
    }

    /**
     * Check if all contexts are set
     * @throws Exception
     */
    public static function checkContexts(): bool
    {
        self::checkCourse();
        self::checkAssignment();
        self::checkUserId();
        return true;
    }

    /**
     * Clear static contexts to prevent memory leaks in long-running processes
     */
    public static function clearContext(): void
    {
        if (isset(self::$course)) {
            unset(self::$course);
        }
        if (isset(self::$assignment)) {
            unset(self::$assignment);
        }
        if (isset(self::$userId)) {
            unset(self::$userId);
        }
    }

    /**
     * Update an existing submission comment
     * @param array<string, mixed>|UpdateSubmissionCommentDTO $data
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $commentId, array|UpdateSubmissionCommentDTO $data): self
    {
        self::checkApiClient();
        self::checkContexts();

        $dto = $data instanceof UpdateSubmissionCommentDTO ? $data : new UpdateSubmissionCommentDTO($data);

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/comments/%d',
            self::$course->id,
            self::$assignment->id,
            self::$userId,
            $commentId
        );

        $response = self::$apiClient->request('PUT', $endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $commentData = self::parseJsonResponse($response);

        return new self($commentData);
    }

    /**
     * Delete a submission comment
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function delete(int $commentId): self
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/comments/%d',
            self::$course->id,
            self::$assignment->id,
            self::$userId,
            $commentId
        );
        self::$apiClient->delete($endpoint);
        return new self([]);
    }

    /**
     * Find a submission comment by ID (required by ApiInterface)
     * Note: Canvas API doesn't provide a direct endpoint for individual comment retrieval
     * @throws Exception
     */
    public static function find(int $id, array $params = []): self
    {
        throw new Exception(
            'SubmissionComment::find() is not supported by Canvas API. Comments are retrieved through submissions.'
        );
    }

    /**
     * Fetch all submission comments (required by ApiInterface)
     * Note: Canvas API doesn't provide a direct endpoint for listing comments independently
     * @param array<string, mixed> $params
     * @return array<self>
     * @throws Exception
     */
    public static function get(array $params = []): array
    {
        throw new Exception(
            'SubmissionComment::get() is not supported by Canvas API. Comments are retrieved through submissions.'
        );
    }

    /**
     * Upload a file to attach to a submission comment
     * @param array<string, mixed> $fileData File upload data
     * @return array<string, mixed> File upload response
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function uploadFile(array $fileData): array
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/comments/files',
            self::$course->id,
            self::$assignment->id,
            self::$userId
        );

        $response = self::$apiClient->request('POST', $endpoint, [
            'json' => $fileData
        ]);

        return self::parseJsonResponse($response);
    }

    /**
     * Save the comment (update only)
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if (!isset($this->id)) {
            throw new CanvasApiException('Cannot save comment without ID');
        }

        self::checkApiClient();
        self::checkContexts();

        $data = $this->toDtoArray();
        $dto = new UpdateSubmissionCommentDTO($data);

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/comments/%d',
            self::$course->id,
            self::$assignment->id,
            self::$userId,
            $this->id
        );

        $response = self::$apiClient->request('PUT', $endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $commentData = self::parseJsonResponse($response);

        // Update the current instance with the response data
        foreach ($commentData as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $camelKey) && !is_null($value)) {
                $this->{$camelKey} = $value;
            }
        }

        return $this;
    }

    // Getter and setter methods for all properties

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(?int $authorId): void
    {
        $this->authorId = $authorId;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): void
    {
        $this->authorName = $authorName;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getEditedAt(): ?string
    {
        return $this->editedAt;
    }

    public function setEditedAt(?string $editedAt): void
    {
        $this->editedAt = $editedAt;
    }

    public function getAuthor(): ?object
    {
        return $this->author;
    }

    public function setAuthor(?object $author): void
    {
        $this->author = $author;
    }

    /**
     * @return array<mixed>|null
     */
    public function getMediaComment(): ?array
    {
        return $this->mediaComment;
    }

    /**
     * @param array<mixed>|null $mediaComment
     */
    public function setMediaComment(?array $mediaComment): void
    {
        $this->mediaComment = $mediaComment;
    }

    /**
     * Get the API endpoint for this resource
     * Note: SubmissionComment is a nested resource under Submission
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'SubmissionComment does not support direct endpoint access. Use context-specific methods.'
        );
    }
}
