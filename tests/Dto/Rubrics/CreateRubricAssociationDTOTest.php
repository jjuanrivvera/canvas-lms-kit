<?php

namespace Tests\Dto\Rubrics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Rubrics\CreateRubricAssociationDTO;

class CreateRubricAssociationDTOTest extends TestCase
{
    /**
     * Test DTO with basic association data
     */
    public function testBasicAssociationData(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 123;
        $dto->associationId = 456;
        $dto->associationType = 'Assignment';
        $dto->useForGrading = true;
        $dto->purpose = 'grading';

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(5, $result);

        // Check rubric ID
        $this->assertEquals('rubric_association[rubric_id]', $result[0]['name']);
        $this->assertEquals('123', $result[0]['contents']);

        // Check association ID
        $this->assertEquals('rubric_association[association_id]', $result[1]['name']);
        $this->assertEquals('456', $result[1]['contents']);

        // Check association type
        $this->assertEquals('rubric_association[association_type]', $result[2]['name']);
        $this->assertEquals('Assignment', $result[2]['contents']);

        // Check use for grading
        $this->assertEquals('rubric_association[use_for_grading]', $result[3]['name']);
        $this->assertEquals('1', $result[3]['contents']);

        // Check purpose
        $this->assertEquals('rubric_association[purpose]', $result[4]['name']);
        $this->assertEquals('grading', $result[4]['contents']);
    }

    /**
     * Test DTO with all fields
     */
    public function testWithAllFields(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 789;
        $dto->associationId = 999;
        $dto->associationType = 'Discussion';
        $dto->useForGrading = false;
        $dto->purpose = 'bookmark';
        $dto->hideScoreTotal = true;
        $dto->hidePoints = false;
        $dto->hideOutcomeResults = true;
        $dto->bookmarked = true;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(9, $result);

        // Check all fields
        $expectedFields = [
            ['name' => 'rubric_association[rubric_id]', 'contents' => '789'],
            ['name' => 'rubric_association[association_id]', 'contents' => '999'],
            ['name' => 'rubric_association[association_type]', 'contents' => 'Discussion'],
            ['name' => 'rubric_association[use_for_grading]', 'contents' => '0'],
            ['name' => 'rubric_association[purpose]', 'contents' => 'bookmark'],
            ['name' => 'rubric_association[hide_score_total]', 'contents' => '1'],
            ['name' => 'rubric_association[hide_points]', 'contents' => '0'],
            ['name' => 'rubric_association[hide_outcome_results]', 'contents' => '1'],
            ['name' => 'rubric_association[bookmarked]', 'contents' => '1']
        ];

        foreach ($expectedFields as $expected) {
            $this->assertContains($expected, $result);
        }
    }

    /**
     * Test DTO with minimal required fields
     */
    public function testMinimalRequiredFields(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 111;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $this->assertEquals('rubric_association[rubric_id]', $result[0]['name']);
        $this->assertEquals('111', $result[0]['contents']);
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 222;
        $dto->associationId = null;
        $dto->associationType = null;
        $dto->useForGrading = null;
        $dto->purpose = null;
        $dto->hideScoreTotal = null;
        $dto->hidePoints = null;
        $dto->hideOutcomeResults = null;
        $dto->bookmarked = null;

        $result = $dto->toApiArray();

        // Only rubric_id should be included
        $this->assertCount(1, $result);
        $this->assertEquals('rubric_association[rubric_id]', $result[0]['name']);
        $this->assertEquals('222', $result[0]['contents']);
    }

    /**
     * Test DTO constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'rubric_id' => 333,
            'association_id' => 444,
            'association_type' => 'Assignment',
            'use_for_grading' => true,
            'purpose' => 'grading',
            'hide_score_total' => false,
            'hide_points' => true,
            'hide_outcome_results' => false,
            'bookmarked' => false
        ];

        $dto = new CreateRubricAssociationDTO($data);

        $this->assertEquals(333, $dto->rubricId);
        $this->assertEquals(444, $dto->associationId);
        $this->assertEquals('Assignment', $dto->associationType);
        $this->assertTrue($dto->useForGrading);
        $this->assertEquals('grading', $dto->purpose);
        $this->assertFalse($dto->hideScoreTotal);
        $this->assertTrue($dto->hidePoints);
        $this->assertFalse($dto->hideOutcomeResults);
        $this->assertFalse($dto->bookmarked);
    }

    /**
     * Test empty DTO
     */
    public function testEmptyDTO(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test boolean conversion
     */
    public function testBooleanConversion(): void
    {
        $dto = new CreateRubricAssociationDTO();
        $dto->rubricId = 555;
        $dto->useForGrading = false;
        $dto->hideScoreTotal = false;
        $dto->hidePoints = true;
        $dto->hideOutcomeResults = true;
        $dto->bookmarked = false;

        $result = $dto->toApiArray();

        // Check boolean to string conversion
        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_score_total]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_outcome_results]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[bookmarked]',
            'contents' => '0'
        ], $result);
    }

    /**
     * Test association types
     */
    public function testAssociationTypes(): void
    {
        $types = ['Assignment', 'Discussion', 'Wiki', 'Quiz'];

        foreach ($types as $type) {
            $dto = new CreateRubricAssociationDTO();
            $dto->rubricId = 666;
            $dto->associationType = $type;

            $result = $dto->toApiArray();

            $this->assertContains([
                'name' => 'rubric_association[association_type]',
                'contents' => $type
            ], $result);
        }
    }

    /**
     * Test purpose values
     */
    public function testPurposeValues(): void
    {
        $purposes = ['grading', 'bookmark'];

        foreach ($purposes as $purpose) {
            $dto = new CreateRubricAssociationDTO();
            $dto->rubricId = 777;
            $dto->purpose = $purpose;

            $result = $dto->toApiArray();

            $this->assertContains([
                'name' => 'rubric_association[purpose]',
                'contents' => $purpose
            ], $result);
        }
    }
}