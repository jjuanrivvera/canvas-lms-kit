<?php

declare(strict_types=1);

namespace Tests\Api\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Modules\ModuleAssignmentOverride;

class ModuleAssignmentOverrideTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $override = new ModuleAssignmentOverride();
        
        // Properties should not be initialized when no data is provided
        $this->assertFalse(property_exists($override, 'id') && isset($override->id));
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'id' => 123,
            'context_module_id' => 456,
            'title' => 'Section Override',
            'students' => [
                ['id' => 1, 'name' => 'Student 1'],
                ['id' => 2, 'name' => 'Student 2']
            ],
            'course_section' => ['id' => 789, 'name' => 'Section A'],
            'group' => null
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals(123, $override->getId());
        $this->assertEquals(456, $override->getContextModuleId());
        $this->assertEquals('Section Override', $override->getTitle());
        $this->assertEquals($data['students'], $override->getStudents());
        $this->assertEquals($data['course_section'], $override->getCourseSection());
        $this->assertNull($override->getGroup());
    }

    public function testConstructorWithSnakeCaseConversion(): void
    {
        $data = [
            'id' => 123,
            'context_module_id' => 456,
            'course_section' => ['id' => 789]
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals(123, $override->getId());
        $this->assertEquals(456, $override->getContextModuleId());
        $this->assertEquals(['id' => 789], $override->getCourseSection());
    }

    public function testGettersAndSetters(): void
    {
        $override = new ModuleAssignmentOverride();

        $override->setId(999);
        $this->assertEquals(999, $override->getId());

        $override->setContextModuleId(888);
        $this->assertEquals(888, $override->getContextModuleId());

        $override->setTitle('Custom Override');
        $this->assertEquals('Custom Override', $override->getTitle());

        $students = [['id' => 10, 'name' => 'Test Student']];
        $override->setStudents($students);
        $this->assertEquals($students, $override->getStudents());

        $section = ['id' => 20, 'name' => 'Test Section'];
        $override->setCourseSection($section);
        $this->assertEquals($section, $override->getCourseSection());

        $group = ['id' => 30, 'name' => 'Test Group'];
        $override->setGroup($group);
        $this->assertEquals($group, $override->getGroup());
    }

    public function testSettersWithNull(): void
    {
        $override = new ModuleAssignmentOverride([
            'students' => [['id' => 1]],
            'course_section' => ['id' => 2],
            'group' => ['id' => 3]
        ]);

        $override->setStudents(null);
        $this->assertNull($override->getStudents());

        $override->setCourseSection(null);
        $this->assertNull($override->getCourseSection());

        $override->setGroup(null);
        $this->assertNull($override->getGroup());
    }

    public function testSectionOverride(): void
    {
        $data = [
            'id' => 123,
            'context_module_id' => 456,
            'title' => 'Section 1 Override',
            'course_section' => [
                'id' => 789,
                'name' => 'Section 1'
            ],
            'students' => null,
            'group' => null
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals('Section 1 Override', $override->getTitle());
        $this->assertNotNull($override->getCourseSection());
        $this->assertEquals(789, $override->getCourseSection()['id']);
        $this->assertNull($override->getStudents());
        $this->assertNull($override->getGroup());
    }

    public function testStudentOverride(): void
    {
        $data = [
            'id' => 123,
            'context_module_id' => 456,
            'title' => 'Special Students',
            'students' => [
                ['id' => 100, 'name' => 'Alice'],
                ['id' => 101, 'name' => 'Bob'],
                ['id' => 102, 'name' => 'Charlie']
            ],
            'course_section' => null,
            'group' => null
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals('Special Students', $override->getTitle());
        $this->assertNotNull($override->getStudents());
        $this->assertCount(3, $override->getStudents());
        $this->assertEquals('Alice', $override->getStudents()[0]['name']);
        $this->assertNull($override->getCourseSection());
        $this->assertNull($override->getGroup());
    }

    public function testGroupOverride(): void
    {
        $data = [
            'id' => 123,
            'context_module_id' => 456,
            'title' => 'Group Project Team',
            'group' => [
                'id' => 200,
                'name' => 'Team Alpha'
            ],
            'students' => null,
            'course_section' => null
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals('Group Project Team', $override->getTitle());
        $this->assertNotNull($override->getGroup());
        $this->assertEquals(200, $override->getGroup()['id']);
        $this->assertEquals('Team Alpha', $override->getGroup()['name']);
        $this->assertNull($override->getStudents());
        $this->assertNull($override->getCourseSection());
    }

    public function testConstructorIgnoresUnknownProperties(): void
    {
        $data = [
            'id' => 123,
            'unknown_property' => 'should be ignored',
            'another_unknown' => 'also ignored',
            'title' => 'Valid Override'
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals(123, $override->getId());
        $this->assertEquals('Valid Override', $override->getTitle());
        
        // Verify unknown properties were not set
        $this->assertFalse(property_exists($override, 'unknownProperty'));
        $this->assertFalse(property_exists($override, 'anotherUnknown'));
    }

    public function testNumericKeysInData(): void
    {
        // Test that numeric keys in data array are handled properly
        $data = [
            0 => 'ignored',
            'id' => 123,
            1 => 'also ignored',
            'title' => 'Test Override'
        ];

        $override = new ModuleAssignmentOverride($data);

        $this->assertEquals(123, $override->getId());
        $this->assertEquals('Test Override', $override->getTitle());
    }
}