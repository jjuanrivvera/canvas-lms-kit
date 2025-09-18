<?php

declare(strict_types=1);

namespace Tests\Api\Files;

use CanvasLMS\Api\Files\File;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FileContextTest extends TestCase
{
    private HttpClientInterface&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        File::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGetUsesUserContext(): void
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            ['id' => 1, 'filename' => 'file1.pdf', 'display_name' => 'File 1'],
            ['id' => 2, 'filename' => 'file2.docx', 'display_name' => 'File 2'],
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('/users/self/files', ['query' => []])
            ->willReturn($mockResponse);

        $files = File::get();

        $this->assertCount(2, $files);
        $this->assertInstanceOf(File::class, $files[0]);
        $this->assertEquals('file1.pdf', $files[0]->filename);
        $this->assertEquals('user', $files[0]->getContextType());
        $this->assertNull($files[0]->getContextId()); // 'self' user ID is unknown
    }

    public function testFetchByContextForCourse(): void
    {
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 3, 'filename' => 'syllabus.pdf', 'display_name' => 'Course Syllabus'],
            ]);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/files', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $files = File::fetchByContext('courses', 123);

        $this->assertCount(1, $files);
        $this->assertEquals('syllabus.pdf', $files[0]->filename);
        $this->assertEquals('course', $files[0]->getContextType());
        $this->assertEquals(123, $files[0]->getContextId());
    }

    public function testFetchByContextForGroup(): void
    {
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 4, 'filename' => 'group_project.zip', 'display_name' => 'Group Project'],
            ]);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('groups/456/files', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $files = File::fetchByContext('groups', 456);

        $this->assertCount(1, $files);
        $this->assertEquals('group_project.zip', $files[0]->filename);
        $this->assertEquals('group', $files[0]->getContextType());
        $this->assertEquals(456, $files[0]->getContextId());
    }

    public function testFetchCourseFilesUsesContextMethod(): void
    {
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 5, 'filename' => 'lecture.pdf', 'display_name' => 'Lecture Notes'],
            ]);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/789/files', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $files = File::fetchCourseFiles(789);

        $this->assertCount(1, $files);
        $this->assertEquals('lecture.pdf', $files[0]->filename);
        $this->assertEquals('course', $files[0]->getContextType());
        $this->assertEquals(789, $files[0]->getContextId());
    }

    public function testUploadToContextForCourse(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $mockUploadResponse = $this->createMockResponseWithBody([
            'upload_url' => 'https://canvas.example.com/upload',
            'upload_params' => ['key' => 'value'],
        ]);

        // Mock for Step 2 - Upload to external storage (with Location header)
        $mockUploadStorageResponse = $this->createMock(ResponseInterface::class);
        $mockUploadStorageResponse->method('getStatusCode')->willReturn(302);
        $mockUploadStorageResponse->method('getHeader')
            ->with('Location')
            ->willReturn(['https://canvas.example.com/confirm']);
        $mockUploadStorageResponse->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class));

        $mockFileResponse = $this->createMockResponseWithBody([
            'id' => 100,
            'filename' => 'uploaded.pdf',
            'display_name' => 'Uploaded File',
        ]);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockUploadResponse);

        $this->mockClient->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnOnConsecutiveCalls(
                $mockUploadStorageResponse,
                $mockFileResponse
            );

        $file = File::uploadToContext('courses', 999, [
            'name' => 'uploaded.pdf',
            'size' => 1024,
            'content_type' => 'application/pdf',
            'file' => $tempFile,
        ]);

        $this->assertEquals(100, $file->id);
        $this->assertEquals('uploaded.pdf', $file->filename);
        $this->assertEquals('course', $file->getContextType());
        $this->assertEquals(999, $file->getContextId());

        // Clean up
        unlink($tempFile);
    }

    public function testUploadToCourseUsesContextMethod(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $mockUploadResponse = $this->createMockResponseWithBody([
            'upload_url' => 'https://canvas.example.com/upload',
            'upload_params' => ['key' => 'value'],
        ]);

        // Mock for Step 2 - Upload to external storage (with Location header)
        $mockUploadStorageResponse = $this->createMock(ResponseInterface::class);
        $mockUploadStorageResponse->method('getStatusCode')->willReturn(302);
        $mockUploadStorageResponse->method('getHeader')
            ->with('Location')
            ->willReturn(['https://canvas.example.com/confirm']);
        $mockUploadStorageResponse->method('getBody')
            ->willReturn($this->createMock(StreamInterface::class));

        $mockFileResponse = $this->createMockResponseWithBody([
            'id' => 200,
            'filename' => 'course_doc.pdf',
            'display_name' => 'Course Document',
        ]);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockUploadResponse);

        $this->mockClient->expects($this->exactly(2))
            ->method('rawRequest')
            ->willReturnOnConsecutiveCalls(
                $mockUploadStorageResponse,
                $mockFileResponse
            );

        $file = File::uploadToCourse(111, [
            'name' => 'course_doc.pdf',
            'size' => 2048,
            'content_type' => 'application/pdf',
            'file' => $tempFile,
        ]);

        $this->assertEquals(200, $file->id);
        $this->assertEquals('course_doc.pdf', $file->filename);
        $this->assertEquals('course', $file->getContextType());
        $this->assertEquals(111, $file->getContextId());

        // Clean up
        unlink($tempFile);
    }

    public function testGetPagesWithUserContext(): void
    {
        // Create mock for first page
        $firstPageData = [['id' => 10, 'filename' => 'personal1.pdf']];
        $firstPageBody = $this->createMock(StreamInterface::class);
        $firstPageBody->method('getContents')->willReturn(json_encode($firstPageData));
        $firstPageResponse = $this->createMock(ResponseInterface::class);
        $firstPageResponse->method('getBody')->willReturn($firstPageBody);

        // Create mock for paginated response with no next page
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('getJsonData')->willReturn($firstPageData);
        $mockPaginatedResponse->method('getNext')->willReturn(null);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('/users/self/files', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $files = File::all();

        $this->assertCount(1, $files);
        $this->assertInstanceOf(File::class, $files[0]);
    }

    private function createMockResponseWithBody(array $data): ResponseInterface&MockObject
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn(json_encode($data));
        $mockBody->method('getContents')->willReturn(json_encode($data));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);
        $mockResponse->method('getStatusCode')->willReturn(200);

        return $mockResponse;
    }
}
