<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Outcomes;

use CanvasLMS\Api\OutcomeImports\OutcomeImport;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class OutcomeImportTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;
    private string $tempFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        
        OutcomeImport::setApiClient($this->mockClient);
        Config::setAccountId(1);
        
        // Create a temporary CSV file for testing
        $this->tempFilePath = sys_get_temp_dir() . '/test_outcomes.csv';
        file_put_contents($this->tempFilePath, "vendor_guid,title,description\nGUID_001,Test Outcome,Test Description");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up temp file
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function testImportFromFile(): void
    {
        $responseData = [
            'id' => 123,
            'learning_outcome_group_id' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'workflow_state' => 'created',
            'progress' => '0'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/outcome_imports',
                $this->callback(function ($options) {
                    return isset($options['multipart']) &&
                           count($options['multipart']) === 2 &&
                           $options['multipart'][0]['name'] === 'import_type' &&
                           $options['multipart'][0]['contents'] === 'instructure_csv' &&
                           $options['multipart'][1]['name'] === 'attachment';
                })
            )
            ->willReturn($this->mockResponse);
        
        $import = OutcomeImport::import($this->tempFilePath);
        
        $this->assertInstanceOf(OutcomeImport::class, $import);
        $this->assertEquals(123, $import->id);
        $this->assertEquals('created', $import->workflowState);
    }

    public function testImportFromData(): void
    {
        $csvData = "vendor_guid,title,description\nGUID_001,Test Outcome,Test Description";
        
        $responseData = [
            'id' => 456,
            'workflow_state' => 'importing',
            'progress' => '50'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/outcome_imports',
                $this->callback(function ($options) use ($csvData) {
                    return isset($options['query']['import_type']) &&
                           $options['query']['import_type'] === 'instructure_csv' &&
                           isset($options['headers']['Content-Type']) &&
                           $options['headers']['Content-Type'] === 'text/csv' &&
                           isset($options['body']) &&
                           $options['body'] === $csvData;
                })
            )
            ->willReturn($this->mockResponse);
        
        $import = OutcomeImport::importFromData($csvData);
        
        $this->assertEquals(456, $import->id);
        $this->assertEquals('importing', $import->workflowState);
        $this->assertTrue($import->isProcessing());
    }

    public function testGetStatus(): void
    {
        $responseData = [
            'id' => 789,
            'workflow_state' => 'succeeded',
            'progress' => '100',
            'ended_at' => '2024-01-01T01:00:00Z'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/outcome_imports/789')
            ->willReturn($this->mockResponse);
        
        $import = OutcomeImport::getStatus('courses', 123, 789);
        
        $this->assertEquals(789, $import->id);
        $this->assertTrue($import->isSuccessful());
        $this->assertTrue($import->isComplete());
        $this->assertEquals(100.0, $import->getProgressPercentage());
    }

    public function testGetLatestStatus(): void
    {
        $responseData = [
            'id' => 999,
            'workflow_state' => 'failed',
            'processing_errors' => [
                [1, 'Missing required field: title'],
                [3, 'Invalid calculation method']
            ]
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/outcome_imports/latest')
            ->willReturn($this->mockResponse);
        
        $import = OutcomeImport::getLatestStatus();
        
        $this->assertTrue($import->hasFailed());
        $this->assertTrue($import->hasErrors());
        $this->assertCount(2, $import->getErrors());
    }

    public function testGetFormattedErrors(): void
    {
        $import = new OutcomeImport([]);
        $import->processingErrors = [
            [1, 'Missing required field: title'],
            [3, 'Invalid calculation method'],
            [5, 'Duplicate vendor_guid']
        ];
        
        $formatted = $import->getFormattedErrors();
        
        $this->assertCount(3, $formatted);
        $this->assertEquals('Row 1: Missing required field: title', $formatted[0]);
        $this->assertEquals('Row 3: Invalid calculation method', $formatted[1]);
        $this->assertEquals('Row 5: Duplicate vendor_guid', $formatted[2]);
    }

    public function testGenerateCsvTemplate(): void
    {
        $template = OutcomeImport::generateCsvTemplate();
        
        $this->assertStringContainsString('vendor_guid', $template);
        $this->assertStringContainsString('title', $template);
        $this->assertStringContainsString('mastery_points', $template);
        $this->assertStringContainsString('ratings', $template);
        $this->assertStringContainsString('calculation_method', $template);
    }

    public function testBuildCsvFromArray(): void
    {
        $outcomes = [
            [
                'vendor_guid' => 'GUID_001',
                'title' => 'Critical Thinking',
                'description' => 'Demonstrates critical thinking',
                'mastery_points' => 3,
                'calculation_method' => 'decaying_average',
                'calculation_int' => 75,
                'ratings' => [
                    ['points' => 4, 'description' => 'Exceeds'],
                    ['points' => 3, 'description' => 'Mastery'],
                    ['points' => 2, 'description' => 'Near Mastery'],
                    ['points' => 1, 'description' => 'Below Mastery']
                ]
            ],
            [
                'vendor_guid' => 'GUID_002',
                'title' => 'Problem Solving',
                'description' => 'Solves complex problems',
                'mastery_points' => 3,
                'ratings' => []
            ]
        ];
        
        $csv = OutcomeImport::buildCsvFromArray($outcomes);
        
        $this->assertStringContainsString('vendor_guid,outcome_group_vendor_guid', $csv);
        $this->assertStringContainsString('GUID_001', $csv);
        $this->assertStringContainsString('Critical Thinking', $csv);
        $this->assertStringContainsString('4:Exceeds|3:Mastery|2:Near Mastery|1:Below Mastery', $csv);
        $this->assertStringContainsString('GUID_002', $csv);
        $this->assertStringContainsString('Problem Solving', $csv);
    }

    public function testWorkflowStateMethods(): void
    {
        $import = new OutcomeImport([]);
        
        $import->workflowState = 'created';
        $this->assertFalse($import->isComplete());
        $this->assertFalse($import->isSuccessful());
        $this->assertFalse($import->hasFailed());
        $this->assertFalse($import->isProcessing());
        
        $import->workflowState = 'importing';
        $this->assertFalse($import->isComplete());
        $this->assertTrue($import->isProcessing());
        
        $import->workflowState = 'succeeded';
        $this->assertTrue($import->isComplete());
        $this->assertTrue($import->isSuccessful());
        $this->assertFalse($import->hasFailed());
        
        $import->workflowState = 'failed';
        $this->assertTrue($import->isComplete());
        $this->assertFalse($import->isSuccessful());
        $this->assertTrue($import->hasFailed());
    }

    public function testImportToContextWithGroup(): void
    {
        $responseData = [
            'id' => 321,
            'learning_outcome_group_id' => 5,
            'workflow_state' => 'created'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'courses/10/outcome_imports/group/5',
                $this->anything()
            )
            ->willReturn($this->mockResponse);
        
        $import = OutcomeImport::importToContext('courses', 10, $this->tempFilePath, 5);
        
        $this->assertEquals(321, $import->id);
        $this->assertEquals(5, $import->learningOutcomeGroupId);
    }
}