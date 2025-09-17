<?php

declare(strict_types=1);

namespace Tests\Api\Files;

use CanvasLMS\Api\Files\File;
use CanvasLMS\Dto\Files\UploadFileDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    private $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        File::setApiClient($this->httpClientMock);
    }

    /**
     * Test file upload to course
     */
    public function testUploadToCourse(): void
    {
        $courseId = 123;

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $fileData = [
            'name' => 'test-file.pdf',
            'size' => 1024,
            'content_type' => 'application/pdf',
            'parent_folder_id' => 456,
            'file' => $tempFile,
        ];

        $uploadResponse = [
            'upload_url' => 'https://upload.example.com',
            'upload_params' => [
                'key' => '/files/test-file.pdf',
                'acl' => 'private',
                'Content-Type' => 'application/pdf',
            ],
        ];

        $fileResponse = [
            'id' => 789,
            'display_name' => 'test-file.pdf',
            'filename' => 'test-file.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
            'folder_id' => 456,
            'uuid' => 'test-uuid-123',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        // First post is for Canvas API (step 1)
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($uploadResponse)));

        // Second call is rawRequest for external upload URL (step 2)
        $callCount = 0;
        $this->httpClientMock->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnCallback(function ($url, $method, $options) use (&$callCount, $fileResponse) {
                $callCount++;

                if ($callCount === 1) {
                    // First call: POST to upload URL
                    $this->assertEquals('https://upload.example.com', $url);
                    $this->assertEquals('POST', $method);
                    $this->assertArrayHasKey('multipart', $options);
                    $this->assertArrayHasKey('skipAuth', $options);
                    $this->assertTrue($options['skipAuth']);
                    $this->assertArrayHasKey('skipDomainValidation', $options);
                    $this->assertTrue($options['skipDomainValidation']);

                    return new Response(200, ['Location' => 'https://confirm.example.com'], '');
                } else {
                    // Second call: GET to confirm URL
                    $this->assertEquals('https://confirm.example.com', $url);
                    $this->assertEquals('GET', $method);
                    $this->assertArrayHasKey('skipAuth', $options);
                    $this->assertTrue($options['skipAuth']);
                    $this->assertArrayHasKey('skipDomainValidation', $options);
                    $this->assertTrue($options['skipDomainValidation']);

                    return new Response(200, [], json_encode($fileResponse));
                }
            });

        $file = File::uploadToCourse($courseId, $fileData);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(789, $file->getId());
        $this->assertEquals('test-file.pdf', $file->getDisplayName());
        $this->assertEquals('application/pdf', $file->getContentType());
        $this->assertEquals(1024, $file->getSize());

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test file upload to user
     */
    public function testUploadToUser(): void
    {
        $userId = 123;

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'user content');

        $fileDto = new UploadFileDTO([
            'name' => 'user-file.txt',
            'size' => 512,
            'file' => $tempFile,
        ]);

        $uploadResponse = [
            'upload_url' => 'https://upload.example.com',
            'upload_params' => [
                'key' => '/users/123/files/user-file.txt',
            ],
        ];

        $fileResponse = [
            'id' => 101,
            'display_name' => 'user-file.txt',
            'filename' => 'user-file.txt',
            'size' => 512,
            'folder_id' => 789,
            'uuid' => 'user-uuid-456',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        // First post is for Canvas API (step 1)
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($uploadResponse)));

        // Second call is rawRequest for external upload URL (step 2) - no Location redirect
        $this->httpClientMock->expects($this->once())
            ->method('rawRequest')
            ->with(
                'https://upload.example.com',
                'POST',
                $this->callback(function ($options) {
                    return isset($options['multipart']) &&
                           isset($options['skipAuth']) &&
                           $options['skipAuth'] === true &&
                           isset($options['skipDomainValidation']) &&
                           $options['skipDomainValidation'] === true;
                })
            )
            ->willReturn(new Response(200, [], json_encode($fileResponse)));

        $file = File::uploadToUser($userId, $fileDto);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(101, $file->getId());
        $this->assertEquals('user-file.txt', $file->getDisplayName());

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test file upload to group
     */
    public function testUploadToGroup(): void
    {
        $groupId = 456;

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'group content');

        $fileData = [
            'name' => 'group-file.docx',
            'size' => 2048,
            'file' => $tempFile,
        ];

        $uploadResponse = [
            'upload_url' => 'https://upload.example.com',
            'upload_params' => [
                'key' => '/groups/456/files/group-file.docx',
            ],
        ];

        $fileResponse = [
            'id' => 202,
            'display_name' => 'group-file.docx',
            'filename' => 'group-file.docx',
            'size' => 2048,
            'folder_id' => 999,
            'uuid' => 'group-uuid-789',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        // First call: POST to Canvas API to get upload URL
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                '/groups/456/files',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn(new Response(200, [], json_encode($uploadResponse)));

        // Second call: rawRequest for external upload and confirmation
        $callCount = 0;
        $this->httpClientMock->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnCallback(function ($url, $method, $options) use (&$callCount, $fileResponse) {
                $callCount++;

                if ($callCount === 1) {
                    // First call: POST to upload URL
                    $this->assertEquals('https://upload.example.com', $url);
                    $this->assertEquals('POST', $method);

                    return new Response(200, ['Location' => 'https://confirm.example.com'], '');
                } else {
                    // Second call: GET to confirm URL
                    $this->assertEquals('https://confirm.example.com', $url);
                    $this->assertEquals('GET', $method);

                    return new Response(200, [], json_encode($fileResponse));
                }
            });

        $file = File::uploadToGroup($groupId, $fileData);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(202, $file->getId());
        $this->assertEquals('group-file.docx', $file->getDisplayName());

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test file upload to assignment submission
     */
    public function testUploadToAssignmentSubmission(): void
    {
        $courseId = 123;
        $assignmentId = 456;
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'submission content');

        $fileData = [
            'name' => 'submission.pdf',
            'size' => 4096,
            'file' => $tempFile,
        ];

        $uploadResponse = [
            'upload_url' => 'https://upload.example.com',
            'upload_params' => [
                'key' => '/courses/123/assignments/456/submissions/self/files/submission.pdf',
            ],
        ];

        $fileResponse = [
            'id' => 303,
            'display_name' => 'submission.pdf',
            'filename' => 'submission.pdf',
            'size' => 4096,
            'folder_id' => 111,
            'uuid' => 'submission-uuid-101',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        // First call: POST to Canvas API to get upload URL
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                '/courses/123/assignments/456/submissions/self/files',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn(new Response(200, [], json_encode($uploadResponse)));

        // Second call: rawRequest for external upload and confirmation
        $callCount = 0;
        $this->httpClientMock->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnCallback(function ($url, $method, $options) use (&$callCount, $fileResponse) {
                $callCount++;

                if ($callCount === 1) {
                    // First call: POST to upload URL
                    $this->assertEquals('https://upload.example.com', $url);
                    $this->assertEquals('POST', $method);

                    return new Response(200, ['Location' => 'https://confirm.example.com'], '');
                } else {
                    // Second call: GET to confirm URL
                    $this->assertEquals('https://confirm.example.com', $url);
                    $this->assertEquals('GET', $method);

                    return new Response(200, [], json_encode($fileResponse));
                }
            });

        $file = File::uploadToAssignmentSubmission($courseId, $assignmentId, $fileData);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(303, $file->getId());
        $this->assertEquals('submission.pdf', $file->getDisplayName());

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test finding a file by ID
     */
    public function testFind(): void
    {
        $fileId = 789;
        $fileResponse = [
            'id' => 789,
            'display_name' => 'found-file.txt',
            'filename' => 'found-file.txt',
            'content_type' => 'text/plain',
            'size' => 1024,
            'folder_id' => 456,
            'uuid' => 'found-uuid-123',
            'url' => 'https://example.com/download/file.txt',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with("/files/{$fileId}")
            ->willReturn(new Response(200, [], json_encode($fileResponse)));

        $file = File::find($fileId);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(789, $file->getId());
        $this->assertEquals('found-file.txt', $file->getDisplayName());
        $this->assertEquals('https://example.com/download/file.txt', $file->getUrl());
    }

    /**
     * Test getting download URL
     */
    public function testGetDownloadUrl(): void
    {
        $fileData = [
            'id' => 789,
            'display_name' => 'download-file.pdf',
            'filename' => 'download-file.pdf',
            'size' => 2048,
            'folder_id' => 456,
            'uuid' => 'download-uuid-456',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        $file = new File($fileData);

        $fileResponseWithUrl = array_merge($fileData, [
            'url' => 'https://example.com/download/download-file.pdf',
        ]);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('/files/789')
            ->willReturn(new Response(200, [], json_encode($fileResponseWithUrl)));

        $downloadUrl = $file->getDownloadUrl();

        $this->assertEquals('https://example.com/download/download-file.pdf', $downloadUrl);
    }

    /**
     * Test file deletion
     */
    public function testDelete(): void
    {
        $fileData = [
            'id' => 789,
            'display_name' => 'delete-file.txt',
            'filename' => 'delete-file.txt',
            'size' => 1024,
            'folder_id' => 456,
            'uuid' => 'delete-uuid-789',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        $file = new File($fileData);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('/files/789')
            ->willReturn(new Response(200, [], ''));

        $result = $file->delete();

        $this->assertInstanceOf(File::class, $result);
    }

    /**
     * Test file deletion failure
     */
    public function testDeleteFailure(): void
    {
        $fileData = [
            'id' => 789,
            'display_name' => 'delete-fail.txt',
            'filename' => 'delete-fail.txt',
            'size' => 1024,
            'folder_id' => 456,
            'uuid' => 'delete-fail-uuid',
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => false,
            'hidden' => false,
        ];

        $file = new File($fileData);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('/files/789')
            ->willThrowException(new CanvasApiException('Delete failed'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Delete failed');
        $file->delete();
    }

    /**
     * Test upload with invalid response
     */
    public function testUploadWithInvalidResponse(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid upload response from Canvas API');

        $courseId = 123;

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'content');

        $fileData = [
            'name' => 'invalid-response.txt',
            'file' => $tempFile,
        ];

        $invalidResponse = [
            'error' => 'Invalid request',
        ];

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode($invalidResponse)));

        try {
            File::uploadToCourse($courseId, $fileData);
        } finally {
            // Clean up
            unlink($tempFile);
        }
    }

    /**
     * Test fetchAll method (fetches current user's files)
     */
    public function testGet(): void
    {
        $filesResponse = [
            [
                'id' => 123,
                'display_name' => 'user-file-1.txt',
                'filename' => 'user-file-1.txt',
                'size' => 1024,
                'folder_id' => 456,
                'uuid' => 'user-uuid-123',
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z',
                'locked' => false,
                'hidden' => false,
            ],
            [
                'id' => 124,
                'display_name' => 'user-file-2.pdf',
                'filename' => 'user-file-2.pdf',
                'size' => 2048,
                'folder_id' => 456,
                'uuid' => 'user-uuid-124',
                'created_at' => '2023-01-01T00:00:00Z',
                'updated_at' => '2023-01-01T00:00:00Z',
                'locked' => false,
                'hidden' => false,
            ],
        ];

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('/users/self/files', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($filesResponse)));

        $files = File::get();

        $this->assertCount(2, $files);
        $this->assertInstanceOf(File::class, $files[0]);
        $this->assertEquals(123, $files[0]->getId());
        $this->assertEquals('user-file-1.txt', $files[0]->getDisplayName());
        $this->assertInstanceOf(File::class, $files[1]);
        $this->assertEquals(124, $files[1]->getId());
        $this->assertEquals('user-file-2.pdf', $files[1]->getDisplayName());
    }

    /**
     * Test getter and setter methods
     */
    public function testGettersAndSetters(): void
    {
        $fileData = [
            'id' => 123,
            'uuid' => 'test-uuid',
            'folder_id' => 456,
            'display_name' => 'Test File',
            'filename' => 'test-file.txt',
            'content_type' => 'text/plain',
            'url' => 'https://example.com/file.txt',
            'size' => 1024,
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-01T00:00:00Z',
            'locked' => true,
            'hidden' => true,
        ];

        $file = new File($fileData);

        // Test getters
        $this->assertEquals(123, $file->getId());
        $this->assertEquals('test-uuid', $file->getUuid());
        $this->assertEquals(456, $file->getFolderId());
        $this->assertEquals('Test File', $file->getDisplayName());
        $this->assertEquals('test-file.txt', $file->getFilename());
        $this->assertEquals('text/plain', $file->getContentType());
        $this->assertEquals('https://example.com/file.txt', $file->getUrl());
        $this->assertEquals(1024, $file->getSize());
        $this->assertEquals('2023-01-01T00:00:00Z', $file->getCreatedAt());
        $this->assertEquals('2023-01-01T00:00:00Z', $file->getUpdatedAt());
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->isHidden());

        // Test setters
        $file->setId(999);
        $file->setUuid('new-uuid');
        $file->setFolderId(777);
        $file->setDisplayName('New Display Name');
        $file->setFilename('new-filename.txt');
        $file->setContentType('application/octet-stream');
        $file->setUrl('https://example.com/new-file.txt');
        $file->setSize(2048);
        $file->setCreatedAt('2024-01-01T00:00:00Z');
        $file->setUpdatedAt('2024-01-01T00:00:00Z');
        $file->setLocked(false);
        $file->setHidden(false);

        $this->assertEquals(999, $file->getId());
        $this->assertEquals('new-uuid', $file->getUuid());
        $this->assertEquals(777, $file->getFolderId());
        $this->assertEquals('New Display Name', $file->getDisplayName());
        $this->assertEquals('new-filename.txt', $file->getFilename());
        $this->assertEquals('application/octet-stream', $file->getContentType());
        $this->assertEquals('https://example.com/new-file.txt', $file->getUrl());
        $this->assertEquals(2048, $file->getSize());
        $this->assertEquals('2024-01-01T00:00:00Z', $file->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $file->getUpdatedAt());
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->isHidden());
    }

    /**
     * Test file upload to S3 with external URLs
     */
    public function testFileUploadToS3WithExternalUrls(): void
    {
        $courseId = 123;

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content for S3');

        $fileData = [
            'name' => 's3-file.pdf',
            'size' => 4096,
            'content_type' => 'application/pdf',
            'file' => $tempFile,
        ];

        // Step 1: Canvas returns S3 upload URL and parameters
        $uploadResponse = [
            'upload_url' => 'https://canvas-files.s3.amazonaws.com/upload',
            'upload_params' => [
                'key' => 'uploads/files/123/s3-file.pdf',
                'acl' => 'private',
                'policy' => 'eyJleHBpcmF0aW9uIjoiMjAyNC0wMS0wMlQwMDowMDowMFoiLCJjb25kaXRpb25zIjpbWyJjb250ZW50LWxlbmd0aC1yYW5nZSIsMCwxMDQ4NTc2MF1dfQ==',
                'signature' => 'abc123signature',
                'AWSAccessKeyId' => 'AKIAIOSFODNN7EXAMPLE',
                'Content-Type' => 'application/pdf',
                'success_action_redirect' => 'https://canvas.example.com/api/v1/files/789/create_success',
            ],
        ];

        // Step 3: S3 redirects to Canvas API after upload
        $fileResponse = [
            'id' => 789,
            'display_name' => 's3-file.pdf',
            'filename' => 's3-file.pdf',
            'content_type' => 'application/pdf',
            'size' => 4096,
            'folder_id' => 456,
            'uuid' => 's3-uuid-123',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'url' => 'https://canvas-files.s3.amazonaws.com/files/789/s3-file.pdf',
            'locked' => false,
            'hidden' => false,
        ];

        // Expect Canvas API call (Step 1)
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo("/courses/{$courseId}/files"),
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn(new Response(200, [], json_encode($uploadResponse)));

        // Expect rawRequest for S3 upload (Step 2) and Canvas redirect (Step 3)
        $callCount = 0;
        $this->httpClientMock->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnCallback(function ($url, $method, $options) use (&$callCount, $fileResponse) {
                $callCount++;

                if ($callCount === 1) {
                    // First call: POST to S3
                    $this->assertEquals('https://canvas-files.s3.amazonaws.com/upload', $url);
                    $this->assertEquals('POST', $method);
                    // Verify skipAuth and skipDomainValidation are set for external S3 URL
                    $this->assertArrayHasKey('multipart', $options);
                    $this->assertArrayHasKey('skipAuth', $options);
                    $this->assertTrue($options['skipAuth']);
                    $this->assertArrayHasKey('skipDomainValidation', $options);
                    $this->assertTrue($options['skipDomainValidation']);

                    return new Response(303, ['Location' => 'https://canvas.example.com/api/v1/files/789/create_success'], '');
                } else {
                    // Second call: GET to Canvas redirect URL
                    $this->assertEquals('https://canvas.example.com/api/v1/files/789/create_success', $url);
                    $this->assertEquals('GET', $method);
                    // Verify skipAuth is true but skipDomainValidation might be false for Canvas redirect
                    $this->assertArrayHasKey('skipAuth', $options);
                    $this->assertTrue($options['skipAuth']);
                    $this->assertArrayHasKey('skipDomainValidation', $options);
                    $this->assertTrue($options['skipDomainValidation']);

                    return new Response(200, [], json_encode($fileResponse));
                }
            });

        $file = File::uploadToCourse($courseId, $fileData);

        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals(789, $file->getId());
        $this->assertEquals('s3-file.pdf', $file->getDisplayName());
        $this->assertEquals('application/pdf', $file->getContentType());
        $this->assertEquals(4096, $file->getSize());
        $this->assertStringContainsString('s3.amazonaws.com', $file->getUrl());

        // Clean up
        unlink($tempFile);
    }
}
