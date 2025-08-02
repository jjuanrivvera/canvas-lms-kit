<?php

namespace Tests\Api\Rubrics;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Rubrics\CreateRubricDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\RubricCriterion;
use CanvasLMS\Config;

class RubricTest extends TestCase
{
    /**
     * @var Rubric
     */
    private Rubric $rubric;

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
        Rubric::setApiClient($this->httpClientMock);
        
        // Set default account ID in config
        Config::setAccountId(1);
        
        // Reset course context
        Rubric::setCourse(null);
        
        $this->rubric = new Rubric([]);
    }

    /**
     * Rubric data provider
     * @return array
     */
    public static function rubricDataProvider(): array
    {
        return [
            'basic_rubric' => [
                [
                    'title' => 'Essay Rubric',
                    'freeFormCriterionComments' => true,
                    'hideScoreTotal' => false,
                    'criteria' => [
                        [
                            'description' => 'Content',
                            'points' => 20,
                            'ratings' => [
                                ['description' => 'Excellent', 'points' => 20],
                                ['description' => 'Good', 'points' => 15],
                                ['description' => 'Satisfactory', 'points' => 10]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 123,
                    'title' => 'Essay Rubric',
                    'context_id' => 456,
                    'context_type' => 'Course',
                    'points_possible' => 20.0,
                    'reusable' => true,
                    'read_only' => false,
                    'free_form_criterion_comments' => true,
                    'hide_score_total' => false,
                    'data' => [
                        [
                            'id' => 'criterion_1',
                            'description' => 'Content',
                            'points' => 20,
                            'ratings' => [
                                ['id' => 'rating_1', 'description' => 'Excellent', 'points' => 20],
                                ['id' => 'rating_2', 'description' => 'Good', 'points' => 15],
                                ['id' => 'rating_3', 'description' => 'Satisfactory', 'points' => 10]
                            ]
                        ]
                    ]
                ]
            ],
            'rubric_with_association' => [
                [
                    'title' => 'Project Rubric',
                    'criteria' => [
                        [
                            'description' => 'Research',
                            'points' => 30
                        ]
                    ],
                    'association' => [
                        'association_id' => 789,
                        'association_type' => 'Assignment',
                        'use_for_grading' => true
                    ]
                ],
                [
                    'rubric' => [
                        'id' => 124,
                        'title' => 'Project Rubric',
                        'points_possible' => 30.0,
                        'data' => [
                            [
                                'id' => 'criterion_2',
                                'description' => 'Research',
                                'points' => 30
                            ]
                        ]
                    ],
                    'rubric_association' => [
                        'id' => 999,
                        'rubric_id' => 124,
                        'association_id' => 789,
                        'association_type' => 'Assignment',
                        'use_for_grading' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * Test the create rubric method with course context
     * @dataProvider rubricDataProvider
     */
    public function testCreateRubricInCourse(array $rubricData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/456/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricDTO();
        $dto->title = $rubricData['title'];
        $dto->criteria = $rubricData['criteria'] ?? null;
        $dto->freeFormCriterionComments = $rubricData['freeFormCriterionComments'] ?? null;
        $dto->hideScoreTotal = $rubricData['hideScoreTotal'] ?? null;
        $dto->association = $rubricData['association'] ?? null;

        // Set course context
        $course = new Course(['id' => 456]);
        Rubric::setCourse($course);
        
        $rubric = Rubric::create($dto);

        // Check if response has nested format
        if (isset($expectedResult['rubric'])) {
            $this->assertEquals($expectedResult['rubric']['id'], $rubric->id);
            $this->assertEquals($expectedResult['rubric']['title'], $rubric->title);
            $this->assertInstanceOf(RubricAssociation::class, $rubric->association);
            $this->assertEquals($expectedResult['rubric_association']['id'], $rubric->association->id);
        } else {
            $this->assertEquals($expectedResult['id'], $rubric->id);
            $this->assertEquals($expectedResult['title'], $rubric->title);
        }
    }

    /**
     * Test the create rubric method with account context
     * Note: Account-scoped operations should now use Account class
     * This test is for backward reference only
     */
    public function testCreateRubricInAccountShouldThrowException(): void
    {
        // Reset course context
        Rubric::setCourse(null);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required');

        $dto = new CreateRubricDTO();
        $dto->title = 'Account Rubric';

        Rubric::create($dto);
    }

    /**
     * Test create without course context throws exception
     */
    public function testCreateWithoutCourseContextThrowsException(): void
    {
        Rubric::setCourse(null);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required');

        $dto = new CreateRubricDTO();
        $dto->title = 'Test Rubric';

        Rubric::create($dto);
    }

    /**
     * Test create without context throws exception
     */
    public function testCreateWithoutContextThrowsException(): void
    {
        Rubric::setCourse(null);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Course context is required for course-scoped rubric operations");

        $dto = new CreateRubricDTO();
        Rubric::create($dto);
    }

    /**
     * Test create rubric with array input
     */
    public function testCreateRubricWithArrayInput(): void
    {
        $rubricData = [
            'title' => 'Array Input Rubric',
            'criteria' => [
                [
                    'description' => 'Grammar',
                    'points' => 5,
                    'id' => 'grammar_1',
                    'ratings' => [
                        ['description' => 'Excellent', 'points' => 5],
                        ['description' => 'Good', 'points' => 3],
                        ['description' => 'Poor', 'points' => 1]
                    ]
                ]
            ],
            'freeFormCriterionComments' => true,
            'hideScoreTotal' => false
        ];

        $expectedResult = [
            'id' => 128,
            'title' => 'Array Input Rubric',
            'points_possible' => 5.0,
            'free_form_criterion_comments' => true,
            'hide_score_total' => false
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/789/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        // Set course context
        $course = new Course(['id' => 789]);
        Rubric::setCourse($course);
        
        $rubric = Rubric::create($rubricData);

        $this->assertEquals(128, $rubric->id);
        $this->assertEquals('Array Input Rubric', $rubric->title);
        $this->assertEquals(5.0, $rubric->pointsPossible);
        $this->assertTrue($rubric->freeFormCriterionComments);
        $this->assertFalse($rubric->hideScoreTotal);
    }

    /**
     * Test find rubric
     */
    public function testFindRubric(): void
    {
        $expectedResult = [
            'id' => 127,
            'title' => 'Found Rubric',
            'data' => [
                ['id' => 'criterion_1', 'description' => 'Test', 'points' => 5]
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                'courses/100/rubrics/127',
                ['query' => ['include' => ['assessments']]]
            )
            ->willReturn($response);

        // Set course context
        $course = new Course(['id' => 100]);
        Rubric::setCourse($course);
        
        $rubric = Rubric::find(127, ['include' => ['assessments']]);

        $this->assertEquals(127, $rubric->id);
        $this->assertEquals('Found Rubric', $rubric->title);
        $this->assertIsArray($rubric->data);
        $this->assertCount(1, $rubric->data);
        $this->assertInstanceOf(RubricCriterion::class, $rubric->data[0]);
    }

    /**
     * Test update rubric
     */
    public function testUpdateRubric(): void
    {
        $expectedResult = [
            'rubric' => [
                'id' => 128,
                'title' => 'Updated Rubric'
            ],
            'rubric_association' => [
                'id' => 1001,
                'rubric_id' => 128
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/200/rubrics/128',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new UpdateRubricDTO();
        $dto->title = 'Updated Rubric';

        // Set course context
        $course = new Course(['id' => 200]);
        Rubric::setCourse($course);
        
        $rubric = Rubric::update(128, $dto);

        $this->assertEquals(128, $rubric->id);
        $this->assertEquals('Updated Rubric', $rubric->title);
        $this->assertInstanceOf(RubricAssociation::class, $rubric->association);
    }

    /**
     * Test update rubric with array input
     */
    public function testUpdateRubricWithArrayInput(): void
    {
        $updateData = [
            'title' => 'Updated Array Rubric',
            'freeFormCriterionComments' => false,
            'hideScoreTotal' => true
        ];

        $expectedResult = [
            'rubric' => [
                'id' => 129,
                'title' => 'Updated Array Rubric',
                'free_form_criterion_comments' => false,
                'hide_score_total' => true
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/201/rubrics/129',
                $this->isType('array')
            )
            ->willReturn($response);

        // Set course context
        $course = new Course(['id' => 201]);
        Rubric::setCourse($course);
        
        $rubric = Rubric::update(129, $updateData);

        $this->assertEquals(129, $rubric->id);
        $this->assertEquals('Updated Array Rubric', $rubric->title);
        $this->assertFalse($rubric->freeFormCriterionComments);
        $this->assertTrue($rubric->hideScoreTotal);
    }

    /**
     * Test delete rubric
     */
    public function testDeleteRubric(): void
    {
        // Set course context
        $course = new Course(['id' => 300]);
        Rubric::setCourse($course);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/300/rubrics/129')
            ->willReturn(new Response(204));

        $rubric = new Rubric(['id' => 129]);

        $result = $rubric->delete();

        $this->assertTrue($result);
    }

    /**
     * Test delete without ID throws exception
     */
    public function testDeleteWithoutIdThrowsException(): void
    {
        $rubric = new Rubric([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Cannot delete rubric without ID");

        $rubric->delete();
    }

    /**
     * Test delete without context throws exception
     */
    public function testDeleteWithoutContextThrowsException(): void
    {
        Rubric::setCourse(null);
        
        $rubric = new Rubric(['id' => 130]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Course context is required for course-scoped rubric operations");

        $rubric->delete();
    }

    /**
     * Test save existing rubric
     */
    public function testSaveExistingRubric(): void
    {
        // Set course context
        $course = new Course(['id' => 400]);
        Rubric::setCourse($course);
        
        $expectedResult = ['id' => 131, 'title' => 'Saved Rubric'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'courses/400/rubrics/131',
                $this->isType('array')
            )
            ->willReturn($response);

        $rubric = new Rubric([
            'id' => 131,
            'title' => 'Saved Rubric'
        ]);

        $savedRubric = $rubric->save();

        $this->assertEquals(131, $savedRubric->id);
        $this->assertEquals('Saved Rubric', $savedRubric->title);
    }

    /**
     * Test save new rubric
     */
    public function testSaveNewRubric(): void
    {
        // Set course context
        $course = new Course(['id' => 500]);
        Rubric::setCourse($course);
        
        $expectedResult = ['id' => 132, 'title' => 'New Rubric'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/500/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        $rubric = new Rubric(['title' => 'New Rubric']);
        $savedRubric = $rubric->save();

        $this->assertEquals(132, $savedRubric->id);
        $this->assertEquals('New Rubric', $savedRubric->title);
    }

    /**
     * Test fetchAll rubrics (test method existence and context handling)
     */
    public function testFetchAllRubrics(): void
    {
        // Testing that fetchAll method works with context parameters
        // Due to pagination complexity, we'll just test that the method exists and handles context
        $this->assertTrue(method_exists(Rubric::class, 'fetchAll'));
        
        // Reset account ID to force exception
        Config::setAccountId(0);
        
        // Test that it throws exception without context
        Rubric::setCourse(null);
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Course context is required for course-scoped rubric operations");
        Rubric::fetchAll([]);
    }

    /**
     * Test get used locations
     */
    public function testGetUsedLocations(): void
    {
        // Set course context
        $course = new Course(['id' => 600]);
        Rubric::setCourse($course);
        
        $expectedResult = [
            'assignments' => [
                ['id' => 1, 'name' => 'Essay Assignment']
            ],
            'courses' => [
                ['id' => 100, 'name' => 'English 101']
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/600/rubrics/135/used_locations')
            ->willReturn($response);

        $rubric = new Rubric(['id' => 135]);

        $locations = $rubric->getUsedLocations();

        $this->assertArrayHasKey('assignments', $locations);
        $this->assertArrayHasKey('courses', $locations);
        $this->assertCount(1, $locations['assignments']);
    }

    /**
     * Test upload CSV
     */
    public function testUploadCsv(): void
    {
        $expectedResult = ['import_id' => 1001, 'status' => 'pending'];
        $response = new Response(200, [], json_encode($expectedResult));

        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'rubric_test');
        file_put_contents($tempFile, 'test,csv,content');

        // Set course context
        $course = new Course(['id' => 700]);
        Rubric::setCourse($course);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'courses/700/rubrics/upload',
                $this->callback(function ($multipart) {
                    return is_array($multipart) &&
                           isset($multipart[0]['name']) &&
                           $multipart[0]['name'] === 'attachment' &&
                           isset($multipart[0]['contents']) &&
                           isset($multipart[0]['filename']);
                })
            )
            ->willReturn($response);

        $result = Rubric::uploadCsv($tempFile);

        $this->assertEquals(1001, $result['import_id']);
        $this->assertEquals('pending', $result['status']);

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test upload CSV with non-existent file throws exception
     */
    public function testUploadCsvWithNonExistentFileThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("CSV file not found");

        // Set course context first
        $course = new Course(['id' => 123]);
        Rubric::setCourse($course);
        
        Rubric::uploadCsv('/non/existent/file.csv');
    }

    /**
     * Test get upload template
     */
    public function testGetUploadTemplate(): void
    {
        $csvContent = "title,description,points\nRubric 1,Description 1,10";
        $response = new Response(200, [], $csvContent);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('rubrics/upload_template')
            ->willReturn($response);

        $template = Rubric::getUploadTemplate();

        $this->assertEquals($csvContent, $template);
    }

    /**
     * Test get upload status
     */
    public function testGetUploadStatus(): void
    {
        $expectedResult = [
            'import_id' => 1002,
            'status' => 'completed',
            'created_count' => 5
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/700/rubrics/upload/1002')
            ->willReturn($response);

        // Set course context
        $course = new Course(['id' => 700]);
        Rubric::setCourse($course);
        
        $status = Rubric::getUploadStatus(1002);

        $this->assertEquals('completed', $status['status']);
        $this->assertEquals(5, $status['created_count']);
    }

    /**
     * Test get upload status without import ID
     */
    public function testGetUploadStatusWithoutImportId(): void
    {
        $expectedResult = ['import_id' => 1003, 'status' => 'processing'];
        $response = new Response(200, [], json_encode($expectedResult));

        // Set course context
        $course = new Course(['id' => 800]);
        Rubric::setCourse($course);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/800/rubrics/upload')
            ->willReturn($response);

        $status = Rubric::getUploadStatus(null);

        $this->assertEquals('processing', $status['status']);
    }
}