<?php

declare(strict_types=1);

namespace Tests\Objects;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Objects\Term;

/**
 * @covers \CanvasLMS\Objects\Term
 */
class TermTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $term = new Term();
        
        $this->assertNull($term->id);
        $this->assertNull($term->name);
        $this->assertNull($term->startAt);
        $this->assertNull($term->endAt);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Fall 2023',
            'start_at' => '2023-09-01T00:00:00Z',
            'end_at' => '2023-12-15T23:59:59Z'
        ];
        
        $term = new Term($data);
        
        $this->assertEquals(123, $term->id);
        $this->assertEquals('Fall 2023', $term->name);
        $this->assertEquals($data['start_at'], $term->startAt);
        $this->assertEquals($data['end_at'], $term->endAt);
    }

    public function testGettersAndSetters(): void
    {
        $term = new Term();
        
        $term->setId(456);
        $this->assertEquals(456, $term->getId());
        
        $term->setName('Spring 2024');
        $this->assertEquals('Spring 2024', $term->getName());
        
        $startAt = '2024-01-15T00:00:00Z';
        $term->setStartAt($startAt);
        $this->assertEquals($startAt, $term->getStartAt());
        
        $endAt = '2024-05-15T23:59:59Z';
        $term->setEndAt($endAt);
        $this->assertEquals($endAt, $term->getEndAt());
    }

    public function testHasStarted(): void
    {
        $term = new Term();
        
        // Always started when startAt is null
        $this->assertTrue($term->hasStarted());
        
        // Future date - not started
        $futureDate = date('c', strtotime('+1 month'));
        $term->setStartAt($futureDate);
        $this->assertFalse($term->hasStarted());
        
        // Past date - has started
        $pastDate = date('c', strtotime('-1 month'));
        $term->setStartAt($pastDate);
        $this->assertTrue($term->hasStarted());
    }

    public function testHasEnded(): void
    {
        $term = new Term();
        
        // Never ends when endAt is null
        $this->assertFalse($term->hasEnded());
        
        // Future date - not ended
        $futureDate = date('c', strtotime('+1 month'));
        $term->setEndAt($futureDate);
        $this->assertFalse($term->hasEnded());
        
        // Past date - has ended
        $pastDate = date('c', strtotime('-1 month'));
        $term->setEndAt($pastDate);
        $this->assertTrue($term->hasEnded());
    }

    public function testIsActive(): void
    {
        $term = new Term();
        
        // Active when no dates set (started but not ended)
        $this->assertTrue($term->isActive());
        
        // Not active - hasn't started yet
        $term->setStartAt(date('c', strtotime('+1 month')));
        $term->setEndAt(date('c', strtotime('+2 months')));
        $this->assertFalse($term->isActive());
        
        // Active - started but not ended
        $term->setStartAt(date('c', strtotime('-1 month')));
        $term->setEndAt(date('c', strtotime('+1 month')));
        $this->assertTrue($term->isActive());
        
        // Not active - has ended
        $term->setStartAt(date('c', strtotime('-2 months')));
        $term->setEndAt(date('c', strtotime('-1 month')));
        $this->assertFalse($term->isActive());
    }

    public function testToArray(): void
    {
        // Test with empty data
        $term = new Term();
        $this->assertEquals([], $term->toArray());
        
        // Test with partial data
        $term->setId(123);
        $term->setName('Fall 2023');
        
        $expected = [
            'id' => 123,
            'name' => 'Fall 2023'
        ];
        
        $this->assertEquals($expected, $term->toArray());
        
        // Test with all data
        $term->setStartAt('2023-09-01T00:00:00Z');
        $term->setEndAt('2023-12-15T23:59:59Z');
        
        $expected = [
            'id' => 123,
            'name' => 'Fall 2023',
            'start_at' => '2023-09-01T00:00:00Z',
            'end_at' => '2023-12-15T23:59:59Z'
        ];
        
        $this->assertEquals($expected, $term->toArray());
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'id' => 789,
            'name' => 'Summer 2024',
            'start_at' => '2024-06-01T00:00:00Z',
            'end_at' => '2024-08-15T23:59:59Z',
            'unknown_property' => 'should be ignored'
        ];
        
        $term = new Term($data);
        
        $this->assertEquals(789, $term->id);
        $this->assertEquals('Summer 2024', $term->name);
        $this->assertEquals($data['start_at'], $term->startAt);
        $this->assertEquals($data['end_at'], $term->endAt);
    }

    public function testSettersWithNull(): void
    {
        $term = new Term([
            'id' => 123,
            'name' => 'Test Term',
            'start_at' => '2023-01-01T00:00:00Z',
            'end_at' => '2023-12-31T23:59:59Z'
        ]);
        
        $term->setId(null);
        $term->setName(null);
        $term->setStartAt(null);
        $term->setEndAt(null);
        
        $this->assertNull($term->getId());
        $this->assertNull($term->getName());
        $this->assertNull($term->getStartAt());
        $this->assertNull($term->getEndAt());
    }
}