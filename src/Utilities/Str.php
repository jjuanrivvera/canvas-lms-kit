<?php

declare(strict_types=1);

namespace CanvasLMS\Utilities;

/**
 * String manipulation utility class.
 *
 * Provides common string transformation methods for the Canvas LMS SDK.
 */
class Str
{
    /**
     * Convert a string from camelCase or PascalCase to snake_case.
     *
     * @param string $string The string to convert
     *
     * @return string The converted string in snake_case format
     */
    public static function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string) ?? '');
    }
}
