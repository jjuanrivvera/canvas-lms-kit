<?php

namespace CanvasLMS\Tests\Api\Courses;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Pages\Page;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Api\Quizzes\Quiz;
use CanvasLMS\Api\Files\File;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\ExternalTools\ExternalTool;
use CanvasLMS\Api\Tabs\Tab;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\CanvasApiException;
use GuzzleHttp\Psr7\Response;

/**
 * Test Course class relationship methods
 */
class CourseRelationshipTest extends TestCase
{
    private MockObject $httpClient;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        Course::setApiClient($this->httpClient);
        
        // Create a course instance with ID
        $this->course = new Course(['id' => 123]);
    }


    public function testAssignmentsReturnsArrayOfAssignments(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Assignment 1'],
            ['id' => 2, 'name' => 'Assignment 2']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $assignments = $this->course->assignments();

        $this->assertCount(2, $assignments);
        $this->assertInstanceOf(Assignment::class, $assignments[0]);
        $this->assertEquals(1, $assignments[0]->id);
    }

    public function testAssignmentsThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to fetch assignments');

        $course->assignments();
    }

    public function testModulesReturnsArrayOfModules(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Module 1'],
            ['id' => 2, 'name' => 'Module 2']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/modules', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $modules = $this->course->modules();

        $this->assertCount(2, $modules);
        $this->assertInstanceOf(Module::class, $modules[0]);
        $this->assertEquals(1, $modules[0]->id);
    }


    public function testPagesReturnsArrayOfPages(): void
    {
        $responseData = [
            ['url' => 'page-1', 'title' => 'Page 1'],
            ['url' => 'page-2', 'title' => 'Page 2']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/pages', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $pages = $this->course->pages();

        $this->assertCount(2, $pages);
        $this->assertInstanceOf(Page::class, $pages[0]);
        $this->assertEquals('page-1', $pages[0]->url);
    }

    public function testSectionsReturnsArrayOfSections(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Section A'],
            ['id' => 2, 'name' => 'Section B']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/sections', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $sections = $this->course->sections();

        $this->assertCount(2, $sections);
        $this->assertInstanceOf(Section::class, $sections[0]);
        $this->assertEquals(1, $sections[0]->id);
    }

    public function testDiscussionTopicsReturnsArrayOfDiscussionTopics(): void
    {
        $responseData = [
            ['id' => 1, 'title' => 'Topic 1'],
            ['id' => 2, 'title' => 'Topic 2']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/discussion_topics', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $discussionTopics = $this->course->discussionTopics();

        $this->assertCount(2, $discussionTopics);
        $this->assertInstanceOf(DiscussionTopic::class, $discussionTopics[0]);
        $this->assertEquals(1, $discussionTopics[0]->id);
    }

    public function testQuizzesReturnsArrayOfQuizzes(): void
    {
        $responseData = [
            ['id' => 1, 'title' => 'Quiz 1'],
            ['id' => 2, 'title' => 'Quiz 2']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/quizzes', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $quizzes = $this->course->quizzes();

        $this->assertCount(2, $quizzes);
        $this->assertInstanceOf(Quiz::class, $quizzes[0]);
        $this->assertEquals(1, $quizzes[0]->id);
    }

    public function testFilesReturnsArrayOfFiles(): void
    {
        $responseData = [
            ['id' => 1, 'display_name' => 'file1.pdf', 'filename' => 'file1.pdf'],
            ['id' => 2, 'display_name' => 'file2.doc', 'filename' => 'file2.doc']
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/files', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $files = $this->course->files();

        $this->assertCount(2, $files);
        $this->assertInstanceOf(File::class, $files[0]);
        $this->assertEquals(1, $files[0]->id);
    }

    public function testRubricsReturnsArrayOfRubrics(): void
    {
        $responseData = [
            ['id' => 1, 'title' => 'Rubric 1'],
            ['id' => 2, 'title' => 'Rubric 2']
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/rubrics', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $rubrics = $this->course->rubrics();

        $this->assertCount(2, $rubrics);
        $this->assertInstanceOf(Rubric::class, $rubrics[0]);
        $this->assertEquals(1, $rubrics[0]->id);
    }

    public function testExternalToolsReturnsArrayOfExternalTools(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Tool 1'],
            ['id' => 2, 'name' => 'Tool 2']
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($responseData);

        $this->httpClient
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/external_tools', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $externalTools = $this->course->externalTools();

        $this->assertCount(2, $externalTools);
        $this->assertInstanceOf(ExternalTool::class, $externalTools[0]);
        $this->assertEquals(1, $externalTools[0]->id);
    }

    public function testTabsReturnsArrayOfTabs(): void
    {
        $responseData = [
            ['id' => 'home', 'label' => 'Home'],
            ['id' => 'assignments', 'label' => 'Assignments']
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/tabs', ['query' => []])
            ->willReturn(new Response(200, [], json_encode($responseData)));

        $tabs = $this->course->tabs();

        $this->assertCount(2, $tabs);
        $this->assertInstanceOf(Tab::class, $tabs[0]);
        $this->assertEquals('home', $tabs[0]->id);
    }

    public function testRelationshipMethodsAcceptParameters(): void
    {
        $params = ['per_page' => 50, 'include[]' => ['total_scores']];
        
        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments', ['query' => $params])
            ->willReturn(new Response(200, [], json_encode([])));

        $assignments = $this->course->assignments($params);
        $this->assertIsArray($assignments);
    }
}