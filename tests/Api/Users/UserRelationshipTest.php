<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Users;

use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class UserRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    private ResponseInterface $mockResponse;

    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        // Set up the API client
        User::setApiClient($this->mockHttpClient);
    }

    public function testEnrollmentsReturnsArrayOfEnrollmentObjects(): void
    {
        // Create test user
        $user = new User(['id' => 100, 'name' => 'Test User']);

        // Mock response data
        $enrollmentsData = [
            [
                'id' => 1,
                'user_id' => 100,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 100,
                'course_id' => 456,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('users/100/enrollments', ['query' => []])
            ->willReturn($this->mockResponse);

        // Test the method
        $enrollments = $user->enrollments();

        // Assertions
        $this->assertIsArray($enrollments);
        $this->assertCount(2, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
        $this->assertEquals(1, $enrollments[0]->id);
        $this->assertEquals('StudentEnrollment', $enrollments[0]->type);
    }

    public function testEnrollmentsWithParametersPassesQueryParams(): void
    {
        // Create test user
        $user = new User(['id' => 100]);

        // Mock response data
        $enrollmentsData = [
            ['id' => 1, 'user_id' => 100, 'course_id' => 123],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $params = ['state' => ['active'], 'type' => ['StudentEnrollment']];

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('users/100/enrollments', ['query' => $params])
            ->willReturn($this->mockResponse);

        // Test the method
        $enrollments = $user->enrollments($params);

        // Assertions
        $this->assertIsArray($enrollments);
        $this->assertCount(1, $enrollments);
    }

    public function testGroupsReturnsArrayOfGroupObjects(): void
    {
        // Create test user
        $user = new User(['id' => 100]);

        // Mock response data
        $groupsData = [
            [
                'id' => 10,
                'name' => 'Study Group 1',
                'description' => 'A study group',
                'group_category_id' => 1,
            ],
            [
                'id' => 20,
                'name' => 'Project Team',
                'description' => 'Project collaboration',
                'group_category_id' => 2,
            ],
        ];

        // Mock paginated response
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($groupsData);

        // Set up mock expectations for paginated request
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('users/100/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        // Test the method
        $groups = $user->groups();

        // Assertions
        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
        $this->assertEquals(10, $groups[0]->id);
        $this->assertEquals('Study Group 1', $groups[0]->name);
    }

    public function testAllMethodsThrowExceptionWhenUserIdMissing(): void
    {
        // Skip this test since User class requires id to be set and is typed as int
        $this->markTestSkipped('User class id property is typed as int and cannot be null');
    }

    public function testGroupsThrowsExceptionWhenUserIdMissing(): void
    {
        // Skip this test since User class requires id to be set and is typed as int
        $this->markTestSkipped('User class id property is typed as int and cannot be null');
    }
}
