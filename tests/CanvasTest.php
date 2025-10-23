<?php

declare(strict_types=1);

namespace CanvasLMS\Tests;

use CanvasLMS\Canvas;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CanvasTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    private ResponseInterface $mockResponse;

    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        // Set up Config
        Config::setBaseUrl('https://canvas.example.com/');
        Config::setApiKey('test-api-key');

        // Set the mock HTTP client
        Canvas::setHttpClient($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Reset the HTTP client
        Canvas::setHttpClient(null);
    }

    public function testGetRequestWithJsonResponse(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses';
        $expectedResponse = ['id' => 123, 'name' => 'Test Course'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json; charset=utf-8');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetRequestWithRelativeUrl(): void
    {
        $url = '/api/v1/courses';
        $expectedResponse = ['courses' => []];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testPostRequestWithData(): void
    {
        $url = '/api/v1/courses/123/assignments';
        $data = [
            'assignment' => [
                'name' => 'New Assignment',
                'points_possible' => 100,
            ],
        ];
        $expectedResponse = ['id' => 456, 'name' => 'New Assignment'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'POST', $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $result = Canvas::post($url, $data);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testPostRequestWithSimpleData(): void
    {
        $url = '/api/v1/courses';
        $data = [
            'name' => 'New Course',
            'course_code' => 'CS101',
        ];
        $expectedResponse = ['id' => 789, 'name' => 'New Course'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'POST', ['json' => $data])
            ->willReturn($this->mockResponse);

        $result = Canvas::post($url, $data);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testPutRequest(): void
    {
        $url = '/api/v1/courses/123';
        $data = ['course' => ['name' => 'Updated Course']];
        $expectedResponse = ['id' => 123, 'name' => 'Updated Course'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'PUT', $this->anything())
            ->willReturn($this->mockResponse);

        $result = Canvas::put($url, $data);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteRequest(): void
    {
        $url = '/api/v1/courses/123';
        $expectedResponse = ['deleted' => true];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'DELETE', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::delete($url);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testPatchRequest(): void
    {
        $url = '/api/v1/courses/123';
        $data = ['course' => ['conclude' => true]];
        $expectedResponse = ['id' => 123, 'workflow_state' => 'completed'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'PATCH', $this->anything())
            ->willReturn($this->mockResponse);

        $result = Canvas::patch($url, $data);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testRequestWithCustomMethod(): void
    {
        $url = '/api/v1/courses/123/analytics';
        $expectedResponse = ['views' => 100];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'HEAD', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::request($url, 'HEAD');

        $this->assertEquals($expectedResponse, $result);
    }

    public function testNonJsonResponse(): void
    {
        $url = '/api/v1/files/123/download';
        $expectedContent = 'This is file content';

        $this->mockStream->method('getContents')
            ->willReturn($expectedContent);

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/plain');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url);

        $this->assertEquals($expectedContent, $result);
    }

    public function testHtmlResponse(): void
    {
        $url = '/api/v1/courses/123/front_page';
        $expectedContent = '<html><body>Page content</body></html>';

        $this->mockStream->method('getContents')
            ->willReturn($expectedContent);

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/html; charset=utf-8');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url);

        $this->assertEquals($expectedContent, $result);
    }

    public function testInvalidJsonReturnsRawContent(): void
    {
        $url = '/api/v1/malformed';
        $malformedJson = '{"invalid": json content';

        $this->mockStream->method('getContents')
            ->willReturn($malformedJson);

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url);

        // Should return raw content when JSON decode fails
        $this->assertEquals($malformedJson, $result);
    }

    public function testGetWithOptions(): void
    {
        $url = '/api/v1/courses';
        $options = [
            'query' => ['per_page' => 50, 'include' => ['term', 'teachers']],
            'headers' => ['X-Custom-Header' => 'value'],
        ];
        $expectedResponse = ['courses' => []];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', $options)
            ->willReturn($this->mockResponse);

        $result = Canvas::get($url, $options);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testErrorPropagation(): void
    {
        $url = '/api/v1/courses/999';

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'GET', [])
            ->willThrowException(new CanvasApiException('Not found', 404, ['error' => 'Course not found']));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Not found');
        $this->expectExceptionCode(404);

        Canvas::get($url);
    }

    public function testEmptyResponse(): void
    {
        $url = '/api/v1/courses/123/reset';

        $this->mockStream->method('getContents')
            ->willReturn('');

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($url, 'POST', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::post($url);

        $this->assertEquals('', $result);
    }

    public function testFollowPaginationUrl(): void
    {
        // Simulate a full pagination URL from Canvas
        $paginationUrl = 'https://canvas.example.com/api/v1/courses?page=2&per_page=10';
        $expectedResponse = ['courses' => [['id' => 1], ['id' => 2]]];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($expectedResponse));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with($paginationUrl, 'GET', [])
            ->willReturn($this->mockResponse);

        $result = Canvas::get($paginationUrl);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testPrepareMultipartDataWithDeeplyNestedSequentialArrays(): void
    {
        // Test Canvas appointment groups pattern: appointment_group[new_appointments][0][]=timestamp
        $data = [
            'appointment_group' => [
                'new_appointments' => [
                    ['2024-01-01T10:00:00Z', '2024-01-01T11:00:00Z'],
                    ['2024-01-02T10:00:00Z', '2024-01-02T11:00:00Z'],
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['id' => 123]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/appointment_groups', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Verify the structure matches Canvas API expectations
                $expectedFields = [
                    'appointment_group[new_appointments][0][]=2024-01-01T10:00:00Z',
                    'appointment_group[new_appointments][0][]=2024-01-01T11:00:00Z',
                    'appointment_group[new_appointments][1][]=2024-01-02T10:00:00Z',
                    'appointment_group[new_appointments][1][]=2024-01-02T11:00:00Z',
                ];

                $actualFields = array_map(fn ($item) => $item['name'] . '=' . $item['contents'], $multipart);

                foreach ($expectedFields as $expected) {
                    if (!in_array($expected, $actualFields, true)) {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/appointment_groups', $data);
    }

    public function testPrepareMultipartDataWithDeeplyNestedAssociativeArrays(): void
    {
        // Test Canvas bulk grades pattern: grade_data[134][posted_grade]=A-
        $data = [
            'grade_data' => [
                '134' => ['posted_grade' => 'A-', 'excuse' => false],
                '135' => ['posted_grade' => 'B+', 'excuse' => false],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['success' => true]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/submissions/bulk', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Verify the structure matches Canvas API expectations
                $expectedFields = [
                    'grade_data[134][posted_grade]' => 'A-',
                    'grade_data[134][excuse]' => '',  // false converts to empty string
                    'grade_data[135][posted_grade]' => 'B+',
                    'grade_data[135][excuse]' => '',
                ];

                foreach ($expectedFields as $expectedName => $expectedContent) {
                    $found = false;
                    foreach ($multipart as $item) {
                        if ($item['name'] === $expectedName && $item['contents'] === $expectedContent) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/submissions/bulk', $data);
    }

    public function testPrepareMultipartDataWithMixedNesting(): void
    {
        // Test mixed sequential and associative nesting
        $data = [
            'quiz' => [
                'title' => 'Final Exam',
                'questions' => [
                    ['question_text' => 'Question 1', 'points_possible' => 10],
                    ['question_text' => 'Question 2', 'points_possible' => 15],
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['id' => 456]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/quizzes', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Verify mixed nesting is handled correctly
                $expectedFields = [
                    'quiz[title]' => 'Final Exam',
                    'quiz[questions][0][question_text]' => 'Question 1',
                    'quiz[questions][0][points_possible]' => '10',
                    'quiz[questions][1][question_text]' => 'Question 2',
                    'quiz[questions][1][points_possible]' => '15',
                ];

                foreach ($expectedFields as $expectedName => $expectedContent) {
                    $found = false;
                    foreach ($multipart as $item) {
                        if ($item['name'] === $expectedName && $item['contents'] === $expectedContent) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/quizzes', $data);
    }

    public function testPrepareMultipartDataBackwardCompatibilitySimpleArrays(): void
    {
        // Ensure existing simple one-level nested arrays still work
        $data = [
            'assignment' => [
                'name' => 'Homework 1',
                'points_possible' => 100,
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['id' => 789]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/assignments', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Verify backward compatibility
                $expectedFields = [
                    'assignment[name]' => 'Homework 1',
                    'assignment[points_possible]' => '100',
                ];

                foreach ($expectedFields as $expectedName => $expectedContent) {
                    $found = false;
                    foreach ($multipart as $item) {
                        if ($item['name'] === $expectedName && $item['contents'] === $expectedContent) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/assignments', $data);
    }

    public function testPrepareMultipartDataWithEmptyArrays(): void
    {
        // Test edge case of empty arrays
        $data = [
            'course' => [
                'name' => 'Test Course',
                'tags' => [],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['id' => 999]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/courses', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Empty arrays should not produce any fields
                $names = array_column($multipart, 'name');

                // Should have course[name] but no course[tags] entries
                return in_array('course[name]', $names, true) &&
                       !in_array('course[tags]', $names, true) &&
                       count(array_filter($names, fn ($n) => str_starts_with($n, 'course[tags]'))) === 0;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/courses', $data);
    }

    public function testPrepareMultipartDataNoStringArrayConversion(): void
    {
        // Ensure nested arrays are NOT converted to "Array" string
        $data = [
            'appointment_group' => [
                'new_appointments' => [
                    ['2024-01-01'],
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode(['id' => 111]));

        $this->mockResponse->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('rawRequest')
            ->with('/api/v1/appointment_groups', 'POST', $this->callback(function ($options) {
                if (!isset($options['multipart'])) {
                    return false;
                }

                $multipart = $options['multipart'];

                // Verify NO field contains "Array" as contents
                foreach ($multipart as $item) {
                    if ($item['contents'] === 'Array') {
                        return false;
                    }
                }

                return true;
            }))
            ->willReturn($this->mockResponse);

        Canvas::post('/api/v1/appointment_groups', $data);
    }
}
