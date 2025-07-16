<?php

namespace Tests\Dto\Files;

use Exception;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Files\UploadFileDto;

class UploadFileDtoTest extends TestCase
{
    /**
     * Test basic DTO construction and properties
     */
    public function testConstruction(): void
    {
        $data = [
            'name' => 'test-file.pdf',
            'size' => 1024,
            'content_type' => 'application/pdf',
            'parent_folder_id' => 123,
            'on_duplicate' => 'rename',
            'file' => 'test content'
        ];

        $dto = new UploadFileDto($data);

        $this->assertEquals('test-file.pdf', $dto->getName());
        $this->assertEquals(1024, $dto->getSize());
        $this->assertEquals('application/pdf', $dto->getContentType());
        $this->assertEquals(123, $dto->getParentFolderId());
        $this->assertEquals('rename', $dto->getOnDuplicate());
        $this->assertEquals('test content', $dto->getFile());
    }

    /**
     * Test toApiArray method
     */
    public function testToApiArray(): void
    {
        $data = [
            'name' => 'api-test.txt',
            'size' => 512,
            'content_type' => 'text/plain',
            'parent_folder_id' => 456,
            'on_duplicate' => 'overwrite'
        ];

        $dto = new UploadFileDto($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'name', 'contents' => 'api-test.txt'],
            ['name' => 'size', 'contents' => '512'],
            ['name' => 'content_type', 'contents' => 'text/plain'],
            ['name' => 'parent_folder_id', 'contents' => '456'],
            ['name' => 'on_duplicate', 'contents' => 'overwrite']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test toApiArray excludes null values
     */
    public function testToApiArrayExcludesNullValues(): void
    {
        $data = [
            'name' => 'minimal-test.txt',
            'size' => 256
        ];

        $dto = new UploadFileDto($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'name', 'contents' => 'minimal-test.txt'],
            ['name' => 'size', 'contents' => '256']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test getFileResource with file path
     */
    public function testGetFileResourceWithFilePath(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test file content');

        $dto = new UploadFileDto(['file' => $tempFile]);
        $resource = $dto->getFileResource();

        $this->assertIsResource($resource);
        $this->assertEquals('test file content', stream_get_contents($resource));

        fclose($resource);
        unlink($tempFile);
    }

    /**
     * Test getFileResource with resource
     */
    public function testGetFileResourceWithResource(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'resource test content');
        $resource = fopen($tempFile, 'r');

        $dto = new UploadFileDto(['file' => $resource]);
        $returnedResource = $dto->getFileResource();

        $this->assertSame($resource, $returnedResource);

        fclose($resource);
        unlink($tempFile);
    }

    /**
     * Test getFileResource with non-existent file
     */
    public function testGetFileResourceWithNonExistentFile(): void
    {
        // Use a path that definitely doesn't exist
        $nonExistentFile = '/absolutely/non/existent/path/file.txt';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to open file: {$nonExistentFile}");

        $dto = new UploadFileDto(['file' => $nonExistentFile]);
        $dto->getFileResource();
    }

    /**
     * Test getFileResource with path traversal attack
     */
    public function testGetFileResourceWithPathTraversal(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid file path: directory traversal not allowed');

        $dto = new UploadFileDto(['file' => '../../../etc/passwd']);
        $dto->getFileResource();
    }

    /**
     * Test getFileResource with relative path containing ..
     */
    public function testGetFileResourceWithRelativePathAttack(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid file path: directory traversal not allowed');

        $dto = new UploadFileDto(['file' => './test/../../../sensitive/file.txt']);
        $dto->getFileResource();
    }

    /**
     * Test getFileResource with null file
     */
    public function testGetFileResourceWithNullFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is required for upload');

        $dto = new UploadFileDto([]);
        $dto->getFileResource();
    }

    /**
     * Test auto-detect content type
     */
    public function testAutoDetectContentType(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test') . '.txt';
        file_put_contents($tempFile, 'text content');

        $dto = new UploadFileDto(['file' => $tempFile]);
        $dto->autoDetectContentType();

        // Content type detection may vary by system, so we check if it's set
        $this->assertNotNull($dto->getContentType());

        unlink($tempFile);
    }

    /**
     * Test auto-detect size
     */
    public function testAutoDetectSize(): void
    {
        $content = 'This is test content for size detection';
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, $content);

        $dto = new UploadFileDto(['file' => $tempFile]);
        $dto->autoDetectSize();

        $this->assertEquals(strlen($content), $dto->getSize());

        unlink($tempFile);
    }

    /**
     * Test auto-detect name
     */
    public function testAutoDetectName(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test') . '.pdf';
        file_put_contents($tempFile, 'pdf content');

        $dto = new UploadFileDto(['file' => $tempFile]);
        $dto->autoDetectName();

        $this->assertEquals(basename($tempFile), $dto->getName());

        unlink($tempFile);
    }

    /**
     * Test auto-detect all file properties
     */
    public function testAutoDetectFileProperties(): void
    {
        $content = 'Complete auto-detection test content';
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test') . '.txt';
        file_put_contents($tempFile, $content);

        $dto = new UploadFileDto(['file' => $tempFile]);
        $dto->autoDetectFileProperties();

        $this->assertEquals(basename($tempFile), $dto->getName());
        $this->assertEquals(strlen($content), $dto->getSize());
        $this->assertNotNull($dto->getContentType());

        unlink($tempFile);
    }

    /**
     * Test validation with valid data
     */
    public function testValidationSuccess(): void
    {
        $dto = new UploadFileDto([
            'name' => 'valid-file.txt',
            'file' => 'content',
            'parent_folder_id' => 123
        ]);

        $this->assertTrue($dto->validate());
    }

    /**
     * Test validation with URL instead of file
     */
    public function testValidationWithUrl(): void
    {
        $dto = new UploadFileDto([
            'name' => 'url-file.txt',
            'url' => 'https://example.com/file.txt'
        ]);

        $this->assertTrue($dto->validate());
    }

    /**
     * Test validation fails without name
     */
    public function testValidationFailsWithoutName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File name is required');

        $dto = new UploadFileDto([
            'file' => 'content'
        ]);

        $dto->validate();
    }

    /**
     * Test validation fails without file or URL
     */
    public function testValidationFailsWithoutFileOrUrl(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Either file or URL is required');

        $dto = new UploadFileDto([
            'name' => 'no-file.txt'
        ]);

        $dto->validate();
    }

    /**
     * Test validation fails with both parent folder ID and path
     */
    public function testValidationFailsWithBothParentFolderIdAndPath(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot specify both parent_folder_id and parent_folder_path');

        $dto = new UploadFileDto([
            'name' => 'conflict.txt',
            'file' => 'content',
            'parent_folder_id' => 123,
            'parent_folder_path' => '/path/to/folder'
        ]);

        $dto->validate();
    }

    /**
     * Test validation fails with invalid on_duplicate value
     */
    public function testValidationFailsWithInvalidOnDuplicate(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('on_duplicate must be either "overwrite" or "rename"');

        $dto = new UploadFileDto([
            'name' => 'invalid-duplicate.txt',
            'file' => 'content',
            'on_duplicate' => 'invalid_option'
        ]);

        $dto->validate();
    }

    /**
     * Test all getter and setter methods
     */
    public function testGettersAndSetters(): void
    {
        $dto = new UploadFileDto([]);

        // Test name
        $dto->setName('setter-test.txt');
        $this->assertEquals('setter-test.txt', $dto->getName());

        // Test size
        $dto->setSize(2048);
        $this->assertEquals(2048, $dto->getSize());

        // Test content type
        $dto->setContentType('application/pdf');
        $this->assertEquals('application/pdf', $dto->getContentType());

        // Test parent folder ID
        $dto->setParentFolderId(789);
        $this->assertEquals(789, $dto->getParentFolderId());

        // Test parent folder path
        $dto->setParentFolderPath('/test/path');
        $this->assertEquals('/test/path', $dto->getParentFolderPath());

        // Test on duplicate
        $dto->setOnDuplicate('overwrite');
        $this->assertEquals('overwrite', $dto->getOnDuplicate());

        // Test file
        $dto->setFile('test content');
        $this->assertEquals('test content', $dto->getFile());

        // Test URL
        $dto->setUrl('https://example.com/test.txt');
        $this->assertEquals('https://example.com/test.txt', $dto->getUrl());

        // Test submit assignment
        $dto->setSubmitAssignment(true);
        $this->assertTrue($dto->getSubmitAssignment());
    }

    /**
     * Test with camelCase property names
     */
    public function testCamelCaseProperties(): void
    {
        $data = [
            'parentFolderId' => 456,
            'contentType' => 'text/plain',
            'onDuplicate' => 'rename',
            'submitAssignment' => true
        ];

        $dto = new UploadFileDto($data);

        $this->assertEquals(456, $dto->getParentFolderId());
        $this->assertEquals('text/plain', $dto->getContentType());
        $this->assertEquals('rename', $dto->getOnDuplicate());
        $this->assertTrue($dto->getSubmitAssignment());
    }
}