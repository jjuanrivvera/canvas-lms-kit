<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\FeatureFlags;

use CanvasLMS\Api\FeatureFlags\FeatureFlag;
use CanvasLMS\Config;
use CanvasLMS\Dto\FeatureFlags\UpdateFeatureFlagDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FeatureFlagTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private int $accountId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        FeatureFlag::setApiClient($this->mockClient);
        Config::setAccountId($this->accountId);
    }

    private function createMockResponse(string $body): ResponseInterface
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn($body);
        
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);
        
        return $mockResponse;
    }

    public function testFetchAllUsesAccountContext(): void
    {
        $expectedData = [
            [
                'feature' => 'new_gradebook',
                'display_name' => 'New Gradebook',
                'applies_to' => 'Course',
                'feature_flag' => [
                    'context_type' => 'Account',
                    'context_id' => 1,
                    'feature' => 'new_gradebook',
                    'state' => 'allowed'
                ]
            ]
        ];

        $mockResponse = $this->createMockResponse(json_encode($expectedData));

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/features', ['query' => []])
            ->willReturn($mockResponse);

        $features = FeatureFlag::fetchAll();

        $this->assertCount(1, $features);
        $this->assertInstanceOf(FeatureFlag::class, $features[0]);
        $this->assertEquals('new_gradebook', $features[0]->feature);
        $this->assertEquals('New Gradebook', $features[0]->display_name);
        $this->assertEquals('account', $features[0]->contextType);
        $this->assertEquals(1, $features[0]->contextId);
    }

    public function testFetchByContextForCourse(): void
    {
        $courseId = 123;
        $expectedData = [
            [
                'feature' => 'anonymous_grading',
                'display_name' => 'Anonymous Grading',
                'applies_to' => 'Course',
                'feature_flag' => [
                    'context_type' => 'Course',
                    'context_id' => $courseId,
                    'feature' => 'anonymous_grading',
                    'state' => 'on'
                ]
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/features', ['query' => []])
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $features = FeatureFlag::fetchByContext('courses', $courseId);

        $this->assertCount(1, $features);
        $this->assertEquals('anonymous_grading', $features[0]->feature);
        $this->assertEquals('course', $features[0]->contextType);
        $this->assertEquals($courseId, $features[0]->contextId);
    }

    public function testFetchByContextForUser(): void
    {
        $userId = 456;
        $expectedData = [
            [
                'feature' => 'student_planner',
                'display_name' => 'Student Planner',
                'applies_to' => 'User',
                'beta' => true,
                'feature_flag' => [
                    'context_type' => 'User',
                    'context_id' => $userId,
                    'feature' => 'student_planner',
                    'state' => 'allowed'
                ]
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('users/456/features', ['query' => []])
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $features = FeatureFlag::fetchByContext('users', $userId);

        $this->assertCount(1, $features);
        $this->assertEquals('student_planner', $features[0]->feature);
        $this->assertEquals('user', $features[0]->contextType);
        $this->assertEquals($userId, $features[0]->contextId);
        $this->assertTrue($features[0]->beta);
    }

    public function testFindUsesAccountContext(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'display_name' => 'New Gradebook',
            'applies_to' => 'Course',
            'feature_flag' => [
                'context_type' => 'Account',
                'context_id' => 1,
                'feature' => 'new_gradebook',
                'state' => 'allowed',
                'locked' => false,
                'hidden' => false
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/features/flags/new_gradebook')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::find('new_gradebook');

        $this->assertInstanceOf(FeatureFlag::class, $feature);
        $this->assertEquals('new_gradebook', $feature->feature);
        $this->assertEquals('allowed', $feature->state);
        $this->assertFalse($feature->locked);
        $this->assertFalse($feature->hidden);
    }

    public function testFindByContext(): void
    {
        $courseId = 123;
        $expectedData = [
            'feature' => 'anonymous_grading',
            'display_name' => 'Anonymous Grading',
            'applies_to' => 'Course',
            'feature_flag' => [
                'context_type' => 'Course',
                'context_id' => $courseId,
                'feature' => 'anonymous_grading',
                'state' => 'on',
                'locked' => true,
                'hidden' => false
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/features/flags/anonymous_grading')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::findByContext('courses', $courseId, 'anonymous_grading');

        $this->assertEquals('anonymous_grading', $feature->feature);
        $this->assertEquals('on', $feature->state);
        $this->assertTrue($feature->locked);
        $this->assertEquals('course', $feature->contextType);
        $this->assertEquals($courseId, $feature->contextId);
    }

    public function testUpdateWithArray(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'display_name' => 'New Gradebook',
            'feature_flag' => [
                'state' => 'on',
                'locked' => true,
                'hidden' => false
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('accounts/1/features/flags/new_gradebook', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                $this->assertCount(2, $multipart);
                
                $stateFound = false;
                $lockedFound = false;
                
                foreach ($multipart as $part) {
                    if ($part['name'] === 'state' && $part['contents'] === 'on') {
                        $stateFound = true;
                    }
                    if ($part['name'] === 'locked' && $part['contents'] === 'true') {
                        $lockedFound = true;
                    }
                }
                
                return $stateFound && $lockedFound;
            }))
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::update('new_gradebook', [
            'state' => 'on',
            'locked' => true
        ]);

        $this->assertEquals('new_gradebook', $feature->feature);
        $this->assertEquals('on', $feature->state);
        $this->assertTrue($feature->locked);
    }

    public function testUpdateWithDTO(): void
    {
        $expectedData = [
            'feature' => 'anonymous_grading',
            'display_name' => 'Anonymous Grading',
            'feature_flag' => [
                'state' => 'allowed',
                'locked' => false,
                'hidden' => true
            ]
        ];

        $dto = new UpdateFeatureFlagDTO();
        $dto->setState('allowed')
            ->setLocked(false)
            ->setHidden(true);

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('accounts/1/features/flags/anonymous_grading', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                $this->assertCount(3, $multipart);
                
                $checks = [
                    'state' => 'allowed',
                    'locked' => 'false',
                    'hidden' => 'true'
                ];
                
                foreach ($multipart as $part) {
                    if (isset($checks[$part['name']])) {
                        $this->assertEquals($checks[$part['name']], $part['contents']);
                    }
                }
                
                return true;
            }))
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::update('anonymous_grading', $dto);

        $this->assertEquals('anonymous_grading', $feature->feature);
        $this->assertEquals('allowed', $feature->state);
        $this->assertFalse($feature->locked);
        $this->assertTrue($feature->hidden);
    }

    public function testUpdateByContext(): void
    {
        $courseId = 123;
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => [
                'state' => 'off',
                'locked' => true
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('courses/123/features/flags/new_gradebook')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::updateByContext('courses', $courseId, 'new_gradebook', [
            'state' => 'off',
            'locked' => true
        ]);

        $this->assertEquals('new_gradebook', $feature->feature);
        $this->assertEquals('off', $feature->state);
        $this->assertTrue($feature->locked);
        $this->assertEquals('course', $feature->contextType);
        $this->assertEquals($courseId, $feature->contextId);
    }

    public function testDeleteSuccess(): void
    {
        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('accounts/1/features/flags/new_gradebook');

        $result = FeatureFlag::delete('new_gradebook');

        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('accounts/1/features/flags/non_existent')
            ->willThrowException(new CanvasApiException('Not Found', 404));

        $result = FeatureFlag::delete('non_existent');

        $this->assertFalse($result);
    }

    public function testDeleteByContext(): void
    {
        $courseId = 123;
        
        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('courses/123/features/flags/anonymous_grading');

        $result = FeatureFlag::deleteByContext('courses', $courseId, 'anonymous_grading');

        $this->assertTrue($result);
    }

    public function testSetFeatureState(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => [
                'state' => 'on'
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('accounts/1/features/flags/new_gradebook', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                return count($multipart) === 1 
                    && $multipart[0]['name'] === 'state' 
                    && $multipart[0]['contents'] === 'on';
            }))
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::setFeatureState('new_gradebook', 'on');

        $this->assertEquals('on', $feature->state);
    }

    public function testEnableFeature(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => [
                'state' => 'on'
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::enable('new_gradebook');

        $this->assertEquals('on', $feature->state);
    }

    public function testDisableFeature(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => [
                'state' => 'off'
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::disable('new_gradebook');

        $this->assertEquals('off', $feature->state);
    }

    public function testAllowFeature(): void
    {
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => [
                'state' => 'allowed'
            ]
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::allow('new_gradebook');

        $this->assertEquals('allowed', $feature->state);
    }

    public function testIsEnabledWithFeatureFlagState(): void
    {
        $feature = new FeatureFlag();
        $feature->feature_flag = ['state' => 'on'];

        $this->assertTrue($feature->isEnabled());
    }

    public function testIsEnabledWithDirectState(): void
    {
        $feature = new FeatureFlag();
        $feature->state = 'on';

        $this->assertTrue($feature->isEnabled());
    }

    public function testIsDisabled(): void
    {
        $feature = new FeatureFlag();
        $feature->feature_flag = ['state' => 'off'];

        $this->assertTrue($feature->isDisabled());
    }

    public function testIsAllowed(): void
    {
        $feature = new FeatureFlag();
        $feature->state = 'allowed';

        $this->assertTrue($feature->isAllowed());
    }

    public function testIsLocked(): void
    {
        $feature = new FeatureFlag();
        $feature->feature_flag = ['locked' => true];

        $this->assertTrue($feature->isLocked());
    }

    public function testIsHidden(): void
    {
        $feature = new FeatureFlag();
        $feature->hidden = true;

        $this->assertTrue($feature->isHidden());
    }

    public function testIsBeta(): void
    {
        $feature = new FeatureFlag();
        $feature->beta = true;

        $this->assertTrue($feature->isBeta());
    }

    public function testIsDevelopment(): void
    {
        $feature = new FeatureFlag();
        $feature->development = true;

        $this->assertTrue($feature->isDevelopment());
    }

    public function testEnableByContext(): void
    {
        $courseId = 123;
        $expectedData = [
            'feature' => 'new_gradebook',
            'feature_flag' => ['state' => 'on']
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('courses/123/features/flags/new_gradebook')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::enableByContext('courses', $courseId, 'new_gradebook');

        $this->assertEquals('on', $feature->state);
        $this->assertEquals('course', $feature->contextType);
        $this->assertEquals($courseId, $feature->contextId);
    }

    public function testDisableByContext(): void
    {
        $userId = 456;
        $expectedData = [
            'feature' => 'student_planner',
            'feature_flag' => ['state' => 'off']
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('users/456/features/flags/student_planner')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::disableByContext('users', $userId, 'student_planner');

        $this->assertEquals('off', $feature->state);
        $this->assertEquals('user', $feature->contextType);
        $this->assertEquals($userId, $feature->contextId);
    }

    public function testAllowByContext(): void
    {
        $courseId = 789;
        $expectedData = [
            'feature' => 'anonymous_grading',
            'feature_flag' => ['state' => 'allowed']
        ];

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('courses/789/features/flags/anonymous_grading')
            ->willReturn($this->createMockResponse(json_encode($expectedData)));

        $feature = FeatureFlag::allowByContext('courses', $courseId, 'anonymous_grading');

        $this->assertEquals('allowed', $feature->state);
        $this->assertEquals('course', $feature->contextType);
        $this->assertEquals($courseId, $feature->contextId);
    }

    public function testInvalidContextTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid context type "invalid". Valid types are: accounts, courses, users');

        FeatureFlag::fetchByContext('invalid', 123);
    }

    public function testContextNormalization(): void
    {
        $courseId = 123;
        $expectedData = [
            'feature' => 'test_feature',
            'feature_flag' => ['state' => 'on']
        ];

        $mockResponse = $this->createMockResponse(json_encode($expectedData));

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/features/flags/test_feature')
            ->willReturn($mockResponse);

        $feature = FeatureFlag::findByContext('courses', $courseId, 'test_feature');

        // Test that contextType is normalized from 'courses' to 'course'
        $this->assertEquals('course', $feature->contextType);
        $this->assertEquals($courseId, $feature->contextId);
    }

    public function testNullSafetyInStateChecking(): void
    {
        $feature = new FeatureFlag();
        
        // Test with non-string state in feature_flag
        $feature->feature_flag = ['state' => 123]; // Invalid type
        $feature->state = 'on';
        
        // Should fall back to direct state since nested is not a string
        $this->assertTrue($feature->isEnabled());
        
        // Test with missing state in feature_flag
        $feature->feature_flag = ['other' => 'value'];
        $feature->state = 'off';
        
        // Should use direct state
        $this->assertTrue($feature->isDisabled());
    }

    public function testHydratePropertiesWithPrecedence(): void
    {
        $feature = new FeatureFlag();
        
        // Set direct state first
        $feature->state = 'on';
        $feature->locked = true;
        
        $data = [
            'state' => 'on',  // Top level
            'locked' => true,
            'feature_flag' => [
                'state' => 'off',  // Nested - should not override
                'locked' => false
            ]
        ];
        
        // Use reflection to call protected method
        $reflection = new \ReflectionClass(FeatureFlag::class);
        $method = $reflection->getMethod('hydrateProperties');
        $method->setAccessible(true);
        $method->invoke(null, $feature, $data);
        
        // Top level values should be preserved
        $this->assertEquals('on', $feature->state);
        $this->assertTrue($feature->locked);
    }
}