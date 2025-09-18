<?php

declare(strict_types=1);

namespace Tests\Api\Users;

use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class UserContentMigrationTest extends TestCase
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

        User::setApiClient($this->mockClient);
        ContentMigration::setApiClient($this->mockClient);
    }

    public function testContentMigrations(): void
    {
        $user = new User(['id' => 123]);

        $migrationsData = [
            ['id' => 1, 'workflow_state' => 'completed'],
            ['id' => 2, 'workflow_state' => 'running'],
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('all')->willReturn($migrationsData);

        $this->mockClient->method('getPaginated')
            ->with('users/123/content_migrations', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $migrations = $user->contentMigrations();

        $this->assertCount(2, $migrations);
        $this->assertInstanceOf(ContentMigration::class, $migrations[0]);
        $this->assertEquals(1, $migrations[0]->getId());
    }

    public function testContentMigrationsThrowsExceptionWithoutId(): void
    {
        $user = new User([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to fetch content migrations');
        $user->contentMigrations();
    }

    public function testContentMigration(): void
    {
        $user = new User(['id' => 123]);

        $migrationData = [
            'id' => 456,
            'workflow_state' => 'completed',
            'migration_type' => 'common_cartridge_importer',
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($migrationData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')
            ->with('users/123/content_migrations/456')
            ->willReturn($this->mockResponse);

        $migration = $user->contentMigration(456);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(456, $migration->getId());
        $this->assertEquals('completed', $migration->getWorkflowState());
    }

    public function testContentMigrationThrowsExceptionWithoutId(): void
    {
        $user = new User([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to fetch content migration');
        $user->contentMigration(456);
    }

    public function testCreateContentMigration(): void
    {
        $user = new User(['id' => 123]);

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
            ->with('users/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $user->createContentMigration($migrationData);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());
    }

    public function testCreateContentMigrationThrowsExceptionWithoutId(): void
    {
        $user = new User([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to create content migration');
        $user->createContentMigration(['migration_type' => 'zip_file_importer']);
    }

    public function testImportCommonCartridge(): void
    {
        $user = new User(['id' => 123]);

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
            ->with('users/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $user->importCommonCartridge($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportCommonCartridgeThrowsExceptionWithoutId(): void
    {
        $user = new User([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to import content');
        $user->importCommonCartridge('/path/to/file.imscc');
    }

    public function testImportCommonCartridgeWithFileUpload(): void
    {
        $user = new User(['id' => 123]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.imscc';
        file_put_contents($tempFile, 'test content');

        $responseData = [
            'id' => 789,
            'workflow_state' => 'pre_processing',
            'migration_type' => 'common_cartridge_importer',
            'migration_issues_url' => 'https://canvas.test/api/v1/users/123/content_migrations/789/migration_issues',
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
            ->with('users/123/content_migrations/789')
            ->willReturn($refreshResponse);

        $migration = $user->importCommonCartridge($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportZipFile(): void
    {
        $user = new User(['id' => 123]);

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
            ->with('users/123/content_migrations')
            ->willReturn($this->mockResponse);

        $migration = $user->importZipFile($tempFile);

        $this->assertInstanceOf(ContentMigration::class, $migration);
        $this->assertEquals(789, $migration->getId());

        unlink($tempFile);
    }

    public function testImportZipFileThrowsExceptionWithoutId(): void
    {
        $user = new User([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to import content');
        $user->importZipFile('/path/to/file.zip');
    }

    public function testContentMigrationsWithSelfPattern(): void
    {
        // Content migration methods require a User ID and don't support the 'self' pattern
        // User::self() returns an empty User instance without an ID
        $currentUser = User::self();

        // Attempting to call content migrations should throw an exception
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to fetch content migrations');

        $currentUser->contentMigrations();
    }
}
