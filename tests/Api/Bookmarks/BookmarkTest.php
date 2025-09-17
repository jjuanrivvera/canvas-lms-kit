<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Bookmarks;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Bookmarks\Bookmark;
use CanvasLMS\Dto\Bookmarks\CreateBookmarkDTO;
use CanvasLMS\Dto\Bookmarks\UpdateBookmarkDTO;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BookmarkTest extends TestCase
{
    private HttpClientInterface $httpClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        Bookmark::setApiClient($this->httpClientMock);
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(AbstractBaseApi::class);
        $property = $reflection->getProperty('apiClient');
        $property->setAccessible(true);
        $property->setValue(null, null);
        parent::tearDown();
    }

    public function testCreateBookmarkWithArray(): void
    {
        $bookmarkData = [
            'name' => 'My Course Bookmark',
            'url' => '/courses/123',
            'position' => 1,
            'data' => '{"course_id": 123}',
        ];

        $expectedResponse = [
            'id' => 456,
            'name' => 'My Course Bookmark',
            'url' => '/courses/123',
            'position' => 1,
            'data' => '{"course_id": 123}',
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                '/users/self/bookmarks',
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertCount(4, $data);

                    $fields = [];
                    foreach ($data as $field) {
                        $this->assertArrayHasKey('name', $field);
                        $this->assertArrayHasKey('contents', $field);
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('My Course Bookmark', $fields['bookmark[name]']);
                    $this->assertEquals('/courses/123', $fields['bookmark[url]']);
                    $this->assertEquals('1', $fields['bookmark[position]']);
                    $this->assertEquals('{"course_id": 123}', $fields['bookmark[data]']);

                    return true;
                })
            )
            ->willReturn($response);

        $bookmark = Bookmark::create($bookmarkData);

        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertEquals(456, $bookmark->id);
        $this->assertEquals('My Course Bookmark', $bookmark->name);
        $this->assertEquals('/courses/123', $bookmark->url);
        $this->assertEquals(1, $bookmark->position);
        $this->assertEquals('{"course_id": 123}', $bookmark->data);
    }

    public function testCreateBookmarkWithDTO(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->name = 'Group Bookmark';
        $dto->url = '/groups/789';
        $dto->position = 2;

        $expectedResponse = [
            'id' => 457,
            'name' => 'Group Bookmark',
            'url' => '/groups/789',
            'position' => 2,
            'data' => null,
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('/users/self/bookmarks', $this->isType('array'))
            ->willReturn($response);

        $bookmark = Bookmark::create($dto);

        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertEquals(457, $bookmark->id);
        $this->assertEquals('Group Bookmark', $bookmark->name);
        $this->assertEquals('/groups/789', $bookmark->url);
        $this->assertEquals(2, $bookmark->position);
        $this->assertNull($bookmark->data);
    }

    public function testFindBookmark(): void
    {
        $expectedResponse = [
            'id' => 123,
            'name' => 'Test Bookmark',
            'url' => '/courses/456',
            'position' => 1,
            'data' => '{"type": "course"}',
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('/users/self/bookmarks/123')
            ->willReturn($response);

        $bookmark = Bookmark::find(123);

        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertEquals(123, $bookmark->id);
        $this->assertEquals('Test Bookmark', $bookmark->name);
        $this->assertEquals('/courses/456', $bookmark->url);
        $this->assertEquals(1, $bookmark->position);
        $this->assertEquals('{"type": "course"}', $bookmark->data);
    }

    public function testGetBookmarks(): void
    {
        $expectedResponse = [
            [
                'id' => 1,
                'name' => 'Bookmark 1',
                'url' => '/courses/1',
                'position' => 1,
                'data' => null,
            ],
            [
                'id' => 2,
                'name' => 'Bookmark 2',
                'url' => '/groups/2',
                'position' => 2,
                'data' => '{"type": "group"}',
            ],
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('/users/self/bookmarks', ['query' => []])
            ->willReturn($response);

        $bookmarks = Bookmark::get();

        $this->assertIsArray($bookmarks);
        $this->assertCount(2, $bookmarks);

        $this->assertInstanceOf(Bookmark::class, $bookmarks[0]);
        $this->assertEquals(1, $bookmarks[0]->id);
        $this->assertEquals('Bookmark 1', $bookmarks[0]->name);

        $this->assertInstanceOf(Bookmark::class, $bookmarks[1]);
        $this->assertEquals(2, $bookmarks[1]->id);
        $this->assertEquals('Bookmark 2', $bookmarks[1]->name);
    }

    public function testUpdateBookmarkWithArray(): void
    {
        $updateData = [
            'name' => 'Updated Bookmark Name',
            'position' => 5,
        ];

        $expectedResponse = [
            'id' => 123,
            'name' => 'Updated Bookmark Name',
            'url' => '/courses/456',
            'position' => 5,
            'data' => null,
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                '/users/self/bookmarks/123',
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertCount(2, $data);

                    $fields = [];
                    foreach ($data as $field) {
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('Updated Bookmark Name', $fields['bookmark[name]']);
                    $this->assertEquals('5', $fields['bookmark[position]']);

                    return true;
                })
            )
            ->willReturn($response);

        $bookmark = Bookmark::update(123, $updateData);

        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertEquals(123, $bookmark->id);
        $this->assertEquals('Updated Bookmark Name', $bookmark->name);
        $this->assertEquals(5, $bookmark->position);
    }

    public function testUpdateBookmarkWithDTO(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->name = 'New Name';
        $dto->url = '/users/999';

        $expectedResponse = [
            'id' => 456,
            'name' => 'New Name',
            'url' => '/users/999',
            'position' => 1,
            'data' => null,
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('/users/self/bookmarks/456', $this->isType('array'))
            ->willReturn($response);

        $bookmark = Bookmark::update(456, $dto);

        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertEquals(456, $bookmark->id);
        $this->assertEquals('New Name', $bookmark->name);
        $this->assertEquals('/users/999', $bookmark->url);
    }

    public function testSaveNewBookmark(): void
    {
        $bookmark = new Bookmark([]);
        $bookmark->name = 'New Bookmark';
        $bookmark->url = '/courses/789';
        $bookmark->position = 3;

        $expectedResponse = [
            'id' => 999,
            'name' => 'New Bookmark',
            'url' => '/courses/789',
            'position' => 3,
            'data' => null,
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                '/users/self/bookmarks',
                $this->callback(function ($data) {
                    $fields = [];
                    foreach ($data as $field) {
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('New Bookmark', $fields['bookmark[name]']);
                    $this->assertEquals('/courses/789', $fields['bookmark[url]']);
                    $this->assertEquals('3', $fields['bookmark[position]']);

                    return true;
                })
            )
            ->willReturn($response);

        $savedBookmark = $bookmark->save();

        $this->assertSame($bookmark, $savedBookmark);
        $this->assertEquals(999, $bookmark->id);
        $this->assertEquals('New Bookmark', $bookmark->name);
    }

    public function testSaveExistingBookmark(): void
    {
        $bookmark = new Bookmark(['id' => 123, 'name' => 'Old Name', 'url' => '/old']);
        $bookmark->name = 'Updated Name';
        $bookmark->url = '/new';

        $expectedResponse = [
            'id' => 123,
            'name' => 'Updated Name',
            'url' => '/new',
            'position' => null,
            'data' => null,
        ];

        $response = new Response(200, [], json_encode($expectedResponse));
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                '/users/self/bookmarks/123',
                $this->callback(function ($data) {
                    $fields = [];
                    foreach ($data as $field) {
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('Updated Name', $fields['bookmark[name]']);
                    $this->assertEquals('/new', $fields['bookmark[url]']);

                    return true;
                })
            )
            ->willReturn($response);

        $savedBookmark = $bookmark->save();

        $this->assertSame($bookmark, $savedBookmark);
        $this->assertEquals(123, $bookmark->id);
        $this->assertEquals('Updated Name', $bookmark->name);
        $this->assertEquals('/new', $bookmark->url);
    }

    public function testDeleteBookmark(): void
    {
        $bookmark = new Bookmark(['id' => 123]);

        $response = new Response(204, [], '');
        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('/users/self/bookmarks/123')
            ->willReturn($response);

        $result = $bookmark->delete();

        $this->assertSame($bookmark, $result);
        $this->assertEquals(123, $bookmark->id);
    }

    public function testDeleteBookmarkWithoutIdThrowsException(): void
    {
        $bookmark = new Bookmark([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete bookmark without ID');

        $bookmark->delete();
    }

    public function testPaginationMethods(): void
    {
        $firstPageResponse = [
            ['id' => 1, 'name' => 'Bookmark 1'],
            ['id' => 2, 'name' => 'Bookmark 2'],
        ];

        $secondPageResponse = [
            ['id' => 3, 'name' => 'Bookmark 3'],
        ];

        // Create PaginatedResponse mocks
        $paginatedResponse1 = $this->createMock(PaginatedResponse::class);
        $paginatedResponse1->method('getJsonData')->willReturn($firstPageResponse);

        $paginatedResponse2 = $this->createMock(PaginatedResponse::class);
        $paginatedResponse2->method('getJsonData')->willReturn($secondPageResponse);
        $paginatedResponse2->method('getNext')->willReturn(null);

        $paginatedResponse1->method('getNext')->willReturn($paginatedResponse2);

        $this->httpClientMock->expects($this->once())
            ->method('getPaginated')
            ->with('/users/self/bookmarks', ['query' => []])
            ->willReturn($paginatedResponse1);

        $allBookmarks = Bookmark::all();

        $this->assertIsArray($allBookmarks);
        $this->assertCount(3, $allBookmarks);
        $this->assertEquals(1, $allBookmarks[0]->id);
        $this->assertEquals(2, $allBookmarks[1]->id);
        $this->assertEquals(3, $allBookmarks[2]->id);
    }

    public function testPaginateMethod(): void
    {
        $bookmarksData = [
            ['id' => 1, 'name' => 'Bookmark 1'],
            ['id' => 2, 'name' => 'Bookmark 2'],
        ];

        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        $paginatedResponse->method('getJsonData')->willReturn($bookmarksData);
        $paginatedResponse->method('toPaginationResult')->willReturn(
            new PaginationResult(
                [
                    new Bookmark(['id' => 1, 'name' => 'Bookmark 1']),
                    new Bookmark(['id' => 2, 'name' => 'Bookmark 2']),
                ],
                [
                    'next' => 'https://canvas.test/api/v1/users/self/bookmarks?page=2',
                    'current' => 'https://canvas.test/api/v1/users/self/bookmarks?page=1',
                    'first' => 'https://canvas.test/api/v1/users/self/bookmarks?page=1',
                    'last' => 'https://canvas.test/api/v1/users/self/bookmarks?page=5',
                ],
                1,
                5,
                10
            )
        );

        $this->httpClientMock->expects($this->once())
            ->method('getPaginated')
            ->with('/users/self/bookmarks', ['query' => ['per_page' => 10]])
            ->willReturn($paginatedResponse);

        $result = Bookmark::paginate(['per_page' => 10]);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertCount(2, $result->getData());
        $this->assertInstanceOf(Bookmark::class, $result->getData()[0]);

        $this->assertEquals(1, $result->getCurrentPage());
        $this->assertEquals(5, $result->getTotalPages());
        $this->assertTrue($result->hasMore());
    }
}
