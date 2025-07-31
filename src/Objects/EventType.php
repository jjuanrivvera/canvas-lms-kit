<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Event types and utilities
 *
 * @package CanvasLMS\Objects
 */
class EventType
{
    /**
     * Generic event type
     */
    public const EVENT = 'event';

    /**
     * Assignment event type
     */
    public const ASSIGNMENT = 'assignment';

    /**
     * Quiz event type
     */
    public const QUIZ = 'quiz';

    /**
     * Discussion topic event type
     */
    public const DISCUSSION_TOPIC = 'discussion_topic';

    /**
     * All valid event types
     * @var array<string>
     */
    public const VALID_TYPES = [
        self::EVENT,
        self::ASSIGNMENT,
        self::QUIZ,
        self::DISCUSSION_TOPIC
    ];

    /**
     * Event types that are automatically created by Canvas
     * @var array<string>
     */
    public const AUTO_CREATED_TYPES = [
        self::ASSIGNMENT,
        self::QUIZ,
        self::DISCUSSION_TOPIC
    ];

    /**
     * Check if an event type is valid
     * @param string $type
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }

    /**
     * Check if an event type is automatically created by Canvas
     * @param string $type
     * @return bool
     */
    public static function isAutoCreated(string $type): bool
    {
        return in_array($type, self::AUTO_CREATED_TYPES, true);
    }

    /**
     * Get a human-readable label for an event type
     * @param string $type
     * @return string
     */
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::EVENT => 'Event',
            self::ASSIGNMENT => 'Assignment',
            self::QUIZ => 'Quiz',
            self::DISCUSSION_TOPIC => 'Discussion',
            default => 'Unknown'
        };
    }

    /**
     * Get the icon or symbol for an event type (for UI purposes)
     * @param string $type
     * @return string
     */
    public static function getIcon(string $type): string
    {
        return match ($type) {
            self::EVENT => '📅',
            self::ASSIGNMENT => '📝',
            self::QUIZ => '📊',
            self::DISCUSSION_TOPIC => '💬',
            default => '❓'
        };
    }

    /**
     * Check if an event type supports custom creation
     * (i.e., not automatically created by Canvas)
     * @param string $type
     * @return bool
     */
    public static function supportsCustomCreation(string $type): bool
    {
        return $type === self::EVENT;
    }

    /**
     * Get the API endpoint prefix for a specific event type
     * @param string $type
     * @return string|null
     */
    public static function getApiPrefix(string $type): ?string
    {
        return match ($type) {
            self::ASSIGNMENT => 'assignments',
            self::QUIZ => 'quizzes',
            self::DISCUSSION_TOPIC => 'discussion_topics',
            self::EVENT => 'calendar_events',
            default => null
        };
    }
}
