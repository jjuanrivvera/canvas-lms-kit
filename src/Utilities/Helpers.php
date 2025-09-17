<?php

declare(strict_types=1);

/**
 * Convert a string from camelCase or PascalCase to snake_case.
 *
 * @param string $string The string to convert
 *
 * @return string The converted string in snake_case format
 */
function str_to_snake_case(string $string): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
}
