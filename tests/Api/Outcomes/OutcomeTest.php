<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Outcomes;

use CanvasLMS\Api\Outcomes\Outcome;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class OutcomeTest extends TestCase
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

        Outcome::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGetUsesAccountContext(): void
    {
        $expectedData = [
            [
                'id' => 1,
                'title' => 'Critical Thinking',
                'description' => 'Student demonstrates critical thinking skills',
                'mastery_points' => 3.0,
            ],
        ];

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('getContents')->willReturn(json_encode($expectedData));

        $this->mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/outcome_groups/global/outcomes', ['query' => []])
            ->willReturn($this->mockResponse);

        $outcomes = Outcome::get();

        $this->assertCount(1, $outcomes);
        $this->assertInstanceOf(Outcome::class, $outcomes[0]);
        $this->assertEquals('Critical Thinking', $outcomes[0]->title);
    }

    public function testFetchByContextWithCourseContext(): void
    {
        $expectedData = [
            [
                'id' => 2,
                'title' => 'Problem Solving',
                'description' => 'Student can solve complex problems',
                'mastery_points' => 4.0,
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($expectedData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/outcome_groups/global/outcomes', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $outcomes = Outcome::fetchByContext('courses', 123);

        $this->assertCount(1, $outcomes);
        $this->assertEquals('Problem Solving', $outcomes[0]->title);
    }

    public function testFindOutcome(): void
    {
        $outcomeData = [
            'id' => 5,
            'title' => 'Communication',
            'description' => 'Effective communication skills',
            'mastery_points' => 3.5,
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4],
                ['description' => 'Mastery', 'points' => 3.5],
                ['description' => 'Near Mastery', 'points' => 2],
                ['description' => 'Below Mastery', 'points' => 1],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($outcomeData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('outcomes/5')
            ->willReturn($this->mockResponse);

        $outcome = Outcome::find(5);

        $this->assertInstanceOf(Outcome::class, $outcome);
        $this->assertEquals(5, $outcome->id);
        $this->assertEquals('Communication', $outcome->title);
        $this->assertCount(4, $outcome->ratings);
    }

    public function testCreateOutcomeWithAccountContext(): void
    {
        $createData = [
            'title' => 'New Outcome',
            'description' => 'A new learning outcome',
            'mastery_points' => 3,
            'ratings' => [
                ['description' => 'Exceeds', 'points' => 4],
                ['description' => 'Mastery', 'points' => 3],
                ['description' => 'Near Mastery', 'points' => 2],
                ['description' => 'Below Mastery', 'points' => 1],
            ],
        ];

        $responseData = array_merge($createData, ['id' => 10]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/outcome_groups/global/outcomes',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);

        $outcome = Outcome::create($createData);

        $this->assertInstanceOf(Outcome::class, $outcome);
        $this->assertEquals(10, $outcome->id);
        $this->assertEquals('New Outcome', $outcome->title);
    }

    public function testUpdateOutcome(): void
    {
        $outcome = new Outcome(['id' => 15, 'title' => 'Original Title']);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $responseData = array_merge($updateData, ['id' => 15]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with(
                'outcomes/15',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);

        $updatedOutcome = $outcome->update($updateData);

        $this->assertEquals('Updated Title', $updatedOutcome->title);
        $this->assertEquals('Updated description', $updatedOutcome->description);
    }

    public function testValidateRatings(): void
    {
        $outcome = new Outcome([]);

        $outcome->ratings = [
            ['description' => 'Exceeds', 'points' => 4],
            ['description' => 'Mastery', 'points' => 3],
            ['description' => 'Near Mastery', 'points' => 2],
            ['description' => 'Below Mastery', 'points' => 1],
        ];
        $outcome->masteryPoints = 3;

        $this->assertTrue($outcome->validateRatings());

        $outcome->masteryPoints = 5;
        $this->assertFalse($outcome->validateRatings());

        $outcome->ratings = [
            ['description' => 'Invalid'],
        ];
        $this->assertFalse($outcome->validateRatings());
    }

    public function testGetCalculationMethodDisplayName(): void
    {
        $outcome = new Outcome([]);

        $outcome->calculationMethod = 'decaying_average';
        $this->assertEquals('Decaying Average', $outcome->getCalculationMethodDisplayName());

        $outcome->calculationMethod = 'n_mastery';
        $this->assertEquals('N Number of Times', $outcome->getCalculationMethodDisplayName());

        $outcome->calculationMethod = 'latest';
        $this->assertEquals('Most Recent Score', $outcome->getCalculationMethodDisplayName());

        $outcome->calculationMethod = 'highest';
        $this->assertEquals('Highest Score', $outcome->getCalculationMethodDisplayName());

        $outcome->calculationMethod = 'average';
        $this->assertEquals('Average', $outcome->getCalculationMethodDisplayName());

        $outcome->calculationMethod = 'unknown';
        $this->assertEquals('Unknown', $outcome->getCalculationMethodDisplayName());
    }

    public function testDeleteFromContext(): void
    {
        $outcome = new Outcome(['id' => 20]);

        $this->mockResponse->method('getStatusCode')
            ->willReturn(204);

        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('courses/123/outcome_groups/global/outcomes/20')
            ->willReturn($this->mockResponse);

        $result = $outcome->deleteFromContext('courses', 123);

        $this->assertTrue($result);
    }

    public function testGetAlignments(): void
    {
        $outcome = new Outcome(['id' => 25]);

        $alignmentData = [
            ['id' => 1, 'assignment_id' => 100, 'rubric_id' => 50],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($alignmentData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/outcomes/25/alignments')
            ->willReturn($this->mockResponse);

        $alignments = $outcome->getAlignments('courses', 123);

        $this->assertIsArray($alignments);
        $this->assertCount(1, $alignments);
        $this->assertEquals(100, $alignments[0]['assignment_id']);
    }

    public function testImportFromVendor(): void
    {
        $vendorGuid = 'standard_123';

        $responseData = [
            'outcome' => [
                'id' => 30,
                'title' => 'Imported Standard',
                'vendor_guid' => $vendorGuid,
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/outcome_groups/global/import',
                $this->callback(function ($options) use ($vendorGuid) {
                    return isset($options['multipart']) &&
                           $options['multipart'][0]['name'] === 'source_outcome_id' &&
                           $options['multipart'][0]['contents'] === $vendorGuid;
                })
            )
            ->willReturn($this->mockResponse);

        $outcome = Outcome::importFromVendor('accounts', 1, $vendorGuid);

        $this->assertInstanceOf(Outcome::class, $outcome);
        $this->assertEquals(30, $outcome->id);
        $this->assertEquals('Imported Standard', $outcome->title);
    }
}
