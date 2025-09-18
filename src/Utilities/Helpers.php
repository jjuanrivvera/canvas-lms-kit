<?php

declare(strict_types=1);

use CanvasLMS\Utilities\Str;

/**
 * Convert a string from camelCase or PascalCase to snake_case.
 *
 * @deprecated Use \CanvasLMS\Utilities\Str::toSnakeCase() instead
 *
 * @param string $string The string to convert
 *
 * @return string The converted string in snake_case format
 */
function str_to_snake_case(string $string): string
{
    @trigger_error(
        'Function str_to_snake_case() is deprecated. ' .
        'Use \CanvasLMS\Utilities\Str::toSnakeCase() instead.',
        E_USER_DEPRECATED
    );

    return Str::toSnakeCase($string);
}
