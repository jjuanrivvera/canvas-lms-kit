<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\OverrideTarget;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\OverrideTarget
 */
class OverrideTargetTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $overrideTarget = new OverrideTarget();

        $this->assertNull($overrideTarget->type);
        $this->assertNull($overrideTarget->id);
        $this->assertNull($overrideTarget->name);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'type' => 'student',
            'id' => 12345,
            'name' => 'John Doe',
        ];

        $overrideTarget = new OverrideTarget($data);

        $this->assertEquals('student', $overrideTarget->type);
        $this->assertEquals(12345, $overrideTarget->id);
        $this->assertEquals('John Doe', $overrideTarget->name);
    }

    public function testGettersAndSetters(): void
    {
        $overrideTarget = new OverrideTarget();

        $overrideTarget->setType('section');
        $this->assertEquals('section', $overrideTarget->getType());

        $overrideTarget->setId(67890);
        $this->assertEquals(67890, $overrideTarget->getId());

        $overrideTarget->setName('Section A');
        $this->assertEquals('Section A', $overrideTarget->getName());
    }

    public function testIsStudent(): void
    {
        $overrideTarget = new OverrideTarget();

        // Not a student when type is null
        $this->assertFalse($overrideTarget->isStudent());

        // Not a student with other types
        $overrideTarget->setType('section');
        $this->assertFalse($overrideTarget->isStudent());

        $overrideTarget->setType('group');
        $this->assertFalse($overrideTarget->isStudent());

        // Is a student when type is 'student'
        $overrideTarget->setType('student');
        $this->assertTrue($overrideTarget->isStudent());
    }

    public function testIsSection(): void
    {
        $overrideTarget = new OverrideTarget();

        // Not a section when type is null
        $this->assertFalse($overrideTarget->isSection());

        // Not a section with other types
        $overrideTarget->setType('student');
        $this->assertFalse($overrideTarget->isSection());

        $overrideTarget->setType('group');
        $this->assertFalse($overrideTarget->isSection());

        // Is a section when type is 'section'
        $overrideTarget->setType('section');
        $this->assertTrue($overrideTarget->isSection());
    }

    public function testIsGroup(): void
    {
        $overrideTarget = new OverrideTarget();

        // Not a group when type is null
        $this->assertFalse($overrideTarget->isGroup());

        // Not a group with other types
        $overrideTarget->setType('student');
        $this->assertFalse($overrideTarget->isGroup());

        $overrideTarget->setType('section');
        $this->assertFalse($overrideTarget->isGroup());

        // Is a group when type is 'group'
        $overrideTarget->setType('group');
        $this->assertTrue($overrideTarget->isGroup());
    }

    public function testToArray(): void
    {
        // Test with empty data
        $overrideTarget = new OverrideTarget();
        $this->assertEquals([], $overrideTarget->toArray());

        // Test with type only
        $overrideTarget->setType('student');

        $expected = [
            'type' => 'student',
        ];

        $this->assertEquals($expected, $overrideTarget->toArray());

        // Test with all data
        $overrideTarget->setId(12345);
        $overrideTarget->setName('Jane Smith');

        $expected = [
            'type' => 'student',
            'id' => 12345,
            'name' => 'Jane Smith',
        ];

        $this->assertEquals($expected, $overrideTarget->toArray());
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        // OverrideTarget properties don't use snake_case, but test the conversion anyway
        $data = [
            'type' => 'section',
            'id' => 99999,
            'name' => 'Test Section',
            'unknown_property' => 'should be ignored',
        ];

        $overrideTarget = new OverrideTarget($data);

        $this->assertEquals('section', $overrideTarget->type);
        $this->assertEquals(99999, $overrideTarget->id);
        $this->assertEquals('Test Section', $overrideTarget->name);
    }

    public function testDifferentOverrideTypes(): void
    {
        $types = ['student', 'section', 'group'];

        foreach ($types as $type) {
            $overrideTarget = new OverrideTarget(['type' => $type]);
            $this->assertEquals($type, $overrideTarget->type);
        }
    }

    public function testSettersWithNull(): void
    {
        $overrideTarget = new OverrideTarget([
            'type' => 'student',
            'id' => 12345,
            'name' => 'Test Student',
        ]);

        $overrideTarget->setType(null);
        $overrideTarget->setId(null);
        $overrideTarget->setName(null);

        $this->assertNull($overrideTarget->getType());
        $this->assertNull($overrideTarget->getId());
        $this->assertNull($overrideTarget->getName());
    }

    public function testNumericIdHandling(): void
    {
        // Test that ID can handle both int and string representations
        $overrideTarget = new OverrideTarget(['id' => '12345']);
        $this->assertEquals(12345, $overrideTarget->getId());

        $overrideTarget = new OverrideTarget(['id' => 67890]);
        $this->assertEquals(67890, $overrideTarget->getId());
    }
}
