<?php

declare(strict_types=1);

namespace Tests\Api\ContentMigrations;

use CanvasLMS\Api\ContentMigrations\ContentMigration;
use CanvasLMS\Api\ContentMigrations\MigrationIssue;
use CanvasLMS\Dto\ContentMigrations\UpdateMigrationIssueDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MigrationIssueTest extends TestCase
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

        MigrationIssue::setApiClient($this->mockClient);
    }

    public function testFindInMigration(): void
    {
        $issueData = [
            'id' => 123,
            'description' => 'Quiz questions could not be imported',
            'workflow_state' => 'active',
            'issue_type' => 'warning',
            'content_migration_url' => 'https://canvas.test/api/v1/courses/1/content_migrations/456'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($issueData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('get')
            ->with('courses/1/content_migrations/456/migration_issues/123')
            ->willReturn($this->mockResponse);

        $issue = MigrationIssue::findInMigration('courses', 1, 456, 123);

        $this->assertEquals(123, $issue->getId());
        $this->assertEquals('Quiz questions could not be imported', $issue->getDescription());
        $this->assertEquals('active', $issue->getWorkflowState());
        $this->assertEquals('warning', $issue->getIssueType());
    }

    public function testFindThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        MigrationIssue::find(123);
    }

    public function testGetThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        MigrationIssue::get();
    }

    public function testGetInMigration(): void
    {
        $issuesData = [
            ['id' => 1, 'issue_type' => 'warning', 'workflow_state' => 'active'],
            ['id' => 2, 'issue_type' => 'error', 'workflow_state' => 'resolved']
        ];

        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->method('all')->willReturn($issuesData);
        
        $this->mockClient->method('getPaginated')
            ->with('courses/1/content_migrations/456/migration_issues', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $issues = MigrationIssue::fetchAllInMigration('courses', 1, 456);

        $this->assertCount(2, $issues);
        $this->assertInstanceOf(MigrationIssue::class, $issues[0]);
    }

    public function testUpdate(): void
    {
        $updatedData = [
            'id' => 123,
            'workflow_state' => 'resolved'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($updatedData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('put')->willReturn($this->mockResponse);

        $issue = MigrationIssue::update('courses', 1, 456, 123, ['workflow_state' => 'resolved']);

        $this->assertEquals('resolved', $issue->getWorkflowState());
    }

    public function testResolve(): void
    {
        $issue = new MigrationIssue([
            'id' => 123,
            'workflow_state' => 'active',
            'content_migration_url' => 'https://canvas.test/api/v1/courses/1/content_migrations/456'
        ]);

        $resolvedData = [
            'id' => 123,
            'workflow_state' => 'resolved',
            'content_migration_url' => $issue->getContentMigrationUrl()
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($resolvedData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('put')
            ->willReturn($this->mockResponse);

        $result = $issue->resolve();

        $this->assertTrue($result);
        $this->assertEquals('resolved', $issue->getWorkflowState());
    }

    public function testReactivate(): void
    {
        $issue = new MigrationIssue([
            'id' => 123,
            'workflow_state' => 'resolved',
            'content_migration_url' => 'https://canvas.test/api/v1/courses/1/content_migrations/456'
        ]);

        $activeData = [
            'id' => 123,
            'workflow_state' => 'active',
            'content_migration_url' => $issue->getContentMigrationUrl()
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($activeData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockClient->method('put')
            ->willReturn($this->mockResponse);

        $result = $issue->reactivate();

        $this->assertTrue($result);
        $this->assertEquals('active', $issue->getWorkflowState());
    }

    public function testResolveWithoutId(): void
    {
        $issue = new MigrationIssue([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Issue ID is required');
        $issue->resolve();
    }

    public function testResolveWithInvalidUrl(): void
    {
        $issue = new MigrationIssue([
            'id' => 123,
            'content_migration_url' => 'invalid-url'
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Unable to determine context from content migration URL');
        $issue->resolve();
    }

    public function testSetContentMigration(): void
    {
        $migration = new ContentMigration(['id' => 456]);
        MigrationIssue::setContentMigration($migration);
        // These methods are deprecated and now return false/no-op
        $this->assertFalse(MigrationIssue::checkContentMigration());
    }

    public function testWorkflowStateHelpers(): void
    {
        $issue = new MigrationIssue(['workflow_state' => 'active']);
        $this->assertTrue($issue->isActive());
        $this->assertFalse($issue->isResolved());

        $issue->setWorkflowState('resolved');
        $this->assertFalse($issue->isActive());
        $this->assertTrue($issue->isResolved());
    }

    public function testIssueTypeHelpers(): void
    {
        $issue = new MigrationIssue(['issue_type' => 'error']);
        $this->assertTrue($issue->isError());
        $this->assertFalse($issue->isWarning());
        $this->assertFalse($issue->isTodo());

        $issue->setIssueType('warning');
        $this->assertFalse($issue->isError());
        $this->assertTrue($issue->isWarning());
        $this->assertFalse($issue->isTodo());

        $issue->setIssueType('todo');
        $this->assertFalse($issue->isError());
        $this->assertFalse($issue->isWarning());
        $this->assertTrue($issue->isTodo());
    }

    public function testGettersAndSetters(): void
    {
        $issue = new MigrationIssue([]);

        $issue->setId(123);
        $this->assertEquals(123, $issue->getId());

        $issue->setDescription('Test description');
        $this->assertEquals('Test description', $issue->getDescription());

        $issue->setFixIssueHtmlUrl('https://canvas.test/fix');
        $this->assertEquals('https://canvas.test/fix', $issue->getFixIssueHtmlUrl());

        $issue->setErrorReportHtmlUrl('https://canvas.test/error');
        $this->assertEquals('https://canvas.test/error', $issue->getErrorReportHtmlUrl());

        $issue->setErrorMessage('Internal error');
        $this->assertEquals('Internal error', $issue->getErrorMessage());

        $now = new \DateTime();
        $issue->setCreatedAt($now);
        $this->assertEquals($now, $issue->getCreatedAt());

        $issue->setUpdatedAt($now);
        $this->assertEquals($now, $issue->getUpdatedAt());
    }
}