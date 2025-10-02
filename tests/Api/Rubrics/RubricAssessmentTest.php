<?php

declare(strict_types=1);

namespace Tests\Api\Rubrics;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Rubrics\RubricAssessment;
use CanvasLMS\Dto\Rubrics\CreateRubricAssessmentDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricAssessmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RubricAssessmentTest extends TestCase
{
    /**
     * @var RubricAssessment
     */
    private RubricAssessment $rubricAssessment;

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
        RubricAssessment::setApiClient($this->httpClientMock);

        $this->rubricAssessment = new RubricAssessment([]);
    }

    /**
     * Test the create rubric assessment method for assignment
     */
    public function testCreateRubricAssessmentForAssignment(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $expectedResult = [
            'id' => 1,
            'rubric_id' => 123,
            'rubric_association_id' => 456,
            'score' => 18.5,
            'data' => [
                [
                    'id' => 'criterion_1',
                    'points' => 9.5,
                    'description' => 'Good',
                    'comments' => 'Nice work on this criterion',
                ],
                [
                    'id' => 'criterion_2',
                    'points' => 9.0,
                    'description' => 'Excellent',
                    'comments' => 'Outstanding performance',
                ],
            ],
            'comments' => 'Overall good work',
            'user_id' => 789,
            'assessor_id' => 999,
            'artifact_type' => 'Submission',
            'artifact_id' => 111,
            'assessment_type' => 'grading',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/100/rubric_associations/456/rubric_assessments',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricAssessmentDTO();
        $dto->userId = 789;
        $dto->assessmentType = 'grading';
        $dto->criterionData = [
            'criterion_1' => ['points' => 9.5, 'comments' => 'Nice work on this criterion'],
            'criterion_2' => ['points' => 9.0, 'comments' => 'Outstanding performance'],
        ];

        $assessment = RubricAssessment::create($dto, 456);

        $this->assertEquals(1, $assessment->id);
        $this->assertEquals(123, $assessment->rubricId);
        $this->assertEquals(456, $assessment->rubricAssociationId);
        $this->assertEquals(18.5, $assessment->score);
        $this->assertIsArray($assessment->data);
        $this->assertCount(2, $assessment->data);
        $this->assertEquals('Overall good work', $assessment->comments);
        $this->assertEquals(789, $assessment->userId);
        $this->assertEquals(999, $assessment->assessorId);
        $this->assertEquals('Submission', $assessment->artifactType);
        $this->assertEquals(111, $assessment->artifactId);
        $this->assertEquals('grading', $assessment->assessmentType);
    }

    /**
     * Test the create rubric assessment method for moderated grading
     */
    public function testCreateRubricAssessmentForModeratedGrading(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $expectedResult = [
            'id' => 2,
            'rubric_id' => 124,
            'assessment_type' => 'provisional_grade',
            'provisional_grade_id' => 222,
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/100/rubric_associations/458/rubric_assessments',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricAssessmentDTO();
        $dto->assessmentType = 'provisional_grade';
        $dto->provisional = true;

        $assessment = RubricAssessment::create($dto, 458);

        $this->assertEquals(2, $assessment->id);
        $this->assertEquals(124, $assessment->rubricId);
        $this->assertEquals('provisional_grade', $assessment->assessmentType);
        $this->assertEquals(222, $assessment->provisionalGradeId);
    }

    /**
     * Test create rubric assessment with array input
     */
    public function testCreateRubricAssessmentWithArrayInput(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $assessmentData = [
            'userId' => 789,
            'assessmentType' => 'grading',
            'criterionData' => [
                'criterion_1' => [
                    'points' => 9.5,
                    'comments' => 'Nice work on this criterion',
                ],
                'criterion_2' => [
                    'points' => 9.0,
                    'comments' => 'Outstanding performance',
                ],
            ],
            'provisional' => false,
            'final' => false,
            'gradedAnonymously' => false,
        ];

        $expectedResult = [
            'id' => 3,
            'rubric_id' => 125,
            'rubric_association_id' => 457,
            'score' => 18.5,
            'user_id' => 789,
            'assessment_type' => 'grading',
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/100/rubric_associations/457/rubric_assessments',
                $this->isType('array')
            )
            ->willReturn($response);

        $assessment = RubricAssessment::create($assessmentData, 457);

        $this->assertEquals(3, $assessment->id);
        $this->assertEquals(125, $assessment->rubricId);
        $this->assertEquals(457, $assessment->rubricAssociationId);
        $this->assertEquals(18.5, $assessment->score);
        $this->assertEquals(789, $assessment->userId);
        $this->assertEquals('grading', $assessment->assessmentType);
    }

    /**
     * Test create without required context throws exception
     */
    public function testCreateWithoutRequiredContextThrowsException(): void
    {
        $this->expectException(\ArgumentCountError::class);

        $dto = new CreateRubricAssessmentDTO();
        RubricAssessment::create($dto);
    }

    /**
     * Test create without current course throws exception
     */
    public function testCreateWithoutCurrentCourseThrowsException(): void
    {
        RubricAssessment::setCourse(null);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context must be set for RubricAssessment operations');

        $dto = new CreateRubricAssessmentDTO();
        RubricAssessment::create($dto, 1);
    }

    /**
     * Test find method throws exception
     */
    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Finding individual rubric assessments is not supported');

        RubricAssessment::find(1);
    }

    /**
     * Test fetchAll method throws exception
     */
    public function testGetThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Fetching all rubric assessments is not supported');

        RubricAssessment::get();
    }

    /**
     * Test update rubric assessment for assignment
     */
    public function testUpdateRubricAssessmentForAssignment(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $expectedResult = [
            'id' => 3,
            'rubric_id' => 125,
            'score' => 20.0,
            'data' => [
                ['id' => 'criterion_1', 'points' => 10.0],
            ],
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/100/rubric_associations/457/rubric_assessments/3',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new UpdateRubricAssessmentDTO();
        $dto->criterionData = [
            'criterion_1' => ['points' => 10.0],
        ];

        $assessment = RubricAssessment::update(3, $dto, 457);

        $this->assertEquals(3, $assessment->id);
        $this->assertEquals(125, $assessment->rubricId);
        $this->assertEquals(20.0, $assessment->score);
    }

    /**
     * Test update rubric assessment for moderated grading
     */
    public function testUpdateRubricAssessmentForModeratedGrading(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $expectedResult = [
            'id' => 4,
            'assessment_type' => 'provisional_grade',
            'provisional_grade_id' => 444,
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/100/rubric_associations/459/rubric_assessments/4',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new UpdateRubricAssessmentDTO();
        $dto->provisional = true;
        $dto->final = true;

        $assessment = RubricAssessment::update(4, $dto, 459);

        $this->assertEquals(4, $assessment->id);
        $this->assertEquals('provisional_grade', $assessment->assessmentType);
        $this->assertEquals(444, $assessment->provisionalGradeId);
    }

    /**
     * Test update rubric assessment with array input
     */
    public function testUpdateRubricAssessmentWithArrayInput(): void
    {
        // Set course context
        RubricAssessment::setCourse(new Course(['id' => 100]));

        $updateData = [
            'userId' => 790,
            'assessmentType' => 'grading',
            'criterionData' => [
                'criterion_1' => ['points' => 10.0, 'comments' => 'Perfect!'],
            ],
            'provisional' => false,
            'final' => false,
            'gradedAnonymously' => true,
        ];

        $expectedResult = [
            'id' => 5,
            'rubric_id' => 126,
            'score' => 10.0,
            'user_id' => 790,
            'assessment_type' => 'grading',
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/100/rubric_associations/458/rubric_assessments/5',
                $this->isType('array')
            )
            ->willReturn($response);

        $assessment = RubricAssessment::update(5, $updateData, 458);

        $this->assertEquals(5, $assessment->id);
        $this->assertEquals(126, $assessment->rubricId);
        $this->assertEquals(10.0, $assessment->score);
        $this->assertEquals(790, $assessment->userId);
        $this->assertEquals('grading', $assessment->assessmentType);
    }

    /**
     * Test delete rubric assessment for assignment
     */
    public function testDeleteRubricAssessmentForAssignment(): void
    {
        RubricAssessment::setCourse(new Course(['id' => 200]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/200/rubric_associations/458/rubric_assessments/5')
            ->willReturn(new Response(204));

        $assessment = new RubricAssessment([
            'id' => 5,
            'rubric_association_id' => 458,
        ]);

        $result = $assessment->delete();

        $this->assertInstanceOf(RubricAssessment::class, $result);
    }

    /**
     * Test delete rubric assessment for moderated grading
     */
    public function testDeleteRubricAssessmentForModeratedGrading(): void
    {
        RubricAssessment::setCourse(new Course(['id' => 300]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/300/rubric_associations/460/rubric_assessments/6')
            ->willReturn(new Response(204));

        $assessment = new RubricAssessment([
            'id' => 6,
            'rubric_association_id' => 460,
            'artifact_type' => 'ModeratedGrading',
            'artifact_id' => 666,
            'provisional_grade_id' => 555,
        ]);

        $result = $assessment->delete();

        $this->assertInstanceOf(RubricAssessment::class, $result);
    }

    /**
     * Test delete without ID throws exception
     */
    public function testDeleteWithoutIdThrowsException(): void
    {
        $assessment = new RubricAssessment([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete rubric assessment without ID');

        $assessment->delete();
    }

    /**
     * Test delete without context throws exception
     */
    public function testDeleteWithoutContextThrowsException(): void
    {
        $assessment = new RubricAssessment(['id' => 7]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete rubric assessment without association ID');

        $assessment->delete();
    }

    /**
     * Test save existing assessment
     */
    public function testSaveExistingAssessment(): void
    {
        RubricAssessment::setCourse(new Course(['id' => 400]));

        $expectedResult = ['id' => 8, 'score' => 15.0];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/400/rubric_associations/459/rubric_assessments/8',
                $this->isType('array')
            )
            ->willReturn($response);

        $assessment = new RubricAssessment([
            'id' => 8,
            'rubric_association_id' => 459,
            'criterion_data' => ['criterion_1' => ['points' => 7.5]],
        ]);

        $savedAssessment = $assessment->save();

        $this->assertEquals(8, $savedAssessment->id);
        $this->assertEquals(15.0, $savedAssessment->score);
    }

    /**
     * Test save new assessment
     */
    public function testSaveNewAssessment(): void
    {
        RubricAssessment::setCourse(new Course(['id' => 500]));

        $expectedResult = [
            'id' => 9,
            'rubric_id' => 126,
            'user_id' => 888,
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/500/rubric_associations/460/rubric_assessments',
                $this->isType('array')
            )
            ->willReturn($response);

        $assessment = new RubricAssessment([
            'rubric_association_id' => 460,
            'user_id' => 888,
            'assessment_type' => 'grading',
        ]);

        $savedAssessment = $assessment->save();

        $this->assertEquals(9, $savedAssessment->id);
        $this->assertEquals(126, $savedAssessment->rubricId);
        $this->assertEquals(888, $savedAssessment->userId);
    }

    /**
     * Test save without required context throws exception
     */
    public function testSaveWithoutRequiredContextThrowsException(): void
    {
        RubricAssessment::setCourse(new Course(['id' => 600]));

        $assessment = new RubricAssessment([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Rubric association ID required for create');

        $assessment->save();
    }

    /**
     * Test constructor with full data
     */
    public function testConstructorWithFullData(): void
    {
        $data = [
            'id' => 10,
            'rubric_id' => 127,
            'rubric_association_id' => 461,
            'score' => 25.5,
            'data' => [
                ['id' => 'criterion_1', 'points' => 15.5],
                ['id' => 'criterion_2', 'points' => 10.0],
            ],
            'comments' => 'Great work overall',
            'user_id' => 999,
            'assessor_id' => 111,
            'artifact_type' => 'Submission',
            'artifact_id' => 222,
            'assessment_type' => 'peer_review',
            'provisional_grade_id' => 333,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $assessment = new RubricAssessment($data);

        $this->assertEquals(10, $assessment->id);
        $this->assertEquals(127, $assessment->rubricId);
        $this->assertEquals(461, $assessment->rubricAssociationId);
        $this->assertEquals(25.5, $assessment->score);
        $this->assertIsArray($assessment->data);
        $this->assertCount(2, $assessment->data);
        $this->assertEquals('Great work overall', $assessment->comments);
        $this->assertEquals(999, $assessment->userId);
        $this->assertEquals(111, $assessment->assessorId);
        $this->assertEquals('Submission', $assessment->artifactType);
        $this->assertEquals(222, $assessment->artifactId);
        $this->assertEquals('peer_review', $assessment->assessmentType);
        $this->assertEquals(333, $assessment->provisionalGradeId);
        $this->assertInstanceOf(\DateTime::class, $assessment->createdAt);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $assessment->createdAt->format('c'));
        $this->assertInstanceOf(\DateTime::class, $assessment->updatedAt);
        $this->assertEquals('2024-01-02T00:00:00+00:00', $assessment->updatedAt->format('c'));
    }

    /**
     * Test constructor converts criterion_data to criterionData
     */
    public function testConstructorConvertsCriterionData(): void
    {
        $data = [
            'id' => 11,
            'criterion_data' => [
                'criterion_1' => ['points' => 5.0],
            ],
        ];

        $assessment = new RubricAssessment($data);

        $this->assertArrayHasKey('criterion_1', $assessment->criterionData);
        $this->assertEquals(5.0, $assessment->criterionData['criterion_1']['points']);
    }
}
