<?php

namespace Tests\Api\Rubrics;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
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

        $rubric = Rubric::create($dto, ['course_id' => 456]);

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
     */
    public function testCreateRubricInAccount(): void
    {
        $expectedResult = [
            'id' => 125,
            'title' => 'Account Rubric',
            'context_id' => 1,
            'context_type' => 'Account',
            'points_possible' => 10.0
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricDTO();
        $dto->title = 'Account Rubric';

        $rubric = Rubric::create($dto, ['account_id' => 1]);

        $this->assertEquals(125, $rubric->id);
        $this->assertEquals('Account Rubric', $rubric->title);
        $this->assertEquals(1, $rubric->contextId);
        $this->assertEquals('Account', $rubric->contextType);
    }

    /**
     * Test create with default account from config
     */
    public function testCreateWithDefaultAccountFromConfig(): void
    {
        Config::setAccountId(5);

        $expectedResult = ['id' => 126, 'title' => 'Default Account Rubric'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'accounts/5/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        $dto = new CreateRubricDTO();
        $dto->title = 'Default Account Rubric';

        $rubric = Rubric::create($dto);

        $this->assertEquals(126, $rubric->id);
    }

    /**
     * Test create without context throws exception
     */
    public function testCreateWithoutContextThrowsException(): void
    {
        Config::setAccountId(0);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Either course_id or account_id must be provided");

        $dto = new CreateRubricDTO();
        Rubric::create($dto);
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

        $rubric = Rubric::find(127, ['course_id' => 100, 'include' => ['assessments']]);

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

        $rubric = Rubric::update(128, $dto, ['course_id' => 200]);

        $this->assertEquals(128, $rubric->id);
        $this->assertEquals('Updated Rubric', $rubric->title);
        $this->assertInstanceOf(RubricAssociation::class, $rubric->association);
    }

    /**
     * Test delete rubric
     */
    public function testDeleteRubric(): void
    {
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/300/rubrics/129')
            ->willReturn(new Response(204));

        $rubric = new Rubric([
            'id' => 129,
            'context_id' => 300,
            'context_type' => 'Course'
        ]);

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
        $rubric = new Rubric(['id' => 130]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Cannot determine context for rubric deletion");

        $rubric->delete();
    }

    /**
     * Test save existing rubric
     */
    public function testSaveExistingRubric(): void
    {
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
            'title' => 'Saved Rubric',
            'context_id' => 400,
            'context_type' => 'Course'
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
        $expectedResult = ['id' => 132, 'title' => 'New Rubric'];
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'accounts/2/rubrics',
                $this->isType('array')
            )
            ->willReturn($response);

        $rubric = new Rubric(['title' => 'New Rubric']);
        $savedRubric = $rubric->save(['account_id' => 2]);

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
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage("Either course_id or account_id must be provided");
        Rubric::fetchAll([]);
    }

    /**
     * Test get used locations
     */
    public function testGetUsedLocations(): void
    {
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

        $rubric = new Rubric([
            'id' => 135,
            'context_id' => 600,
            'context_type' => 'Course'
        ]);

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

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'accounts/3/rubrics/upload',
                $this->callback(function ($multipart) {
                    return is_array($multipart) &&
                           isset($multipart[0]['name']) &&
                           $multipart[0]['name'] === 'attachment' &&
                           isset($multipart[0]['contents']) &&
                           isset($multipart[0]['filename']);
                })
            )
            ->willReturn($response);

        $result = Rubric::uploadCsv($tempFile, ['account_id' => 3]);

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

        Rubric::uploadCsv('/non/existent/file.csv', ['account_id' => 1]);
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

        $status = Rubric::getUploadStatus(1002, ['course_id' => 700]);

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

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/4/rubrics/upload')
            ->willReturn($response);

        $status = Rubric::getUploadStatus(null, ['account_id' => 4]);

        $this->assertEquals('processing', $status['status']);
    }
}