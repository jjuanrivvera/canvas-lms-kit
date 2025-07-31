<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Event context types and utilities
 *
 * @package CanvasLMS\Objects
 */
class EventContext
{
    /**
     * Course context type
     */
    public const COURSE = 'course';

    /**
     * User context type
     */
    public const USER = 'user';

    /**
     * Group context type
     */
    public const GROUP = 'group';

    /**
     * Account context type
     */
    public const ACCOUNT = 'account';

    /**
     * All valid context types
     * @var array<string>
     */
    public const VALID_CONTEXTS = [
        self::COURSE,
        self::USER,
        self::GROUP,
        self::ACCOUNT
    ];

    /**
     * Build a context code from type and ID
     * @param string $type Context type
     * @param int $id Context ID
     * @return string
     */
    public static function buildCode(string $type, int $id): string
    {
        if (!in_array($type, self::VALID_CONTEXTS, true)) {
            throw new \InvalidArgumentException("Invalid context type: {$type}");
        }

        return "{$type}_{$id}";
    }

    /**
     * Parse a context code into type and ID
     * @param string $contextCode Context code (e.g., "course_123")
     * @return array{type: string, id: int}
     */
    public static function parseCode(string $contextCode): array
    {
        $parts = explode('_', $contextCode, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid context code format: {$contextCode}");
        }

        $type = $parts[0];
        $id = $parts[1];

        if (!in_array($type, self::VALID_CONTEXTS, true)) {
            throw new \InvalidArgumentException("Invalid context type in code: {$type}");
        }

        if (!is_numeric($id)) {
            throw new \InvalidArgumentException("Invalid context ID in code: {$id}");
        }

        return [
            'type' => $type,
            'id' => (int) $id
        ];
    }

    /**
     * Get the context type from a context code
     * @param string $contextCode
     * @return string
     */
    public static function getType(string $contextCode): string
    {
        $parsed = self::parseCode($contextCode);
        return $parsed['type'];
    }

    /**
     * Get the context ID from a context code
     * @param string $contextCode
     * @return int
     */
    public static function getId(string $contextCode): int
    {
        $parsed = self::parseCode($contextCode);
        return $parsed['id'];
    }

    /**
     * Check if a context code is valid
     * @param string $contextCode
     * @return bool
     */
    public static function isValid(string $contextCode): bool
    {
        try {
            self::parseCode($contextCode);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get a human-readable label for a context type
     * @param string $type
     * @return string
     */
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::COURSE => 'Course',
            self::USER => 'User',
            self::GROUP => 'Group',
            self::ACCOUNT => 'Account',
            default => 'Unknown'
        };
    }
}
