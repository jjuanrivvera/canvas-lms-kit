<?php

declare(strict_types=1);

namespace Tests\Api\Groups;

use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GroupContentMigrationTest extends TestCase
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

        Group::setApiClient($this->mockClient);
        ContentMigration::setApiClient($this->mockClient);
    }

    public function testContentMigrations(): void
    {
        $group = new Group(['id' => 123]);

        $migrationsData = [
            ['id' => 1, 'workflow_state' => 'completed'],
            ['id' => 2, 'workflow_state' => 'running'],
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('all')->willReturn($migrationsData);

        $this->mockClient->method('getPaginated')
            ->with('groups/123/content_migrations', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $migrations = $group->contentMigrations();

        $this->assertCount(2, $migrations);
        $this->assertInstanceOf(ContentMigration::class, $migrations[0]);
        $this->assertEquals(1, $migrations[0]->getId());
    }

    public function testContentMigrationsThrowsExceptionWithoutId(): void
    {
        $group = new Group([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required to fetch content migrations');
        $group->contentMigrations();
    }

    public function testContentMigration(): void
    {
        $group = new Group(['id' => 123]);

        $migrationData = [
            'id' => 456,
            'workflow_state' => 'completed',
            'migration_type' => 'common_cartridge_importer',
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migrationData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')
            ->with('groups/123/content_migrations/456')
            ->willReturn($this->mockResponse);

        $migration = $group->contentMigration(456);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(456, $migration->getId());
        $this->assertEquals('completed', $migration->getWorkflowState());
    }

    public function testContentMigrationThrowsExceptionWithoutId(): void
    {
        $group = new Group([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required to fetch content migration');
        $group->contentMigration(456);
    }

    public function testCreateContentMigration(): void
    {
        $group = new Group(['id' => 123]);

        $migrationData = [
            'migration_type' => 'zip_file_importer',
            'settings' => ['folder_id' => 456],
        ];

        $responseData = array_merge($migrationData, [
            'id' => 789,
            'workflow_state' => 'pre_processing',
        ]);

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('groups/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $group->createContentMigration($migrationData);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }

    public function testCreateContentMigrationThrowsExceptionWithoutId(): void
    {
        $group = new Group([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required to create content migration');
        $group->createContentMigration(['migration_type' => 'zip_file_importer']);
    }

    public function testImportCommonCartridge(): void
    {
        $group = new Group(['id' => 123]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.imscc';
        file_put_contents($tempFile, 'test content');

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'common_cartridge_importer',
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('groups/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $group->importCommonCartridge($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportCommonCartridgeThrowsExceptionWithoutId(): void
    {
        $group = new Group([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required to import content');
        $group->importCommonCartridge('/path/to/file.imscc');
    }

    public function testImportCommonCartridgeWithFileUpload(): void
    {
        $group = new Group(['id' => 123]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.imscc';
        file_put_contents($tempFile, 'test content');

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'common_cartridge_importer',
            'migration_issues_url' => 'https://canvas.test/api/v1/groups/123/content_migrations/789/migration_issues',
            'pre_attachment' => [
                'upload_url' => 'https://canvas.test/upload',
                'upload_params' => ['key' => 'value'],
            ],
        ];

        // Mock the initial create response
        $createResponse = $this->createMock(ResponseInterface::class);
        $createStream = $this->createMock(StreamInterface::class);
        $createStream->method('getContents')->willReturn(json_encode($responseData));
        $createResponse->method('getBody')->willReturn($createStream);

        // Mock the file upload response
        $uploadResponse = $this->createMock(ResponseInterface::class);
        $uploadResponse->method('getStatusCode')->willReturn(200);

        // Mock the refresh response
        $refreshedData = $responseData;
        $refreshedData['workflow_state'] = 'running';
        unset($refreshedData['pre_attachment']);

        $refreshResponse = $this->createMock(ResponseInterface::class);
        $refreshStream = $this->createMock(StreamInterface::class);
        $refreshStream->method('getContents')->willReturn(json_encode($refreshedData));
        $refreshResponse->method('getBody')->willReturn($refreshStream);

        $this->mockClient->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($createResponse, $uploadResponse);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('groups/123/content_migrations/789')
            ->willReturn($refreshResponse);

        $migration = $group->importCommonCartridge($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportZipFile(): void
    {
        $group = new Group(['id' => 123]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.zip';
        file_put_contents($tempFile, 'test content');

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'zip_file_importer',
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('post')
            ->with('groups/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $group->importZipFile($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportZipFileThrowsExceptionWithoutId(): void
    {
        $group = new Group([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required to import content');
        $group->importZipFile('/path/to/file.zip');
    }
}
