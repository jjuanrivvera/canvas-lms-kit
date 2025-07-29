<?php

declare(strict_types=1);

namespace Tests\Objects;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Objects\ContentDetails;

/**
 * @covers \CanvasLMS\Objects\ContentDetails
 */
class ContentDetailsTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $contentDetails = new ContentDetails();
        
        $this->assertNull($contentDetails->pointsPossible);
        $this->assertNull($contentDetails->dueAt);
        $this->assertNull($contentDetails->unlockAt);
        $this->assertNull($contentDetails->lockAt);
        $this->assertNull($contentDetails->lockedForUser);
        $this->assertNull($contentDetails->lockExplanation);
        $this->assertNull($contentDetails->lockInfo);
        $this->assertNull($contentDetails->lockContext);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'points_possible' => 100.0,
            'due_at' => '2023-12-15T23:59:59Z',
            'unlock_at' => '2023-12-01T00:00:00Z',
            'lock_at' => '2023-12-20T23:59:59Z',
            'locked_for_user' => true,
            'lock_explanation' => 'This content is locked until December 1st',
            'lock_info' => ['can_view' => false],
            'lock_context' => 'module'
        ];
        
        $contentDetails = new ContentDetails($data);
        
        $this->assertEquals(100.0, $contentDetails->pointsPossible);
        $this->assertEquals($data['due_at'], $contentDetails->dueAt);
        $this->assertEquals($data['unlock_at'], $contentDetails->unlockAt);
        $this->assertEquals($data['lock_at'], $contentDetails->lockAt);
        $this->assertTrue($contentDetails->lockedForUser);
        $this->assertEquals($data['lock_explanation'], $contentDetails->lockExplanation);
        $this->assertEquals($data['lock_info'], $contentDetails->lockInfo);
        $this->assertEquals('module', $contentDetails->lockContext);
    }

    public function testGettersAndSetters(): void
    {
        $contentDetails = new ContentDetails();
        
        $contentDetails->setPointsPossible(50.5);
        $this->assertEquals(50.5, $contentDetails->getPointsPossible());
        
        $dueAt = '2023-12-31T23:59:59Z';
        $contentDetails->setDueAt($dueAt);
        $this->assertEquals($dueAt, $contentDetails->getDueAt());
        
        $unlockAt = '2023-12-01T00:00:00Z';
        $contentDetails->setUnlockAt($unlockAt);
        $this->assertEquals($unlockAt, $contentDetails->getUnlockAt());
        
        $lockAt = '2024-01-15T23:59:59Z';
        $contentDetails->setLockAt($lockAt);
        $this->assertEquals($lockAt, $contentDetails->getLockAt());
        
        $contentDetails->setLockedForUser(false);
        $this->assertFalse($contentDetails->getLockedForUser());
        
        $explanation = 'Content is locked';
        $contentDetails->setLockExplanation($explanation);
        $this->assertEquals($explanation, $contentDetails->getLockExplanation());
        
        $lockInfo = ['can_view' => true, 'asset_string' => 'assignment_123'];
        $contentDetails->setLockInfo($lockInfo);
        $this->assertEquals($lockInfo, $contentDetails->getLockInfo());
        
        $contentDetails->setLockContext('assignment');
        $this->assertEquals('assignment', $contentDetails->getLockContext());
    }

    public function testIsLocked(): void
    {
        $contentDetails = new ContentDetails();
        
        // Not locked when lockedForUser is null or false
        $this->assertFalse($contentDetails->isLocked());
        
        $contentDetails->setLockedForUser(false);
        $this->assertFalse($contentDetails->isLocked());
        
        // Locked when lockedForUser is true
        $contentDetails->setLockedForUser(true);
        $this->assertTrue($contentDetails->isLocked());
    }

    public function testIsAvailable(): void
    {
        $contentDetails = new ContentDetails();
        
        // Always available when no unlock date
        $this->assertTrue($contentDetails->isAvailable());
        
        // Not available when unlock date is in the future
        $futureDate = date('c', strtotime('+1 day'));
        $contentDetails->setUnlockAt($futureDate);
        $this->assertFalse($contentDetails->isAvailable());
        
        // Available when unlock date is in the past
        $pastDate = date('c', strtotime('-1 day'));
        $contentDetails->setUnlockAt($pastDate);
        $this->assertTrue($contentDetails->isAvailable());
    }

    public function testIsExpired(): void
    {
        $contentDetails = new ContentDetails();
        
        // Not expired when no lock date
        $this->assertFalse($contentDetails->isExpired());
        
        // Not expired when lock date is in the future
        $futureDate = date('c', strtotime('+1 day'));
        $contentDetails->setLockAt($futureDate);
        $this->assertFalse($contentDetails->isExpired());
        
        // Expired when lock date is in the past
        $pastDate = date('c', strtotime('-1 day'));
        $contentDetails->setLockAt($pastDate);
        $this->assertTrue($contentDetails->isExpired());
    }

    public function testHasDeadline(): void
    {
        $contentDetails = new ContentDetails();
        
        // No deadline when dueAt is null
        $this->assertFalse($contentDetails->hasDeadline());
        
        // Has deadline when dueAt is set
        $contentDetails->setDueAt('2023-12-15T23:59:59Z');
        $this->assertTrue($contentDetails->hasDeadline());
    }

    public function testToArray(): void
    {
        // Test with empty data
        $contentDetails = new ContentDetails();
        $this->assertEquals([], $contentDetails->toArray());
        
        // Test with partial data
        $contentDetails->setPointsPossible(75.0);
        $contentDetails->setDueAt('2023-12-15T23:59:59Z');
        
        $expected = [
            'points_possible' => 75.0,
            'due_at' => '2023-12-15T23:59:59Z'
        ];
        
        $this->assertEquals($expected, $contentDetails->toArray());
        
        // Test with all data
        $contentDetails->setUnlockAt('2023-12-01T00:00:00Z');
        $contentDetails->setLockAt('2023-12-20T23:59:59Z');
        $contentDetails->setLockedForUser(true);
        $contentDetails->setLockExplanation('Locked for testing');
        $contentDetails->setLockInfo(['test' => 'data']);
        $contentDetails->setLockContext('module');
        
        $expected = [
            'points_possible' => 75.0,
            'due_at' => '2023-12-15T23:59:59Z',
            'unlock_at' => '2023-12-01T00:00:00Z',
            'lock_at' => '2023-12-20T23:59:59Z',
            'locked_for_user' => true,
            'lock_explanation' => 'Locked for testing',
            'lock_info' => ['test' => 'data'],
            'lock_context' => 'module'
        ];
        
        $this->assertEquals($expected, $contentDetails->toArray());
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'points_possible' => 100.0,
            'due_at' => '2023-12-15T23:59:59Z',
            'unlock_at' => '2023-12-01T00:00:00Z',
            'lock_at' => '2023-12-20T23:59:59Z',
            'locked_for_user' => true,
            'lock_explanation' => 'Test explanation',
            'lock_info' => ['key' => 'value'],
            'lock_context' => 'assignment',
            'unknown_property' => 'should be ignored'
        ];
        
        $contentDetails = new ContentDetails($data);
        
        $this->assertEquals(100.0, $contentDetails->pointsPossible);
        $this->assertEquals($data['due_at'], $contentDetails->dueAt);
        $this->assertEquals($data['unlock_at'], $contentDetails->unlockAt);
        $this->assertEquals($data['lock_at'], $contentDetails->lockAt);
        $this->assertTrue($contentDetails->lockedForUser);
        $this->assertEquals($data['lock_explanation'], $contentDetails->lockExplanation);
        $this->assertEquals($data['lock_info'], $contentDetails->lockInfo);
        $this->assertEquals('assignment', $contentDetails->lockContext);
    }

    public function testSettersWithNull(): void
    {
        $contentDetails = new ContentDetails([
            'points_possible' => 100.0,
            'due_at' => '2023-12-15T23:59:59Z',
            'unlock_at' => '2023-12-01T00:00:00Z',
            'lock_at' => '2023-12-20T23:59:59Z',
            'locked_for_user' => true,
            'lock_explanation' => 'Test',
            'lock_info' => ['test' => 'data'],
            'lock_context' => 'module'
        ]);
        
        $contentDetails->setPointsPossible(null);
        $contentDetails->setDueAt(null);
        $contentDetails->setUnlockAt(null);
        $contentDetails->setLockAt(null);
        $contentDetails->setLockedForUser(null);
        $contentDetails->setLockExplanation(null);
        $contentDetails->setLockInfo(null);
        $contentDetails->setLockContext(null);
        
        $this->assertNull($contentDetails->getPointsPossible());
        $this->assertNull($contentDetails->getDueAt());
        $this->assertNull($contentDetails->getUnlockAt());
        $this->assertNull($contentDetails->getLockAt());
        $this->assertNull($contentDetails->getLockedForUser());
        $this->assertNull($contentDetails->getLockExplanation());
        $this->assertNull($contentDetails->getLockInfo());
        $this->assertNull($contentDetails->getLockContext());
    }
}