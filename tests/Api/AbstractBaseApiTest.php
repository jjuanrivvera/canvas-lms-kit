<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * AbstractBaseApiTest Class
 *
 * Test cases for AbstractBaseApi pagination functionality including:
 * - Helper methods for pagination
 * - PaginatedResponse integration
 * - Model conversion from paginated responses
 * - Backward compatibility with existing methods
 */
class AbstractBaseApiTest extends TestCase
{
    /**
     * Concrete implementation of AbstractBaseApi for testing
     */
    private string $testApiClass;

    /**
     * Mock HTTP client
     */
    private HttpClientInterface $mockHttpClient;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        // Create a concrete test class that extends AbstractBaseApi
        $this->testApiClass = $this->createTestApiClass();

        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->testApiClass::setApiClient($this->mockHttpClient);
    }

    /**
     * Create a concrete test class that extends AbstractBaseApi
     */
    private function createTestApiClass(): string
    {
        // Use anonymous class instead of eval()
        // We need to pass empty array to constructor to avoid the error
        $testClass = new class([]) extends \CanvasLMS\Api\AbstractBaseApi
        {
            public $id;
            public $name;
            
            protected static function getEndpoint(): string
            {
                return 'test_items';
            }
            
            public static function find(int $id, array $params = []): self
            {
                return new self(['id' => $id, 'name' => 'Test Item']);
            }
            
            public static function get(array $params = []): array
            {
                return [
                    new self(['id' => 1, 'name' => 'Test Item 1']),
                    new self(['id' => 2, 'name' => 'Test Item 2']),
                ];
            }
            
            public static function testGetPaginatedResponse(string $endpoint, array $params = []): \CanvasLMS\Pagination\PaginatedResponse
            {
                return parent::getPaginatedResponse($endpoint, $params);
            }
            
            public static function testConvertPaginatedResponseToModels(\CanvasLMS\Pagination\PaginatedResponse $paginatedResponse): array
            {
                return parent::convertPaginatedResponseToModels($paginatedResponse);
            }
            
            
            public static function testCreatePaginationResult(\CanvasLMS\Pagination\PaginatedResponse $paginatedResponse): \CanvasLMS\Pagination\PaginationResult
            {
                return parent::createPaginationResult($paginatedResponse);
            }

            public static function testParseJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
            {
                return parent::parseJsonResponse($response);
            }
        };

        return get_class($testClass);
    }

    /**
     * Test getPaginatedResponse method
     */
    public function testGetPaginatedResponse(): void
    {
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('/test/endpoint', ['query' => ['per_page' => 10]])
            ->willReturn($mockPaginatedResponse);

        $result = $this->testApiClass::testGetPaginatedResponse('/test/endpoint', ['per_page' => 10]);

        $this->assertSame($mockPaginatedResponse, $result);
    }

    /**
     * Test convertPaginatedResponseToModels method
     */
    public function testConvertPaginatedResponseToModels(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($responseData);

        $models = $this->testApiClass::testConvertPaginatedResponseToModels($mockPaginatedResponse);

        $this->assertIsArray($models);
        $this->assertCount(3, $models);

        foreach ($models as $index => $model) {
            $this->assertInstanceOf($this->testApiClass, $model);
            $this->assertEquals($responseData[$index]['id'], $model->id);
            $this->assertEquals($responseData[$index]['name'], $model->name);
        }
    }

    /**
     * Test fetching all pages using the new pattern
     */
    public function testFetchAllPagesNewPattern(): void
    {
        $allPagesData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3'],
            ['id' => 4, 'name' => 'Item 4'],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($allPagesData);

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('/test/endpoint', ['query' => ['per_page' => 50]])
            ->willReturn($mockPaginatedResponse);

        // Test the new pattern: getPaginatedResponse + all() + array_map
        $paginatedResponse = $this->testApiClass::testGetPaginatedResponse('/test/endpoint', ['per_page' => 50]);
        $allData = $paginatedResponse->all();
        $models = array_map(fn($data) => new $this->testApiClass($data), $allData);

        $this->assertIsArray($models);
        $this->assertCount(4, $models);

        foreach ($models as $index => $model) {
            $this->assertInstanceOf($this->testApiClass, $model);
            $this->assertEquals($allPagesData[$index]['id'], $model->id);
            $this->assertEquals($allPagesData[$index]['name'], $model->name);
        }
    }

    /**
     * Test createPaginationResult method
     */
    public function testCreatePaginationResult(): void
    {
        $responseData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $mockPaginationResult = $this->createMock(PaginationResult::class);
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);

        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($responseData);

        $mockPaginatedResponse->expects($this->once())
            ->method('toPaginationResult')
            ->with($this->callback(function ($models) use ($responseData) {
                // Verify that models are correctly created
                $this->assertIsArray($models);
                $this->assertCount(2, $models);

                foreach ($models as $index => $model) {
                    $this->assertInstanceOf($this->testApiClass, $model);
                    $this->assertEquals($responseData[$index]['id'], $model->id);
                    $this->assertEquals($responseData[$index]['name'], $model->name);
                }

                return true;
            }))
            ->willReturn($mockPaginationResult);

        $result = $this->testApiClass::testCreatePaginationResult($mockPaginatedResponse);

        $this->assertSame($mockPaginationResult, $result);
    }

    /**
     * Test method aliases work correctly
     */
    public function testMethodAliases(): void
    {
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);

        // Test that aliases are registered
        $aliases = $this->getMethodAliases();

        $this->assertArrayHasKey('get', $aliases);
        $this->assertArrayHasKey('all', $aliases);
        $this->assertArrayHasKey('paginate', $aliases);
        $this->assertArrayHasKey('find', $aliases);

        // Test specific alias mappings
        $this->assertEquals(['fetch', 'list', 'fetchAll'], $aliases['get']);
        $this->assertEquals(['fetchAllPages', 'getAll'], $aliases['all']);
        $this->assertEquals(['getPaginated', 'withPagination', 'fetchPage'], $aliases['paginate']);
        $this->assertEquals(['one', 'getOne'], $aliases['find']);
    }

    /**
     * Test backward compatibility - existing methods should still work
     */
    public function testBackwardCompatibility(): void
    {
        // Create a test class that implements fetchAll like existing API classes
        $testClass = $this->createTestApiClassWithFetchAll();

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $responseData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/test', ['query' => ['per_page' => 10]])
            ->willReturn($mockResponse);

        $testClass::setApiClient($this->mockHttpClient);
        $result = $testClass::get(['per_page' => 10]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        foreach ($result as $index => $model) {
            $this->assertInstanceOf($testClass, $model);
            $this->assertEquals($responseData[$index]['id'], $model->id);
            $this->assertEquals($responseData[$index]['name'], $model->name);
        }
    }

    /**
     * Create a test class that implements fetchAll like existing API classes
     */
    private function createTestApiClassWithFetchAll(): string
    {
        // Use anonymous class instead of eval()
        // We need to pass empty array to constructor to avoid the error
        $testClass = new class([]) extends \CanvasLMS\Api\AbstractBaseApi
        {
            public $id;
            public $name;
            
            protected static function getEndpoint(): string
            {
                return 'test';
            }
            
            public static function find(int $id, array $params = []): self
            {
                return new self(['id' => $id, 'name' => 'Test Item']);
            }
            
            public static function get(array $params = []): array
            {
                self::checkApiClient();
                
                $response = self::$apiClient->get('/test', ['query' => $params]);
                $data = json_decode($response->getBody()->getContents(), true);
                
                return array_map(function ($item) {
                    return new self($item);
                }, $data);
            }
        };

        return get_class($testClass);
    }

    /**
     * Helper method to access method aliases
     */
    private function getMethodAliases(): array
    {
        $reflection = new \ReflectionClass($this->testApiClass);
        $property = $reflection->getProperty('methodAliases');
        $property->setAccessible(true);

        return $property->getValue();
    }

    /**
     * Test that invalid method calls throw appropriate exceptions
     */
    public function testInvalidMethodCallThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method invalidMethod does not exist');

        $this->testApiClass::invalidMethod();
    }

    /**
     * Test that constructor properly sets properties
     */
    public function testConstructorSetsProperties(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test Name',
            'non_existent_property' => 'should be ignored'
        ];

        $instance = new $this->testApiClass($data);

        $this->assertEquals(123, $instance->id);
        $this->assertEquals('Test Name', $instance->name);
        $this->assertFalse(property_exists($instance, 'non_existent_property'));
    }

    /**
     * Test that snake_case properties are converted to camelCase
     */
    public function testSnakeCaseToCarmelCaseConversion(): void
    {
        // Use anonymous class instead of eval()
        // We need to pass empty array to constructor to avoid the error
        $testClass = new class([]) extends \CanvasLMS\Api\AbstractBaseApi
        {
            public $someProperty;
            
            protected static function getEndpoint(): string
            {
                return 'test';
            }
            
            public static function find(int $id, array $params = []): self
            {
                return new self(['id' => $id]);
            }
            
            public static function get(array $params = []): array
            {
                return [];
            }
        };

        $data = [
            'some_property' => 'test value'
        ];

        $className = get_class($testClass);
        $instance = new $className($data);

        $this->assertEquals('test value', $instance->someProperty);
    }

    /**
     * Test that populate method correctly handles camelCase properties
     */
    public function testPopulateHandlesCamelCaseProperties(): void
    {
        // Create a test class with camelCase properties
        $testClass = new class([]) extends \CanvasLMS\Api\AbstractBaseApi
        {
            public ?string $firstName = null;
            public ?string $lastName = null;
            public ?int $userId = null;
            public ?bool $isActive = null;

            protected static function getEndpoint(): string
            {
                return 'test';
            }

            public static function find(int $id, array $params = []): self
            {
                return new self(['id' => $id]);
            }

            // Make populate method public for testing
            public function testPopulate(array $data): void
            {
                $this->populate($data);
            }
        };

        $className = get_class($testClass);
        $instance = new $className([]);

        // Test with snake_case input data
        $snakeCaseData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_id' => 123,
            'is_active' => true
        ];

        $instance->testPopulate($snakeCaseData);

        $this->assertEquals('John', $instance->firstName);
        $this->assertEquals('Doe', $instance->lastName);
        $this->assertEquals(123, $instance->userId);
        $this->assertTrue($instance->isActive);

        // Test with camelCase input data (should also work)
        $camelCaseData = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'userId' => 456,
            'isActive' => false
        ];

        $instance->testPopulate($camelCaseData);

        $this->assertEquals('Jane', $instance->firstName);
        $this->assertEquals('Smith', $instance->lastName);
        $this->assertEquals(456, $instance->userId);
        $this->assertFalse($instance->isActive);
    }

    /**
     * Test parseJsonResponse method with valid JSON
     */
    public function testParseJsonResponseWithValidJson(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $responseData = [
            'id' => 123,
            'name' => 'Test Item',
            'status' => 'active'
        ];

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertIsArray($result);
        $this->assertEquals($responseData, $result);
    }

    /**
     * Test parseJsonResponse method with invalid JSON
     */
    public function testParseJsonResponseWithInvalidJson(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn('{"invalid json"');

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parseJsonResponse method with empty response
     */
    public function testParseJsonResponseWithEmptyResponse(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn('');

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parseJsonResponse method with null response
     */
    public function testParseJsonResponseWithNullString(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn('null');

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parseJsonResponse method with array response
     */
    public function testParseJsonResponseWithArrayResponse(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $responseData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3']
        ];

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($responseData, $result);
    }

    /**
     * Test parseJsonResponse method properly handles StreamInterface
     */
    public function testParseJsonResponseHandlesStreamInterface(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $responseData = ['success' => true, 'data' => 'test'];

        // Verify that getContents() is called on the StreamInterface object
        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $result = $this->testApiClass::testParseJsonResponse($mockResponse);

        $this->assertEquals($responseData, $result);
    }
}
