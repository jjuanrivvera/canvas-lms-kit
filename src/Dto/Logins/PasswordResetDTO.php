<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Logins;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for password reset request
 */
class PasswordResetDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The user's email address to send password recovery to
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * Transform DTO properties to Canvas API multipart format
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $this->validateParameters();

        $modifiedProperties = [];

        if (!empty($this->email)) {
            $modifiedProperties[] = [
                'name' => 'pseudonym_session[unique_id_forgot]',
                'contents' => $this->email
            ];
        }

        return $modifiedProperties;
    }

    /**
     * Validate parameters
     *
     * @throws \InvalidArgumentException
     */
    private function validateParameters(): void
    {
        if (empty($this->email)) {
            throw new \InvalidArgumentException('Email is required for password reset');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }

    // Getters and setters
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
