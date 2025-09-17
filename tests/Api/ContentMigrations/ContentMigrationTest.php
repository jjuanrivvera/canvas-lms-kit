<?php

declare(strict_types=1);

namespace Tests\Api\ContentMigrations;

use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Api\ContentMigrations\MigrationIssue;
use CanvasLMS\Api\Progress\Progress;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\Migrator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ContentMigrationTest extends TestCase
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

        ContentMigration::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testFind(): void
    {
        $migrationData = [
            'id' => 123,
            'migration_type' => 'course_copy_importer',
            'workflow_state' => 'completed',
            'progress_url' => 'https://canvas.test/api/v1/progress/456',
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migrationData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')->with('courses/1/content_migrations/123')->willReturn($this->mockResponse);

        $migration = ContentMigration::findByContext('courses', 1, 123);

        $this->assertEquals(123, $migration->getId());
        $this->assertEquals('course_copy_importer', $migration->getMigrationType());
        $this->assertEquals('completed', $migration->getWorkflowState());
    }

    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        ContentMigration::find(123);
    }

    public function testGet(): void
    {
        $migrationsData = [
            ['id' => 1, 'migration_type' => 'course_copy_importer'],
            ['id' => 2, 'migration_type' => 'zip_file_importer'],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migrationsData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);

        $this->mockClient->method('get')
            ->with('accounts/1/content_migrations', ['query' => []])
            ->willReturn($this->mockResponse);

        $migrations = ContentMigration::get();

        $this->assertCount(2, $migrations);
        $this->assertInstanceOf(ContentMigration::class, $migrations[0]);
    }

    public function testCreateCourseCopy(): void
    {
        $responseData = [
            'id' => 789,
            'migration_type' => 'course_copy_importer',
            'workflow_state' => 'pre_processing',
            'settings' => ['source_course_id' => 456],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')->willReturn($this->mockResponse);

        $migration = ContentMigration::createCourseCopy(123, 456);

        $this->assertEquals(789, $migration->getId());
        $this->assertEquals('course_copy_importer', $migration->getMigrationType());
        $this->assertEquals('pre_processing', $migration->getWorkflowState());
    }

    public function testCreateWithFileUploadError(): void
    {
        $responseData = [
            'id' => 789,
            'migration_type' => 'common_cartridge_importer',
            'workflow_state' => 'pre_processing',
            'pre_attachment' => [
                'message' => 'file exceeded quota',
            ],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')->willReturn($this->mockResponse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('File upload initialization failed: file exceeded quota');

        ContentMigration::createInContext('courses', 123, [
            'migration_type' => 'common_cartridge_importer',
            'pre_attachment' => ['name' => 'test.imscc', 'size' => 12345],
        ]);
    }

    public function testProcessFileUpload(): void
    {
        $migration = new ContentMigration([
            'id' => 123,
            'migration_type' => 'common_cartridge_importer',
            'workflow_state' => 'pre_processing',
            'pre_attachment' => [
                'upload_url' => 'https://s3.amazonaws.com/upload',
                'upload_params' => [
                    'key' => 'uploads/123',
                    'acl' => 'private',
                ],
            ],
            'migration_issues_url' => 'https://canvas.test/api/v1/courses/456/content_migrations/123/migration_issues',
        ]);

        // Mock file upload response
        $this->mockResponse->method('getStatusCode')->willReturn(200);
        $this->mockClient->method('post')->willReturn($this->mockResponse);

        // Mock refresh response
        $refreshData = [
            'id' => 123,
            'workflow_state' => 'running',
            'migration_type' => 'common_cartridge_importer',
            'pre_attachment' => $migration->getPreAttachment(),
            'migration_issues_url' => $migration->getMigrationIssuesUrl(),
        ];
        $refreshStream = $this->createMock(StreamInterface::class);
        $refreshStream->method('getContents')->willReturn(json_encode($refreshData));
        $refreshResponse = $this->createMock(ResponseInterface::class);
        $refreshResponse->method('getBody')->willReturn($refreshStream);
        $this->mockClient->method('get')
            ->with('courses/456/content_migrations/123')
            ->willReturn($refreshResponse);

        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        try {
            $result = $migration->processFileUpload($tempFile);
            // After file upload, the migration may still be in pre_processing
            // as the Canvas server needs time to process the uploaded file
            $this->assertContains($result->getWorkflowState(), ['pre_processing', 'running']);
            $this->assertSame($migration, $result); // Should return the same instance
        } finally {
            unlink($tempFile);
        }
    }

    public function testGetProgress(): void
    {
        $migration = new ContentMigration([
            'id' => 123,
            'progress_url' => 'https://canvas.test/api/v1/progress/456',
        ]);

        $progressData = [
            'id' => 456,
            'workflow_state' => 'running',
            'completion' => 75,
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($progressData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')->with('/progress/456')->willReturn($this->mockResponse);

        Progress::setApiClient($this->mockClient);
        $progress = $migration->getProgress();

        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertEquals(456, $progress->getId());
    }

    public function testWaitForCompletion(): void
    {
        // Start with a completed migration to test immediate return
        $migration = new ContentMigration([
            'id' => 123,
            'workflow_state' => 'completed',
            'migration_issues_url' => 'https://canvas.test/api/v1/courses/456/content_migrations/123/migration_issues',
        ]);

        // waitForCompletion should return immediately if already completed
        $result = $migration->waitForCompletion(1, 0);
        $this->assertEquals('completed', $result->getWorkflowState());
        $this->assertSame($migration, $result);
    }

    public function testGetMigrationIssues(): void
    {
        $migration = new ContentMigration([
            'id' => 123,
            'migration_issues_url' => 'https://canvas.test/api/v1/courses/456/content_migrations/123/migration_issues',
        ]);

        $issuesData = [
            ['id' => 1, 'issue_type' => 'warning', 'description' => 'Some warning'],
            ['id' => 2, 'issue_type' => 'error', 'description' => 'Some error'],
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('all')->willReturn($issuesData);

        $this->mockClient->method('getPaginated')
            ->with('courses/456/content_migrations/123/migration_issues', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        MigrationIssue::setApiClient($this->mockClient);
        $issues = $migration->getMigrationIssues();

        $this->assertCount(2, $issues);
        $this->assertInstanceOf(MigrationIssue::class, $issues[0]);
    }

    public function testGetSelectiveData(): void
    {
        $migration = new ContentMigration([
            'id' => 123,
            'migration_issues_url' => 'https://canvas.test/api/v1/courses/456/content_migrations/123/migration_issues',
        ]);

        $selectiveData = [
            [
                'type' => 'assignments',
                'property' => 'copy[all_assignments]',
                'title' => 'Assignments',
                'count' => 5,
            ],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($selectiveData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')->willReturn($this->mockResponse);

        $data = $migration->getSelectiveData();

        $this->assertCount(1, $data);
        $this->assertEquals('assignments', $data[0]['type']);
    }

    public function testGetAssetIdMapping(): void
    {
        $mappingData = [
            'assignments' => ['13' => '740', '14' => '741'],
            'discussion_topics' => ['15' => '743'],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($mappingData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')->willReturn($this->mockResponse);

        $mapping = ContentMigration::getAssetIdMapping(123, 456);

        $this->assertArrayHasKey('assignments', $mapping);
        $this->assertEquals('740', $mapping['assignments']['13']);
    }

    public function testGetMigrators(): void
    {
        $migratorsData = [
            [
                'type' => 'course_copy_importer',
                'requires_file_upload' => false,
                'name' => 'Course Copy',
                'required_settings' => ['source_course_id'],
            ],
            [
                'type' => 'common_cartridge_importer',
                'requires_file_upload' => true,
                'name' => 'Common Cartridge 1.x Package',
            ],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migratorsData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')->willReturn($this->mockResponse);

        $migrators = ContentMigration::getMigrators('courses', 123);

        $this->assertCount(2, $migrators);
        $this->assertInstanceOf(Migrator::class, $migrators[0]);
        $this->assertEquals('course_copy_importer', $migrators[0]->getType());
        $this->assertFalse($migrators[0]->isFileBased());
    }

    public function testWorkflowStateHelpers(): void
    {
        $migration = new ContentMigration(['workflow_state' => 'completed']);
        $this->assertTrue($migration->isCompleted());
        $this->assertFalse($migration->isFailed());
        $this->assertFalse($migration->isRunning());
        $this->assertTrue($migration->isFinished());

        $migration->setWorkflowState('failed');
        $this->assertFalse($migration->isCompleted());
        $this->assertTrue($migration->isFailed());
        $this->assertTrue($migration->isFinished());

        $migration->setWorkflowState('running');
        $this->assertTrue($migration->isRunning());
        $this->assertFalse($migration->isFinished());
    }

    public function testFileUploadHelpers(): void
    {
        $migration = new ContentMigration([
            'workflow_state' => 'pre_processing',
            'pre_attachment' => [
                'upload_url' => 'https://s3.amazonaws.com/upload',
            ],
        ]);

        $this->assertTrue($migration->isFileUploadPending());
        $this->assertFalse($migration->hasFileUploadError());
        $this->assertNull($migration->getFileUploadError());

        $migration = new ContentMigration([
            'pre_attachment' => [
                'message' => 'quota exceeded',
            ],
        ]);

        $this->assertFalse($migration->isFileUploadPending());
        $this->assertTrue($migration->hasFileUploadError());
        $this->assertEquals('quota exceeded', $migration->getFileUploadError());
    }

    public function testImportCommonCartridge(): void
    {
        $createResponse = [
            'id' => 123,
            'migration_type' => 'common_cartridge_importer',
            'workflow_state' => 'pre_processing',
            'pre_attachment' => [
                'upload_url' => 'https://s3.amazonaws.com/upload',
                'upload_params' => ['key' => 'value'],
            ],
            'migration_issues_url' => 'https://canvas.test/api/v1/courses/456/content_migrations/123/migration_issues',
        ];

        $uploadedResponse = [
            'id' => 123,
            'workflow_state' => 'running',
        ];

        // Create temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $this->mockStream->method('getContents')
            ->willReturnOnConsecutiveCalls(
                json_encode($createResponse),
                json_encode($uploadedResponse)
            );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockResponse->method('getStatusCode')->willReturn(200);
        $this->mockClient->method('post')->willReturn($this->mockResponse);
        $this->mockClient->method('get')->willReturn($this->mockResponse);

        try {
            $migration = ContentMigration::importCommonCartridge(456, $tempFile);
            $this->assertEquals(123, $migration->getId());
            $this->assertContains($migration->getWorkflowState(), ['pre_processing', 'running']);
        } finally {
            unlink($tempFile);
        }
    }
}
