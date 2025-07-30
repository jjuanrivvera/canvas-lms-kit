<?php

namespace Tests\Api\Rubrics;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Dto\Rubrics\CreateRubricAssociationDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricAssociationDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Config;
use CanvasLMS\Api\Courses\Course;

class RubricAssociationTest extends TestCase
{
    /**
     * @var RubricAssociation
     */
    private RubricAssociation $rubricAssociation;

    /**
     * @var mixed
     */
    private $httpClientMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        RubricAssociation::setApiClient($this->httpClientMock);
        
        $this->rubricAssociation = new RubricAssociation([]);
    }

    protected function tearDown(): void
    {
        // Reset static course state between tests
        // We can't set it to null directly, so we'll rely on each test setting its own context
        parent::tearDown();
    }

    /**
     * Test the create rubric association method
     */
    public function testCreateRubricAssociation(): void
    {
        $expectedResult = [
            'id' => 1,
            'rubric_id' => 123,
            'association_id' => 456,
            'association_type' => 'Assignment',
            'use_for_grading' => true,
            'summary_data' => null,
            'purpose' => 'grading',
            'hide_score_total' => false,
            'hide_points' => false,
            'hide_outcome_results' => false,
            'bookmarked' => true,
            'context_id' => 100,
            'context_type' => 'Course',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/100/rubric_associations',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 123;
        $dto->associationId = 456;
        $dto->associationType = 'Assignment';
        $dto->useForGrading = true;
        $dto->purpose = 'grading';
        $dto->bookmarked = true;

        $association = RubricAssociation::create($dto, 100);

        $this->assertEquals(1, $association->id);
        $this->assertEquals(123, $association->rubricId);
        $this->assertEquals(456, $association->associationId);
        $this->assertEquals('Assignment', $association->associationType);
        $this->assertTrue($association->useForGrading);
        $this->assertEquals('grading', $association->purpose);
        $this->assertTrue($association->bookmarked);
        $this->assertEquals(100, $association->contextId);
        $this->assertEquals('Course', $association->contextType);
    }

    /**
     * Test create without current course throws exception
     */
    public function testCreateWithoutCurrentCourseThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Course context must be set for RubricAssociation operations");

        $dto = new CreateRubricAssociationDTO();
        RubricAssociation::create($dto);
    }

    /**
     * Test find method throws exception
     */
    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Finding individual rubric associations is not supported");

        RubricAssociation::find(1);
    }

    /**
     * Test fetchAll method throws exception
     */
    public function testFetchAllThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Fetching all rubric associations is not supported");

        RubricAssociation::fetchAll();
    }

    /**
     * Test update rubric association
     */
    public function testUpdateRubricAssociation(): void
    {
        $expectedResult = [
            'id' => 2,
            'rubric_id' => 124,
            'association_id' => 457,
            'association_type' => 'Assignment',
            'use_for_grading' => false,
            'purpose' => 'bookmark',
            'hide_score_total' => true
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/100/rubric_associations/2',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = false;
        $dto->purpose = 'bookmark';
        $dto->hideScoreTotal = true;

        $association = RubricAssociation::update(2, $dto, 100);

        $this->assertEquals(2, $association->id);
        $this->assertFalse($association->useForGrading);
        $this->assertEquals('bookmark', $association->purpose);
        $this->assertTrue($association->hideScoreTotal);
    }

    /**
     * Test delete rubric association
     */
    public function testDeleteRubricAssociation(): void
    {
        // Set course context for delete operation
        RubricAssociation::setCourse(new Course(['id' => 200]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/200/rubric_associations/3')
            ->willReturn(new Response(204));

        $association = new RubricAssociation(['id' => 3]);
        $result = $association->delete();

        $this->assertTrue($result);
    }

    /**
     * Test delete without ID throws exception
     */
    public function testDeleteWithoutIdThrowsException(): void
    {
        $association = new RubricAssociation([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Cannot delete rubric association without ID");

        $association->delete();
    }

    /**
     * Test delete without current course throws exception
     */
    public function testDeleteWithoutCurrentCourseThrowsException(): void
    {
        // Set course context to null to test the exception
        RubricAssociation::setCourse(null);
        
        $association = new RubricAssociation(['id' => 4]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Course context must be set for delete operation");

        $association->delete();
    }

    /**
     * Test save existing association
     */
    public function testSaveExistingAssociation(): void
    {
        // Set course context for save operation
        RubricAssociation::setCourse(new Course(['id' => 300]));

        $expectedResult = [
            'id' => 5,
            'rubric_id' => 125,
            'use_for_grading' => true
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/300/rubric_associations/5',
                $this->isType('array')
            )
            ->willReturn($response);

        $association = new RubricAssociation([
            'id' => 5,
            'rubric_id' => 125,
            'use_for_grading' => true
        ]);

        $savedAssociation = $association->save();

        $this->assertEquals(5, $savedAssociation->id);
        $this->assertTrue($savedAssociation->useForGrading);
    }

    /**
     * Test save new association
     */
    public function testSaveNewAssociation(): void
    {

        $expectedResult = [
            'id' => 6,
            'rubric_id' => 126,
            'association_id' => 458,
            'association_type' => 'Discussion',
            'use_for_grading' => false
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/400/rubric_associations',
                $this->isType('array')
            )
            ->willReturn($response);

        $association = new RubricAssociation([
            'rubric_id' => 126,
            'association_id' => 458,
            'association_type' => 'Discussion',
            'use_for_grading' => false
        ]);

        $savedAssociation = $association->save(400);

        $this->assertEquals(6, $savedAssociation->id);
        $this->assertEquals(126, $savedAssociation->rubricId);
        $this->assertEquals(458, $savedAssociation->associationId);
        $this->assertEquals('Discussion', $savedAssociation->associationType);
        $this->assertFalse($savedAssociation->useForGrading);
    }

    /**
     * Test save without required fields throws exception
     */
    public function testSaveWithoutRequiredFieldsThrowsException(): void
    {

        $association = new RubricAssociation([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Rubric ID is required for creating a new association");

        $association->save(500);
    }

    /**
     * Test constructor with full data
     */
    public function testConstructorWithFullData(): void
    {
        $data = [
            'id' => 7,
            'rubric_id' => 127,
            'association_id' => 459,
            'association_type' => 'Assignment',
            'use_for_grading' => true,
            'summary_data' => ['some' => 'data'],
            'purpose' => 'grading',
            'hide_score_total' => false,
            'hide_points' => false,
            'hide_outcome_results' => false,
            'bookmarked' => true,
            'context_id' => 600,
            'context_type' => 'Course',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z'
        ];

        $association = new RubricAssociation($data);

        $this->assertEquals(7, $association->id);
        $this->assertEquals(127, $association->rubricId);
        $this->assertEquals(459, $association->associationId);
        $this->assertEquals('Assignment', $association->associationType);
        $this->assertTrue($association->useForGrading);
        $this->assertEquals(['some' => 'data'], $association->summaryData);
        $this->assertEquals('grading', $association->purpose);
        $this->assertFalse($association->hideScoreTotal);
        $this->assertFalse($association->hidePoints);
        $this->assertFalse($association->hideOutcomeResults);
        $this->assertTrue($association->bookmarked);
        $this->assertEquals(600, $association->contextId);
        $this->assertEquals('Course', $association->contextType);
        $this->assertEquals('2024-01-01T00:00:00Z', $association->createdAt);
        $this->assertEquals('2024-01-02T00:00:00Z', $association->updatedAt);
    }
}