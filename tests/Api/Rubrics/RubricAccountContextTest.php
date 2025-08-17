<?php

namespace Tests\Api\Rubrics;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Dto\Rubrics\CreateRubricDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricDTO;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Config;

/**
 * Test Rubric API with Account-as-Default context pattern
 */
class RubricAccountContextTest extends TestCase
{
    private $httpClientMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Rubric::setApiClient($this->httpClientMock);
        
        // Set default account ID in config
        Config::setAccountId(1);
    }

    /**
     * Test fetching rubrics from account context (default)
     */
    public function testFetchAllFromAccountContext(): void
    {
        $rubricsData = [
            ['id' => 1, 'title' => 'Account Rubric 1', 'context_type' => 'Account', 'context_id' => 1],
            ['id' => 2, 'title' => 'Account Rubric 2', 'context_type' => 'Account', 'context_id' => 1]
        ];
        
        $mockResponse = new Response(200, [], json_encode($rubricsData));
        
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/rubrics', ['query' => []])
            ->willReturn($mockResponse);
        
        $rubrics = Rubric::fetchAll();
        
        $this->assertCount(2, $rubrics);
        $this->assertInstanceOf(Rubric::class, $rubrics[0]);
        $this->assertEquals('Account Rubric 1', $rubrics[0]->title);
        $this->assertEquals('Account', $rubrics[0]->contextType);
    }

    /**
     * Test fetching rubrics from course context
     */
    public function testFetchByContextCourse(): void
    {
        $rubricsData = [
            ['id' => 3, 'title' => 'Course Rubric 1', 'context_type' => 'Course', 'context_id' => 123],
            ['id' => 4, 'title' => 'Course Rubric 2', 'context_type' => 'Course', 'context_id' => 123]
        ];
        
        $mockResponse = new Response(200, [], json_encode($rubricsData));
        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        $paginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($rubricsData);
        
        $this->httpClientMock->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/rubrics', ['query' => []])
            ->willReturn($paginatedResponse);
        
        $rubrics = Rubric::fetchByContext('courses', 123);
        
        $this->assertCount(2, $rubrics);
        $this->assertEquals('Course Rubric 1', $rubrics[0]->title);
        $this->assertEquals('Course', $rubrics[0]->contextType);
        $this->assertEquals(123, $rubrics[0]->contextId);
    }

    /**
     * Test creating rubric in account context (default)
     */
    public function testCreateInAccountContext(): void
    {
        $createData = [
            'title' => 'New Account Rubric',
            'criteria' => [
                [
                    'description' => 'Content',
                    'points' => 20,
                    'ratings' => [
                        ['description' => 'Excellent', 'points' => 20],
                        ['description' => 'Good', 'points' => 15]
                    ]
                ]
            ]
        ];

        $responseData = [
            'rubric' => [
                'id' => 100,
                'title' => 'New Account Rubric',
                'context_type' => 'Account',
                'context_id' => 1,
                'points_possible' => 20.0
            ]
        ];

        $mockResponse = new Response(201, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('accounts/1/rubrics', $this->anything())
            ->willReturn($mockResponse);
        
        $rubric = Rubric::create($createData);
        
        $this->assertEquals(100, $rubric->id);
        $this->assertEquals('New Account Rubric', $rubric->title);
        $this->assertEquals('Account', $rubric->contextType);
        $this->assertEquals(1, $rubric->contextId);
    }

    /**
     * Test creating rubric in specific course context
     */
    public function testCreateInCourseContext(): void
    {
        $createData = [
            'title' => 'New Course Rubric',
            'criteria' => []
        ];

        $responseData = [
            'rubric' => [
                'id' => 101,
                'title' => 'New Course Rubric',
                'context_type' => 'Course',
                'context_id' => 456
            ]
        ];

        $mockResponse = new Response(201, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/456/rubrics', $this->anything())
            ->willReturn($mockResponse);
        
        $rubric = Rubric::createInContext('courses', 456, $createData);
        
        $this->assertEquals(101, $rubric->id);
        $this->assertEquals('Course', $rubric->contextType);
        $this->assertEquals(456, $rubric->contextId);
    }

    /**
     * Test finding rubric by ID in account context
     */
    public function testFindInAccountContext(): void
    {
        $responseData = [
            'id' => 200,
            'title' => 'Found Rubric',
            'context_type' => 'Account',
            'context_id' => 1
        ];

        $mockResponse = new Response(200, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/rubrics/200', ['query' => []])
            ->willReturn($mockResponse);
        
        $rubric = Rubric::find(200);
        
        $this->assertEquals(200, $rubric->id);
        $this->assertEquals('Found Rubric', $rubric->title);
    }

    /**
     * Test updating rubric in account context
     */
    public function testUpdateInAccountContext(): void
    {
        $updateData = ['title' => 'Updated Account Rubric'];
        
        $responseData = [
            'rubric' => [
                'id' => 300,
                'title' => 'Updated Account Rubric',
                'context_type' => 'Account',
                'context_id' => 1
            ]
        ];

        $mockResponse = new Response(200, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('accounts/1/rubrics/300', $this->anything())
            ->willReturn($mockResponse);
        
        $rubric = Rubric::update(300, $updateData);
        
        $this->assertEquals(300, $rubric->id);
        $this->assertEquals('Updated Account Rubric', $rubric->title);
    }

    /**
     * Test paginated fetch
     */
    public function testPaginate(): void
    {
        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        
        $this->httpClientMock->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/rubrics', ['query' => ['per_page' => 10]])
            ->willReturn($paginatedResponse);
        
        $result = Rubric::paginate(['per_page' => 10]);
        
        $this->assertInstanceOf(\CanvasLMS\Pagination\PaginationResult::class, $result);
    }

    /**
     * Test CSV upload to account context
     */
    public function testUploadCsvToAccount(): void
    {
        $filePath = __DIR__ . '/test_rubric.csv';
        file_put_contents($filePath, 'test,data');
        
        $responseData = ['import_id' => 123, 'status' => 'processing'];
        $mockResponse = new Response(200, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('accounts/1/rubrics/upload', $this->anything())
            ->willReturn($mockResponse);
        
        try {
            $result = Rubric::uploadCsv($filePath);
            $this->assertEquals(123, $result['import_id']);
        } finally {
            unlink($filePath);
        }
    }

    /**
     * Test get upload status from account context
     */
    public function testGetUploadStatusFromAccount(): void
    {
        $responseData = ['import_id' => 123, 'status' => 'completed'];
        $mockResponse = new Response(200, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/rubrics/upload/123')
            ->willReturn($mockResponse);
        
        $status = Rubric::getUploadStatus(123);
        
        $this->assertEquals('completed', $status['status']);
    }
}