<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Files;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Files\File;
use CanvasLMS\Interfaces\HttpClientInterface;

class FileRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);

        // Set up the API client
        File::setApiClient($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testFolderReturnsNullAlways(): void
    {
        // Create test file with folder_id
        $file = new File([
            'id' => 456,
            'display_name' => 'test.pdf',
            'folder_id' => 789
        ]);

        // Test the method
        $folder = $file->folder();

        // Assertions - should always return null as Folder class doesn't exist
        $this->assertNull($folder);
    }

    public function testFolderReturnsNullWhenNoFolderId(): void
    {
        // Create test file without folder_id
        $file = new File([
            'id' => 456,
            'display_name' => 'test.pdf'
        ]);

        // Test the method
        $folder = $file->folder();

        // Assertions
        $this->assertNull($folder);
    }

    public function testFolderDoesNotMakeApiCall(): void
    {
        // Create test file with folder_id
        $file = new File([
            'id' => 456,
            'display_name' => 'test.pdf',
            'folder_id' => 789
        ]);

        // Expect no API calls
        $this->mockHttpClient->expects($this->never())
            ->method('get');

        // Test the method
        $folder = $file->folder();

        // Assertions
        $this->assertNull($folder);
    }

    public function testFolderHandlesVariousFolderIdValues(): void
    {
        // Test with integer folder_id
        $file1 = new File(['folder_id' => 123]);
        $this->assertNull($file1->folder());

        // Test with string folder_id
        $file2 = new File(['folder_id' => '123']);
        $this->assertNull($file2->folder());

        // Test with null folder_id
        $file3 = new File(['folder_id' => null]);
        $this->assertNull($file3->folder());

        // Test with zero folder_id
        $file4 = new File(['folder_id' => 0]);
        $this->assertNull($file4->folder());
    }
}