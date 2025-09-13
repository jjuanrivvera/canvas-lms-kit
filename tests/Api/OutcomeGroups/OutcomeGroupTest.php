<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\OutcomeGroups;

use CanvasLMS\Api\OutcomeGroups\OutcomeGroup;
use CanvasLMS\Config;
use CanvasLMS\Dto\OutcomeGroups\CreateOutcomeGroupDTO;
use CanvasLMS\Dto\OutcomeGroups\UpdateOutcomeGroupDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\OutcomeLink;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class OutcomeGroupTest extends TestCase
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

        OutcomeGroup::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGet(): void
    {
        $responseData = [
            ['id' => 1, 'title' => 'Group 1'],
            ['id' => 2, 'title' => 'Group 2']
        ];

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/outcome_groups', ['query' => []])
            ->willReturn($mockResponse);

        $groups = OutcomeGroup::get();

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(OutcomeGroup::class, $groups[0]);
        $this->assertEquals(1, $groups[0]->id);
        $this->assertEquals('Group 1', $groups[0]->title);
    }

    public function testFetchGlobal(): void
    {
        $responseData = [
            ['id' => 1, 'title' => 'Global Group 1'],
            ['id' => 2, 'title' => 'Global Group 2']
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($responseData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('global/outcome_groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $groups = OutcomeGroup::fetchGlobal();

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(OutcomeGroup::class, $groups[0]);
        $this->assertEquals(1, $groups[0]->id);
        $this->assertEquals('Global Group 1', $groups[0]->title);
    }

    public function testFindGlobal(): void
    {
        $responseData = ['id' => 123, 'title' => 'Global Group'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('global/outcome_groups/123')
            ->willReturn($this->mockResponse);

        $group = OutcomeGroup::findGlobal(123);

        $this->assertInstanceOf(OutcomeGroup::class, $group);
        $this->assertEquals(123, $group->id);
        $this->assertEquals('Global Group', $group->title);
    }

    public function testGetGlobalRootGroup(): void
    {
        $responseData = ['id' => 0, 'title' => 'Global Root'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('global/root_outcome_group')
            ->willReturn($this->mockResponse);

        $group = OutcomeGroup::getGlobalRootGroup();

        $this->assertInstanceOf(OutcomeGroup::class, $group);
        $this->assertEquals(0, $group->id);
        $this->assertEquals('Global Root', $group->title);
    }

    public function testCreateGlobal(): void
    {
        $createData = [
            'title' => 'New Global Group',
            'description' => 'Test description'
        ];

        $responseData = [
            'id' => 456,
            'title' => 'New Global Group',
            'description' => 'Test description'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'global/outcome_groups/global/subgroups',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);

        $group = OutcomeGroup::createGlobal($createData);

        $this->assertInstanceOf(OutcomeGroup::class, $group);
        $this->assertEquals(456, $group->id);
        $this->assertEquals('New Global Group', $group->title);
    }

    public function testOutcomesReturnsOutcomeLinks(): void
    {
        $group = new OutcomeGroup([
            'id' => 123,
            'context_type' => 'Account',
            'context_id' => 1
        ]);

        $responseData = [
            [
                'url' => '/api/v1/accounts/1/outcome_groups/123/outcomes/1',
                'context_id' => 1,
                'context_type' => 'Account',
                'assessed' => false,
                'can_unlink' => true,
                'outcome' => [
                    'id' => 1,
                    'title' => 'Outcome 1'
                ]
            ]
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($responseData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with(
                'Account/1/outcome_groups/123/outcomes',
                $this->callback(function ($options) {
                    return isset($options['query']) && 
                           $options['query']['outcome_style'] === 'abbrev';
                })
            )
            ->willReturn($mockPaginatedResponse);

        $outcomes = $group->outcomes();

        $this->assertCount(1, $outcomes);
        $this->assertInstanceOf(OutcomeLink::class, $outcomes[0]);
        $this->assertEquals('/api/v1/accounts/1/outcome_groups/123/outcomes/1', $outcomes[0]->url);
        $this->assertFalse($outcomes[0]->assessed);
        $this->assertTrue($outcomes[0]->canUnlink);
    }

    public function testLinkOutcomeWithMoveFrom(): void
    {
        $group = new OutcomeGroup([
            'id' => 123,
            'context_type' => 'Account',
            'context_id' => 1
        ]);

        $responseData = [
            'url' => '/api/v1/accounts/1/outcome_groups/123/outcomes/456',
            'outcome' => ['id' => 456, 'title' => 'Linked Outcome']
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with(
                'Account/1/outcome_groups/123/outcomes/456',
                $this->callback(function ($options) {
                    return isset($options['query']) && 
                           $options['query']['move_from'] === 789;
                })
            )
            ->willReturn($this->mockResponse);

        $link = $group->linkOutcome(456, 789);

        $this->assertInstanceOf(OutcomeLink::class, $link);
        $this->assertEquals('/api/v1/accounts/1/outcome_groups/123/outcomes/456', $link->url);
    }

    public function testCreateOutcome(): void
    {
        $group = new OutcomeGroup([
            'id' => 123,
            'context_type' => 'Account',
            'context_id' => 1
        ]);

        $createData = [
            'title' => 'New Outcome',
            'description' => 'Test outcome',
            'mastery_points' => 3
        ];

        $responseData = [
            'url' => '/api/v1/accounts/1/outcome_groups/123/outcomes/789',
            'outcome' => [
                'id' => 789,
                'title' => 'New Outcome',
                'description' => 'Test outcome'
            ]
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'Account/1/outcome_groups/123/outcomes',
                $this->callback(function ($options) {
                    return isset($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);

        $link = $group->createOutcome($createData);

        $this->assertInstanceOf(OutcomeLink::class, $link);
        $this->assertEquals('/api/v1/accounts/1/outcome_groups/123/outcomes/789', $link->url);
    }

    public function testGetLinks(): void
    {
        $responseData = [
            [
                'url' => '/api/v1/accounts/1/outcome_group_links/1',
                'outcome' => ['id' => 1, 'title' => 'Outcome 1'],
                'outcome_group' => ['id' => 10, 'title' => 'Group 1']
            ]
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($responseData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/outcome_group_links', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $links = OutcomeGroup::fetchAllLinks();

        $this->assertCount(1, $links);
        $this->assertInstanceOf(OutcomeLink::class, $links[0]);
        $this->assertEquals('/api/v1/accounts/1/outcome_group_links/1', $links[0]->url);
    }

    public function testGetLinksByContext(): void
    {
        $responseData = [
            [
                'url' => '/api/v1/courses/123/outcome_group_links/1',
                'outcome' => ['id' => 1, 'title' => 'Outcome 1']
            ]
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($responseData);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/outcome_group_links', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $links = OutcomeGroup::fetchAllLinksByContext('courses', 123);

        $this->assertCount(1, $links);
        $this->assertInstanceOf(OutcomeLink::class, $links[0]);
        $this->assertEquals('/api/v1/courses/123/outcome_group_links/1', $links[0]->url);
    }

    public function testFindWithNullContextCallsFindGlobal(): void
    {
        $responseData = ['id' => 123, 'title' => 'Global Group'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('global/outcome_groups/123')
            ->willReturn($this->mockResponse);

        $group = OutcomeGroup::findByContext(null, null, 123);

        $this->assertInstanceOf(OutcomeGroup::class, $group);
        $this->assertEquals(123, $group->id);
    }
}