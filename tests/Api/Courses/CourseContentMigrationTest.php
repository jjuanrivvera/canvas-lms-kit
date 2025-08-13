<?php

declare(strict_types=1);

namespace Tests\Api\Courses;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Dto\ContentMigrations\CreateContentMigrationDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CourseContentMigrationTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        Course::setApiClient($this->mockClient);
        ContentMigration::setApiClient($this->mockClient);
    }

    public function testContentMigrations(): void
    {
        $course = new Course(['id' => 123]);
        
        $migrationsData = [
            ['id' => 1, 'workflow_state' => 'completed'],
            ['id' => 2, 'workflow_state' => 'running']
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('fetchAllPages')->willReturn($migrationsData);
        
        $this->mockClient->method('getPaginated')
            ->with('courses/123/content_migrations', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $migrations = $course->contentMigrations();

        $this->assertCount(2, $migrations);
        $this->assertInstanceOf(ContentMigration::class, $migrations[0]);
        $this->assertEquals(1, $migrations[0]->getId());
    }

    public function testContentMigrationsThrowsExceptionWithoutId(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to fetch content migrations');
        $course->contentMigrations();
    }

    public function testContentMigration(): void
    {
        $course = new Course(['id' => 123]);
        
        $migrationData = [
            'id' => 456,
            'workflow_state' => 'completed',
            'migration_type' => 'course_copy_importer'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migrationData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')
            ->with('courses/123/content_migrations/456')
            ->willReturn($this->mockResponse);

        $migration = $course->contentMigration(456);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(456, $migration->getId());
        $this->assertEquals('completed', $migration->getWorkflowState());
    }

    public function testCreateContentMigration(): void
    {
        $course = new Course(['id' => 123]);
        
        $migrationData = [
            'migration_type' => 'course_copy_importer',
            'settings' => ['source_course_id' => 456]
        ];

        $responseData = array_merge($migrationData, [
            'id' => 789,
            'workflow_state' => 'pre_processing'
        ]);

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('courses/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $course->createContentMigration($migrationData);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }

    public function testCopyContentFrom(): void
    {
        $course = new Course(['id' => 123]);
        
        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'course_copy_importer'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('courses/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $course->copyContentFrom(456);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }

    public function testImportCommonCartridge(): void
    {
        $course = new Course(['id' => 123]);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.imscc';
        file_put_contents($tempFile, 'test content');

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'common_cartridge_importer'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('courses/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $course->importCommonCartridge($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testSelectiveCopyFrom(): void
    {
        $course = new Course(['id' => 123]);
        
        $selections = [
            'assignments' => [1, 2, 3],
            'quizzes' => ['abc', 'def']
        ];

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'course_copy_importer'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('courses/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $course->selectiveCopyFrom(456, $selections);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }

    public function testCopyWithDateShift(): void
    {
        $course = new Course(['id' => 123]);
        
        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'course_copy_importer'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('courses/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $course->copyWithDateShift(456, '2024-01-01', '2024-09-01');

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }
}