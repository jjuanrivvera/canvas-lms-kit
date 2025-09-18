<?php

declare(strict_types=1);

namespace Tests\Utilities;

use CanvasLMS\Utilities\Str;
use PHPUnit\Framework\TestCase;

/**
 * Test class for the Str utility class.
 *
 * @covers \CanvasLMS\Utilities\Str
 */
class StrTest extends TestCase
{
    /**
     * Test toSnakeCase method with various input strings.
     *
     * @dataProvider toSnakeCaseProvider
     */
    public function testToSnakeCase(string $input, string $expected): void
    {
        $result = Str::toSnakeCase($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for toSnakeCase tests.
     *
     * @return array<array{string, string}>
     */
    public static function toSnakeCaseProvider(): array
    {
        return [
            // Basic camelCase
            ['camelCase', 'camel_case'],
            ['someVariableName', 'some_variable_name'],

            // PascalCase
            ['PascalCase', 'pascal_case'],
            ['SomeClassName', 'some_class_name'],

            // Already snake_case
            ['already_snake_case', 'already_snake_case'],
            ['snake_case_example', 'snake_case_example'],

            // Single word
            ['word', 'word'],
            ['WORD', 'w_o_r_d'],

            // All uppercase (each letter treated as separate word)
            ['UPPERCASE', 'u_p_p_e_r_c_a_s_e'],
            ['ALLCAPS', 'a_l_l_c_a_p_s'],

            // Mixed case with numbers
            ['mixedCASE123', 'mixed_c_a_s_e123'],
            ['someVar2Test', 'some_var2_test'],

            // Edge cases
            ['a', 'a'],
            ['A', 'a'],
            ['aB', 'a_b'],
            ['AB', 'a_b'],
            ['ABC', 'a_b_c'],

            // Multiple consecutive capitals
            ['XMLHttpRequest', 'x_m_l_http_request'],
            ['HTMLParser', 'h_t_m_l_parser'],

            // Starting with lowercase
            ['iPhone', 'i_phone'],
            ['eBay', 'e_bay'],

            // Empty string
            ['', ''],
        ];
    }

    /**
     * Test that the deprecated global function still works and triggers deprecation notice.
     */
    public function testDeprecatedGlobalFunction(): void
    {
        // Capture the deprecation notice
        $deprecationTriggered = false;
        set_error_handler(
            function ($errno, $errstr) use (&$deprecationTriggered) {
                if ($errno === E_USER_DEPRECATED) {
                    $deprecationTriggered = true;
                    $this->assertStringContainsString(
                        'str_to_snake_case() is deprecated',
                        $errstr
                    );
                    $this->assertStringContainsString(
                        'Use \CanvasLMS\Utilities\Str::toSnakeCase() instead',
                        $errstr
                    );

                    return true;
                }

                return false;
            }
        );

        // Call the deprecated function
        $result = str_to_snake_case('camelCase');

        // Restore previous error handler
        restore_error_handler();

        // Verify the function still works
        $this->assertEquals('camel_case', $result);

        // Verify deprecation notice was triggered
        $this->assertTrue($deprecationTriggered, 'Deprecation notice was not triggered');
    }
}
