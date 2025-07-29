<?php

declare(strict_types=1);

namespace Tests\Objects;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Objects\CompletionRequirement;

/**
 * @covers \CanvasLMS\Objects\CompletionRequirement
 */
class CompletionRequirementTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $requirement = new CompletionRequirement();
        
        $this->assertNull($requirement->type);
        $this->assertNull($requirement->minScore);
        $this->assertNull($requirement->minPercentage);
        $this->assertNull($requirement->completed);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'type' => 'min_score',
            'min_score' => 80.5,
            'min_percentage' => 75.0,
            'completed' => true
        ];
        
        $requirement = new CompletionRequirement($data);
        
        $this->assertEquals('min_score', $requirement->type);
        $this->assertEquals(80.5, $requirement->minScore);
        $this->assertEquals(75.0, $requirement->minPercentage);
        $this->assertTrue($requirement->completed);
    }

    public function testGettersAndSetters(): void
    {
        $requirement = new CompletionRequirement();
        
        $requirement->setType('must_view');
        $this->assertEquals('must_view', $requirement->getType());
        
        $requirement->setMinScore(90.0);
        $this->assertEquals(90.0, $requirement->getMinScore());
        
        $requirement->setMinPercentage(85.5);
        $this->assertEquals(85.5, $requirement->getMinPercentage());
        
        $requirement->setCompleted(false);
        $this->assertFalse($requirement->getCompleted());
    }

    public function testIsScoreBased(): void
    {
        $requirement = new CompletionRequirement();
        
        // Not score-based when type is null
        $this->assertFalse($requirement->isScoreBased());
        
        // Not score-based with other types
        $requirement->setType('must_view');
        $this->assertFalse($requirement->isScoreBased());
        
        $requirement->setType('must_submit');
        $this->assertFalse($requirement->isScoreBased());
        
        // Score-based when type is min_score
        $requirement->setType('min_score');
        $this->assertTrue($requirement->isScoreBased());
    }

    public function testIsPercentageBased(): void
    {
        $requirement = new CompletionRequirement();
        
        // Not percentage-based when type is null
        $this->assertFalse($requirement->isPercentageBased());
        
        // Not percentage-based with other types
        $requirement->setType('must_view');
        $this->assertFalse($requirement->isPercentageBased());
        
        $requirement->setType('min_score');
        $this->assertFalse($requirement->isPercentageBased());
        
        // Percentage-based when type is min_percentage
        $requirement->setType('min_percentage');
        $this->assertTrue($requirement->isPercentageBased());
    }

    public function testToArray(): void
    {
        // Test with minimal data
        $requirement = new CompletionRequirement();
        $requirement->setType('must_view');
        
        $expected = [
            'type' => 'must_view'
        ];
        
        $this->assertEquals($expected, $requirement->toArray());
        
        // Test with min_score
        $requirement = new CompletionRequirement();
        $requirement->setType('min_score');
        $requirement->setMinScore(75.0);
        
        $expected = [
            'type' => 'min_score',
            'min_score' => 75.0
        ];
        
        $this->assertEquals($expected, $requirement->toArray());
        
        // Test with min_percentage
        $requirement = new CompletionRequirement();
        $requirement->setType('min_percentage');
        $requirement->setMinPercentage(80.0);
        
        $expected = [
            'type' => 'min_percentage',
            'min_percentage' => 80.0
        ];
        
        $this->assertEquals($expected, $requirement->toArray());
        
        // Test with completed status
        $requirement = new CompletionRequirement();
        $requirement->setType('must_submit');
        $requirement->setCompleted(true);
        
        $expected = [
            'type' => 'must_submit',
            'completed' => true
        ];
        
        $this->assertEquals($expected, $requirement->toArray());
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'type' => 'min_score',
            'min_score' => 85.0,
            'min_percentage' => 90.0,
            'completed' => false,
            'unknown_property' => 'should be ignored'
        ];
        
        $requirement = new CompletionRequirement($data);
        
        $this->assertEquals('min_score', $requirement->type);
        $this->assertEquals(85.0, $requirement->minScore);
        $this->assertEquals(90.0, $requirement->minPercentage);
        $this->assertFalse($requirement->completed);
    }

    public function testDifferentCompletionTypes(): void
    {
        $types = [
            'must_view',
            'must_submit',
            'must_contribute',
            'min_score',
            'min_percentage',
            'must_mark_done'
        ];
        
        foreach ($types as $type) {
            $requirement = new CompletionRequirement(['type' => $type]);
            $this->assertEquals($type, $requirement->type);
        }
    }

    public function testSettersWithNull(): void
    {
        $requirement = new CompletionRequirement([
            'type' => 'min_score',
            'min_score' => 80.0,
            'min_percentage' => 75.0,
            'completed' => true
        ]);
        
        $requirement->setType(null);
        $requirement->setMinScore(null);
        $requirement->setMinPercentage(null);
        $requirement->setCompleted(null);
        
        $this->assertNull($requirement->getType());
        $this->assertNull($requirement->getMinScore());
        $this->assertNull($requirement->getMinPercentage());
        $this->assertNull($requirement->getCompleted());
    }
}