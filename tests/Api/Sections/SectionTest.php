<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Sections;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Dto\Sections\CreateSectionDTO;
use CanvasLMS\Dto\Sections\UpdateSectionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class SectionTest extends TestCase
{
    private HttpClientInterface $mockClient;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(HttpClientInterface::class);
        Section::setApiClient($this->mockClient);

        // Set up course context
        $this->course = new Course(['id' => 123, 'name' => 'Test Course']);
        Section::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up static properties
        $reflection = new \ReflectionClass(Section::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null);
    }

    public function testCheckCourse(): void
    {
        // With course context set
        $this->assertTrue(Section::checkCourse());

        // Clean course context and test exception
        $reflection = new \ReflectionClass(Section::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required');
        Section::checkCourse();
    }

    public function testCheckCourseWithInvalidCourse(): void
    {
        // Set course without ID
        $invalidCourse = new Course(['name' => 'Test Course']);
        Section::setCourse($invalidCourse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required');
        Section::checkCourse();
    }

    public function testFindWithCourseContext(): void
    {
        $sectionData = [
            'id' => 456,
            'name' => 'Section A',
            'course_id' => 123,
            'sis_section_id' => 's34643',
            'integration_id' => '3452342345',
            'start_at' => '2012-06-01T00:00:00-06:00',
            'end_at' => null,
            'restrict_enrollments_to_section_dates' => true,
            'total_students' => 25,
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/sections/456', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($sectionData)));

        $section = Section::find(456);

        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals(456, $section->id);
        $this->assertEquals('Section A', $section->name);
        $this->assertEquals(123, $section->courseId);
        $this->assertEquals('s34643', $section->sisSectionId);
        $this->assertEquals(25, $section->totalStudents);
    }

    public function testFindWithoutCourseContext(): void
    {
        // Remove course context
        $reflection = new \ReflectionClass(Section::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null);

        $sectionData = [
            'id' => 456,
            'name' => 'Section A',
            'course_id' => 789,
            'sis_section_id' => 's34643',
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('sections/456', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($sectionData)));

        $section = Section::find(456);

        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals(456, $section->id);
        $this->assertEquals(789, $section->courseId);
    }

    public function testFindWithIncludes(): void
    {
        $sectionData = [
            'id' => 456,
            'name' => 'Section A',
            'course_id' => 123,
            'total_students' => 25,
            'students' => [
                ['id' => 1, 'name' => 'Student 1'],
                ['id' => 2, 'name' => 'Student 2'],
            ],
        ];

        $params = ['include' => ['students', 'total_students']];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/sections/456', ['query' => $params])
            ->willReturn(new Response(200, [], json_encode($sectionData)));

        $section = Section::find(456, $params);

        $this->assertIsArray($section->students);
        $this->assertCount(2, $section->students);
        $this->assertEquals(25, $section->totalStudents);
    }

    public function testGet(): void
    {
        $sectionsData = [
            ['id' => 1, 'name' => 'Section A', 'course_id' => 123],
            ['id' => 2, 'name' => 'Section B', 'course_id' => 123],
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/sections', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($sectionsData)));

        $sections = Section::get();

        $this->assertIsArray($sections);
        $this->assertCount(2, $sections);
        $this->assertInstanceOf(Section::class, $sections[0]);
        $this->assertEquals('Section A', $sections[0]->name);
        $this->assertEquals(123, $sections[0]->courseId);
    }

    public function testGetWithSearchTerm(): void
    {
        $sectionsData = [
            ['id' => 1, 'name' => 'Lab Section', 'course_id' => 123],
        ];

        $params = ['search_term' => 'Lab'];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/sections', ['query' => $params])
            ->willReturn(new Response(200, [], json_encode($sectionsData)));

        $sections = Section::get($params);

        $this->assertCount(1, $sections);
        $this->assertEquals('Lab Section', $sections[0]->name);
    }

    public function testGetWithInvalidSearchTerm(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('search_term must be at least 2 characters');

        Section::get(['search_term' => 'a']);
    }

    public function testGetWithoutCourseContext(): void
    {
        // Remove course context
        $reflection = new \ReflectionClass(Section::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required');

        Section::get();
    }

    public function testPaginate(): void
    {
        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        $paginationResult = $this->createMock(PaginationResult::class);

        $paginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([['id' => 1, 'name' => 'Section 1']]);

        $paginatedResponse->expects($this->once())
            ->method('toPaginationResult')
            ->willReturn($paginationResult);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/sections', ['query' => []])
            ->willReturn($paginatedResponse);

        $result = Section::paginate();

        $this->assertSame($paginationResult, $result);
    }

    public function testCreate(): void
    {
        $createData = [
            'name' => 'New Section',
            'sis_section_id' => 'NEW-001',
            'start_at' => '2024-01-15T08:00:00Z',
            'end_at' => '2024-05-15T17:00:00Z',
            'restrict_enrollments_to_section_dates' => true,
            'enable_sis_reactivation' => true,
        ];

        $responseData = array_merge($createData, [
            'id' => 789,
            'course_id' => 123,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $dto = new CreateSectionDTO($createData);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('courses/123/sections', ['multipart' => $dto->toApiArray()])
            ->willReturn(new Response(201, [], json_encode($responseData)));

        $section = Section::create($createData);

        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals(789, $section->id);
        $this->assertEquals('New Section', $section->name);
        $this->assertEquals(123, $section->courseId);
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateSectionDTO(['name' => 'DTO Section']);
        $dto->sis_section_id = 'DTO-001';
        $dto->enable_sis_reactivation = true;

        $responseData = [
            'id' => 890,
            'name' => 'DTO Section',
            'course_id' => 123,
            'sis_section_id' => 'DTO-001',
        ];

        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn(new Response(201, [], json_encode($responseData)));

        $section = Section::create($dto);

        $this->assertEquals(890, $section->id);
        $this->assertEquals('DTO Section', $section->name);
    }

    public function testUpdate(): void
    {
        $updateData = [
            'name' => 'Updated Section',
            'end_at' => '2024-06-30T17:00:00Z',
            'override_sis_stickiness' => false,
        ];

        $responseData = [
            'id' => 456,
            'name' => 'Updated Section',
            'course_id' => 123,
            'end_at' => '2024-06-30T17:00:00Z',
        ];

        $dto = new UpdateSectionDTO($updateData);

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('sections/456', ['multipart' => $dto->toApiArray()])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $section = Section::update(456, $updateData);

        $this->assertEquals('Updated Section', $section->name);
        $this->assertEquals('2024-06-30T17:00:00Z', $section->endAt);
    }

    public function testCrossList(): void
    {
        $responseData = [
            'id' => 456,
            'name' => 'Section A',
            'course_id' => 999,
            'nonxlist_course_id' => 123,
        ];

        $expectedParams = [
            [
                'name' => 'override_sis_stickiness',
                'contents' => 'true',
            ],
        ];

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('sections/456/crosslist/999', ['multipart' => $expectedParams])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $section = Section::crossList(456, 999);

        $this->assertEquals(999, $section->courseId);
        $this->assertEquals(123, $section->nonxlistCourseId);
    }

    public function testDeCrossList(): void
    {
        $responseData = [
            'id' => 456,
            'name' => 'Section A',
            'course_id' => 123,
            'nonxlist_course_id' => null,
        ];

        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('sections/456/crosslist', ['query' => ['override_sis_stickiness' => true]])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $section = Section::deCrossList(456);

        $this->assertEquals(123, $section->courseId);
        $this->assertNull($section->nonxlistCourseId);
    }

    public function testSaveCreate(): void
    {
        $section = new Section([]);
        $section->name = 'New Section';
        $section->sisSectionId = 'NEW-002';

        $responseData = [
            'id' => 1001,
            'name' => 'New Section',
            'course_id' => 123,
            'sis_section_id' => 'NEW-002',
        ];

        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn(new Response(201, [], json_encode($responseData)));

        $result = $section->save();

        $this->assertInstanceOf(Section::class, $result);
        $this->assertEquals(1001, $section->id);
    }

    public function testSaveUpdate(): void
    {
        $section = new Section(['id' => 456, 'name' => 'Existing Section']);
        $section->name = 'Updated Name';

        $responseData = [
            'id' => 456,
            'name' => 'Updated Name',
            'course_id' => 123,
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $result = $section->save();

        $this->assertInstanceOf(Section::class, $result);
        $this->assertEquals('Updated Name', $section->name);
    }

    public function testDelete(): void
    {
        $section = new Section(['id' => 456]);

        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('sections/456')
            ->willReturn(new Response(204));

        $result = $section->delete();

        $this->assertInstanceOf(Section::class, $result);
    }

    public function testDeleteWithoutId(): void
    {
        $section = new Section([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Section ID is required for deletion');

        $section->delete();
    }

    public function testToDtoArray(): void
    {
        $section = new Section([
            'id' => 456,
            'name' => 'Section A',
            'sis_section_id' => 's34643',
            'integration_id' => '3452342345',
            'start_at' => '2024-01-15T08:00:00Z',
            'end_at' => '2024-05-15T17:00:00Z',
            'restrict_enrollments_to_section_dates' => true,
            'course_id' => 123,
            'total_students' => 25,
        ]);

        $dtoArray = $section->toDtoArray();

        $expectedArray = [
            'name' => 'Section A',
            'sis_section_id' => 's34643',
            'integration_id' => '3452342345',
            'start_at' => '2024-01-15T08:00:00Z',
            'end_at' => '2024-05-15T17:00:00Z',
            'restrict_enrollments_to_section_dates' => true,
        ];

        $this->assertEquals($expectedArray, $dtoArray);
        $this->assertArrayNotHasKey('id', $dtoArray);
        $this->assertArrayNotHasKey('course_id', $dtoArray);
        $this->assertArrayNotHasKey('total_students', $dtoArray);
    }

    public function testAll(): void
    {
        $firstPageData = [
            ['id' => 1, 'name' => 'Section A', 'course_id' => 123],
            ['id' => 2, 'name' => 'Section B', 'course_id' => 123],
        ];
        $secondPageData = [
            ['id' => 3, 'name' => 'Section C', 'course_id' => 123],
        ];

        $allData = array_merge($firstPageData, $secondPageData);

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($allData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/sections', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $sections = Section::all();

        $this->assertIsArray($sections);
        $this->assertCount(3, $sections);
        $this->assertInstanceOf(Section::class, $sections[0]);
        $this->assertInstanceOf(Section::class, $sections[2]);
        $this->assertEquals('Section A', $sections[0]->name);
        $this->assertEquals('Section C', $sections[2]->name);
    }
}
