<?php

declare(strict_types=1);

namespace Tests\Objects;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Objects\GradebookHistoryGrader;

class GradebookHistoryGraderTest extends TestCase
{
    public function testConstructorWithFullData(): void
    {
        $data = [
            'id' => 456,
            'name' => 'John Teacher',
            'assignments' => [789, 790, 791]
        ];

        $grader = new GradebookHistoryGrader($data);

        $this->assertEquals(456, $grader->id);
        $this->assertEquals('John Teacher', $grader->name);
        $this->assertEquals([789, 790, 791], $grader->assignments);
    }

    public function testConstructorWithEmptyData(): void
    {
        $grader = new GradebookHistoryGrader([]);

        $this->assertNull($grader->id);
        $this->assertNull($grader->name);
        $this->assertEquals([], $grader->assignments);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test Grader',
            'assignments' => [1, 2, 3]
        ];

        $grader = GradebookHistoryGrader::fromArray($data);

        $this->assertInstanceOf(GradebookHistoryGrader::class, $grader);
        $this->assertEquals(123, $grader->id);
        $this->assertEquals('Test Grader', $grader->name);
    }

    public function testWorkedOnAssignment(): void
    {
        $grader = new GradebookHistoryGrader([
            'id' => 456,
            'name' => 'John Teacher',
            'assignments' => [789, 790, 791]
        ]);

        $this->assertTrue($grader->workedOnAssignment(789));
        $this->assertTrue($grader->workedOnAssignment(790));
        $this->assertTrue($grader->workedOnAssignment(791));
        $this->assertFalse($grader->workedOnAssignment(792));
        $this->assertFalse($grader->workedOnAssignment(0));
    }

    public function testGetAssignmentCount(): void
    {
        $grader = new GradebookHistoryGrader([
            'id' => 456,
            'name' => 'John Teacher',
            'assignments' => [789, 790, 791]
        ]);

        $this->assertEquals(3, $grader->getAssignmentCount());

        $emptyGrader = new GradebookHistoryGrader([]);
        $this->assertEquals(0, $emptyGrader->getAssignmentCount());
    }

    public function testAssignmentsAreIntegerCast(): void
    {
        $data = [
            'id' => '456',
            'name' => 'John Teacher',
            'assignments' => ['789', '790', '791']
        ];

        $grader = new GradebookHistoryGrader($data);

        $this->assertSame(456, $grader->id);
        $this->assertSame([789, 790, 791], $grader->assignments);
    }
}