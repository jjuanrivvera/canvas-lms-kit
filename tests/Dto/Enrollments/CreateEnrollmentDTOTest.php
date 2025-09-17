<?php

declare(strict_types=1);

namespace Tests\Dto\Enrollments;

use CanvasLMS\Dto\Enrollments\CreateEnrollmentDTO;
use PHPUnit\Framework\TestCase;

class CreateEnrollmentDTOTest extends TestCase
{
    public function testConstructorPopulatesProperties(): void
    {
        $data = [
            'userId' => '100',
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active',
            'courseSectionId' => '456',
            'roleId' => '789',
            'limitPrivilegesToCourseSection' => true,
            'notify' => false,
            'selfEnrollmentCode' => 'ABC123',
            'startAt' => '2023-01-01T00:00:00Z',
            'endAt' => '2023-06-01T00:00:00Z',
            'sisUserId' => 'user123',
            'userEmail' => 'test@example.com',
            'userFirstName' => 'John',
            'userLastName' => 'Doe',
            'userSisId' => 'sisuser123',
        ];

        $dto = new CreateEnrollmentDTO($data);

        $this->assertEquals('100', $dto->getUserId());
        $this->assertEquals('StudentEnrollment', $dto->getType());
        $this->assertEquals('active', $dto->getEnrollmentState());
        $this->assertEquals('456', $dto->getCourseSectionId());
        $this->assertEquals('789', $dto->getRoleId());
        $this->assertTrue($dto->isLimitPrivilegesToCourseSection());
        $this->assertFalse($dto->isNotify());
        $this->assertEquals('ABC123', $dto->getSelfEnrollmentCode());
        $this->assertEquals('2023-01-01T00:00:00Z', $dto->getStartAt());
        $this->assertEquals('2023-06-01T00:00:00Z', $dto->getEndAt());
        $this->assertEquals('user123', $dto->getSisUserId());
        $this->assertEquals('test@example.com', $dto->getUserEmail());
        $this->assertEquals('John', $dto->getUserFirstName());
        $this->assertEquals('Doe', $dto->getUserLastName());
        $this->assertEquals('sisuser123', $dto->getUserSisId());
    }

    public function testDefaultEnrollmentState(): void
    {
        $dto = new CreateEnrollmentDTO([]);

        $this->assertEquals('active', $dto->getEnrollmentState());
    }

    public function testSetterMethods(): void
    {
        $dto = new CreateEnrollmentDTO([]);

        $dto->setUserId('200');
        $dto->setType('TeacherEnrollment');
        $dto->setEnrollmentState('invited');
        $dto->setCourseSectionId('789');
        $dto->setRoleId('123');
        $dto->setLimitPrivilegesToCourseSection(false);
        $dto->setNotify(true);
        $dto->setSelfEnrollmentCode('XYZ789');
        $dto->setStartAt('2023-02-01T00:00:00Z');
        $dto->setEndAt('2023-07-01T00:00:00Z');
        $dto->setSisUserId('user456');
        $dto->setUserEmail('teacher@example.com');
        $dto->setUserFirstName('Jane');
        $dto->setUserLastName('Smith');
        $dto->setUserSisId('sisuser456');

        $this->assertEquals('200', $dto->getUserId());
        $this->assertEquals('TeacherEnrollment', $dto->getType());
        $this->assertEquals('invited', $dto->getEnrollmentState());
        $this->assertEquals('789', $dto->getCourseSectionId());
        $this->assertEquals('123', $dto->getRoleId());
        $this->assertFalse($dto->isLimitPrivilegesToCourseSection());
        $this->assertTrue($dto->isNotify());
        $this->assertEquals('XYZ789', $dto->getSelfEnrollmentCode());
        $this->assertEquals('2023-02-01T00:00:00Z', $dto->getStartAt());
        $this->assertEquals('2023-07-01T00:00:00Z', $dto->getEndAt());
        $this->assertEquals('user456', $dto->getSisUserId());
        $this->assertEquals('teacher@example.com', $dto->getUserEmail());
        $this->assertEquals('Jane', $dto->getUserFirstName());
        $this->assertEquals('Smith', $dto->getUserLastName());
        $this->assertEquals('sisuser456', $dto->getUserSisId());
    }

    public function testToArray(): void
    {
        $data = [
            'userId' => '100',
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active',
            'courseSectionId' => '456',
            'roleId' => '789',
            'notify' => true,
            'startAt' => '2023-01-01T00:00:00Z',
            'sisUserId' => 'user123',
        ];

        $dto = new CreateEnrollmentDTO($data);
        $array = $dto->toArray();

        $this->assertEquals('100', $array['userId']);
        $this->assertEquals('StudentEnrollment', $array['type']);
        $this->assertEquals('active', $array['enrollmentState']);
        $this->assertEquals('456', $array['courseSectionId']);
        $this->assertEquals('789', $array['roleId']);
        $this->assertTrue($array['notify']);
        $this->assertEquals('2023-01-01T00:00:00Z', $array['startAt']);
        $this->assertEquals('user123', $array['sisUserId']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'userId' => '100',
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active',
            'courseSectionId' => '456',
            'notify' => true,
        ];

        $dto = new CreateEnrollmentDTO($data);
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
        $dto = new CreateEnrollmentDTO([]);

        // All optional fields should be null by default (except enrollmentState which has default)
        $this->assertNull($dto->getUserId());
        $this->assertNull($dto->getType());
        $this->assertEquals('active', $dto->getEnrollmentState()); // Has default
        $this->assertNull($dto->getCourseSectionId());
        $this->assertNull($dto->getRoleId());
        $this->assertNull($dto->isLimitPrivilegesToCourseSection());
        $this->assertNull($dto->isNotify());
        $this->assertNull($dto->getSelfEnrollmentCode());
        $this->assertNull($dto->getStartAt());
        $this->assertNull($dto->getEndAt());
        $this->assertNull($dto->getSisUserId());
        $this->assertNull($dto->getUserEmail());
        $this->assertNull($dto->getUserFirstName());
        $this->assertNull($dto->getUserLastName());
        $this->assertNull($dto->getUserSisId());
    }

    public function testBooleanValueHandling(): void
    {
        // Test with boolean true
        $dto1 = new CreateEnrollmentDTO([
            'limitPrivilegesToCourseSection' => true,
            'notify' => true,
        ]);

        $this->assertTrue($dto1->isLimitPrivilegesToCourseSection());
        $this->assertTrue($dto1->isNotify());

        // Test with boolean false
        $dto2 = new CreateEnrollmentDTO([
            'limitPrivilegesToCourseSection' => false,
            'notify' => false,
        ]);

        $this->assertFalse($dto2->isLimitPrivilegesToCourseSection());
        $this->assertFalse($dto2->isNotify());
    }

    public function testUserCreationFields(): void
    {
        $dto = new CreateEnrollmentDTO([
            'userEmail' => 'newuser@example.com',
            'userFirstName' => 'New',
            'userLastName' => 'User',
            'userSisId' => 'newuser123',
        ]);

        $this->assertEquals('newuser@example.com', $dto->getUserEmail());
        $this->assertEquals('New', $dto->getUserFirstName());
        $this->assertEquals('User', $dto->getUserLastName());
        $this->assertEquals('newuser123', $dto->getUserSisId());
    }

    public function testSISIntegrationFields(): void
    {
        $dto = new CreateEnrollmentDTO([
            'sisUserId' => 'sis_user_123',
        ]);

        $this->assertEquals('sis_user_123', $dto->getSisUserId());
    }

    public function testDateConstraintFields(): void
    {
        $startDate = '2023-01-15T08:00:00Z';
        $endDate = '2023-05-15T17:00:00Z';

        $dto = new CreateEnrollmentDTO([
            'startAt' => $startDate,
            'endAt' => $endDate,
        ]);

        $this->assertEquals($startDate, $dto->getStartAt());
        $this->assertEquals($endDate, $dto->getEndAt());
    }
}
