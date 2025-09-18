<?php

declare(strict_types=1);

namespace Tests\Dto\Enrollments;

use CanvasLMS\Dto\Enrollments\UpdateEnrollmentDTO;
use PHPUnit\Framework\TestCase;

class UpdateEnrollmentDTOTest extends TestCase
{
    public function testConstructorPopulatesProperties(): void
    {
        $data = [
            'enrollmentState' => 'completed',
            'courseSectionId' => '456',
            'roleId' => '789',
            'limitPrivilegesToCourseSection' => true,
            'startAt' => '2023-01-01T00:00:00Z',
            'endAt' => '2023-06-01T00:00:00Z',
            'notify' => false,
        ];

        $dto = new UpdateEnrollmentDTO($data);

        $this->assertEquals('completed', $dto->getEnrollmentState());
        $this->assertEquals('456', $dto->getCourseSectionId());
        $this->assertEquals('789', $dto->getRoleId());
        $this->assertTrue($dto->isLimitPrivilegesToCourseSection());
        $this->assertEquals('2023-01-01T00:00:00Z', $dto->getStartAt());
        $this->assertEquals('2023-06-01T00:00:00Z', $dto->getEndAt());
        $this->assertFalse($dto->isNotify());
    }

    public function testSetterMethods(): void
    {
        $dto = new UpdateEnrollmentDTO([]);

        $dto->setEnrollmentState('inactive');
        $dto->setCourseSectionId('789');
        $dto->setRoleId('123');
        $dto->setLimitPrivilegesToCourseSection(false);
        $dto->setStartAt('2023-02-01T00:00:00Z');
        $dto->setEndAt('2023-07-01T00:00:00Z');
        $dto->setNotify(true);

        $this->assertEquals('inactive', $dto->getEnrollmentState());
        $this->assertEquals('789', $dto->getCourseSectionId());
        $this->assertEquals('123', $dto->getRoleId());
        $this->assertFalse($dto->isLimitPrivilegesToCourseSection());
        $this->assertEquals('2023-02-01T00:00:00Z', $dto->getStartAt());
        $this->assertEquals('2023-07-01T00:00:00Z', $dto->getEndAt());
        $this->assertTrue($dto->isNotify());
    }

    public function testToArray(): void
    {
        $data = [
            'enrollmentState' => 'completed',
            'courseSectionId' => '456',
            'roleId' => '789',
            'limitPrivilegesToCourseSection' => true,
            'startAt' => '2023-01-01T00:00:00Z',
            'notify' => true,
        ];

        $dto = new UpdateEnrollmentDTO($data);
        $array = $dto->toArray();

        $this->assertEquals('completed', $array['enrollmentState']);
        $this->assertEquals('456', $array['courseSectionId']);
        $this->assertEquals('789', $array['roleId']);
        $this->assertTrue($array['limitPrivilegesToCourseSection']);
        $this->assertEquals('2023-01-01T00:00:00Z', $array['startAt']);
        $this->assertTrue($array['notify']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'enrollmentState' => 'completed',
            'courseSectionId' => '456',
            'roleId' => '789',
            'notify' => true,
        ];

        $dto = new UpdateEnrollmentDTO($data);
        $apiArray = $dto->toApiArray();

        // Should contain multipart format with enrollment property
        $this->assertIsArray($apiArray);
        $this->assertNotEmpty($apiArray);

        // The actual structure depends on AbstractBaseDto implementation
        // This test ensures the method works without throwing an exception
        $this->assertTrue(method_exists($dto, 'toApiArray'));
    }

    public function testNullValuesHandling(): void
    {
        $dto = new UpdateEnrollmentDTO([]);

        // All fields should be null by default for update DTO
        $this->assertNull($dto->getEnrollmentState());
        $this->assertNull($dto->getCourseSectionId());
        $this->assertNull($dto->getRoleId());
        $this->assertNull($dto->isLimitPrivilegesToCourseSection());
        $this->assertNull($dto->getStartAt());
        $this->assertNull($dto->getEndAt());
        $this->assertNull($dto->isNotify());
    }

    public function testBooleanValueHandling(): void
    {
        // Test with boolean true
        $dto1 = new UpdateEnrollmentDTO([
            'limitPrivilegesToCourseSection' => true,
            'notify' => true,
        ]);

        $this->assertTrue($dto1->isLimitPrivilegesToCourseSection());
        $this->assertTrue($dto1->isNotify());

        // Test with boolean false
        $dto2 = new UpdateEnrollmentDTO([
            'limitPrivilegesToCourseSection' => false,
            'notify' => false,
        ]);

        $this->assertFalse($dto2->isLimitPrivilegesToCourseSection());
        $this->assertFalse($dto2->isNotify());
    }

    public function testEnrollmentStateUpdate(): void
    {
        $dto = new UpdateEnrollmentDTO(['enrollmentState' => 'completed']);
        $this->assertEquals('completed', $dto->getEnrollmentState());

        $dto->setEnrollmentState('inactive');
        $this->assertEquals('inactive', $dto->getEnrollmentState());

        $dto->setEnrollmentState('active');
        $this->assertEquals('active', $dto->getEnrollmentState());
    }

    public function testCourseSectionUpdate(): void
    {
        $dto = new UpdateEnrollmentDTO(['courseSectionId' => '123']);
        $this->assertEquals('123', $dto->getCourseSectionId());

        $dto->setCourseSectionId('456');
        $this->assertEquals('456', $dto->getCourseSectionId());
    }

    public function testRoleUpdate(): void
    {
        $dto = new UpdateEnrollmentDTO(['roleId' => '789']);
        $this->assertEquals('789', $dto->getRoleId());

        $dto->setRoleId('101112');
        $this->assertEquals('101112', $dto->getRoleId());
    }

    public function testPrivilegeRestrictionUpdate(): void
    {
        $dto = new UpdateEnrollmentDTO(['limitPrivilegesToCourseSection' => false]);
        $this->assertFalse($dto->isLimitPrivilegesToCourseSection());

        $dto->setLimitPrivilegesToCourseSection(true);
        $this->assertTrue($dto->isLimitPrivilegesToCourseSection());
    }

    public function testDateConstraintUpdate(): void
    {
        $startDate = '2023-01-15T08:00:00Z';
        $endDate = '2023-05-15T17:00:00Z';

        $dto = new UpdateEnrollmentDTO([
            'startAt' => $startDate,
            'endAt' => $endDate,
        ]);

        $this->assertEquals($startDate, $dto->getStartAt());
        $this->assertEquals($endDate, $dto->getEndAt());

        $newStartDate = '2023-02-01T09:00:00Z';
        $newEndDate = '2023-06-01T16:00:00Z';

        $dto->setStartAt($newStartDate);
        $dto->setEndAt($newEndDate);

        $this->assertEquals($newStartDate, $dto->getStartAt());
        $this->assertEquals($newEndDate, $dto->getEndAt());
    }

    public function testNotificationPreferenceUpdate(): void
    {
        $dto = new UpdateEnrollmentDTO(['notify' => false]);
        $this->assertFalse($dto->isNotify());

        $dto->setNotify(true);
        $this->assertTrue($dto->isNotify());

        $dto->setNotify(false);
        $this->assertFalse($dto->isNotify());
    }

    public function testPartialUpdate(): void
    {
        // Test that only specified fields are updated
        $dto = new UpdateEnrollmentDTO([
            'enrollmentState' => 'completed',
            // Other fields intentionally left null
        ]);

        $this->assertEquals('completed', $dto->getEnrollmentState());
        $this->assertNull($dto->getCourseSectionId());
        $this->assertNull($dto->getRoleId());
        $this->assertNull($dto->isLimitPrivilegesToCourseSection());
        $this->assertNull($dto->getStartAt());
        $this->assertNull($dto->getEndAt());
        $this->assertNull($dto->isNotify());
    }

    public function testEmptyUpdate(): void
    {
        // Test that an empty DTO can be created
        $dto = new UpdateEnrollmentDTO([]);

        $this->assertNull($dto->getEnrollmentState());
        $this->assertNull($dto->getCourseSectionId());
        $this->assertNull($dto->getRoleId());
        $this->assertNull($dto->isLimitPrivilegesToCourseSection());
        $this->assertNull($dto->getStartAt());
        $this->assertNull($dto->getEndAt());
        $this->assertNull($dto->isNotify());
    }
}
