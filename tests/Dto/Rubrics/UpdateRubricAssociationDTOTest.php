<?php

namespace Tests\Dto\Rubrics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Rubrics\UpdateRubricAssociationDTO;

class UpdateRubricAssociationDTOTest extends TestCase
{
    /**
     * Test DTO with basic update data
     */
    public function testBasicUpdateData(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = false;
        $dto->purpose = 'bookmark';
        $dto->hideScoreTotal = true;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Check use for grading
        $this->assertEquals('rubric_association[use_for_grading]', $result[0]['name']);
        $this->assertEquals('0', $result[0]['contents']);

        // Check purpose
        $this->assertEquals('rubric_association[purpose]', $result[1]['name']);
        $this->assertEquals('bookmark', $result[1]['contents']);

        // Check hide score total
        $this->assertEquals('rubric_association[hide_score_total]', $result[2]['name']);
        $this->assertEquals('1', $result[2]['contents']);
    }

    /**
     * Test DTO with all fields
     */
    public function testWithAllFields(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = true;
        $dto->purpose = 'grading';
        $dto->hideScoreTotal = false;
        $dto->hidePoints = true;
        $dto->hideOutcomeResults = false;
        $dto->bookmarked = true;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertCount(6, $result);

        $expectedFields = [
            ['name' => 'rubric_association[use_for_grading]', 'contents' => '1'],
            ['name' => 'rubric_association[purpose]', 'contents' => 'grading'],
            ['name' => 'rubric_association[hide_score_total]', 'contents' => '0'],
            ['name' => 'rubric_association[hide_points]', 'contents' => '1'],
            ['name' => 'rubric_association[hide_outcome_results]', 'contents' => '0'],
            ['name' => 'rubric_association[bookmarked]', 'contents' => '1']
        ];

        foreach ($expectedFields as $expected) {
            $this->assertContains($expected, $result);
        }
    }

    /**
     * Test DTO with null values
     */
    public function testWithNullValues(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = null;
        $dto->purpose = null;
        $dto->hideScoreTotal = null;
        $dto->hidePoints = null;
        $dto->hideOutcomeResults = null;
        $dto->bookmarked = null;

        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test DTO constructor with initial data
     */
    public function testConstructorWithData(): void
    {
        $data = [
            'use_for_grading' => false,
            'purpose' => 'bookmark',
            'hide_score_total' => true,
            'hide_points' => false,
            'hide_outcome_results' => true,
            'bookmarked' => false
        ];

        $dto = new UpdateRubricAssociationDTO($data);

        $this->assertFalse($dto->useForGrading);
        $this->assertEquals('bookmark', $dto->purpose);
        $this->assertTrue($dto->hideScoreTotal);
        $this->assertFalse($dto->hidePoints);
        $this->assertTrue($dto->hideOutcomeResults);
        $this->assertFalse($dto->bookmarked);
    }

    /**
     * Test empty DTO
     */
    public function testEmptyDTO(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test partial update
     */
    public function testPartialUpdate(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = true;
        // Leave other fields null

        $result = $dto->toApiArray();

        $this->assertCount(1, $result);
        $this->assertEquals('rubric_association[use_for_grading]', $result[0]['name']);
        $this->assertEquals('1', $result[0]['contents']);
    }

    /**
     * Test boolean conversion
     */
    public function testBooleanConversion(): void
    {
        // Test all true values
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = true;
        $dto->hideScoreTotal = true;
        $dto->hidePoints = true;
        $dto->hideOutcomeResults = true;
        $dto->bookmarked = true;

        $result = $dto->toApiArray();

        foreach ($result as $item) {
            if ($item['name'] !== 'rubric_association[purpose]') {
                $this->assertEquals('1', $item['contents']);
            }
        }

        // Test all false values
        $dto2 = new UpdateRubricAssociationDTO();
        $dto2->useForGrading = false;
        $dto2->hideScoreTotal = false;
        $dto2->hidePoints = false;
        $dto2->hideOutcomeResults = false;
        $dto2->bookmarked = false;

        $result2 = $dto2->toApiArray();

        foreach ($result2 as $item) {
            if ($item['name'] !== 'rubric_association[purpose]') {
                $this->assertEquals('0', $item['contents']);
            }
        }
    }

    /**
     * Test purpose values
     */
    public function testPurposeValues(): void
    {
        $purposes = ['grading', 'bookmark', null];

        foreach ($purposes as $purpose) {
            $dto = new UpdateRubricAssociationDTO();
            $dto->purpose = $purpose;

            $result = $dto->toApiArray();

            if ($purpose === null) {
                $this->assertEmpty($result);
            } else {
                $this->assertCount(1, $result);
                $this->assertEquals('rubric_association[purpose]', $result[0]['name']);
                $this->assertEquals($purpose, $result[0]['contents']);
            }
        }
    }

    /**
     * Test mixed update scenario
     */
    public function testMixedUpdateScenario(): void
    {
        $dto = new UpdateRubricAssociationDTO();
        $dto->useForGrading = false;
        $dto->purpose = 'bookmark';
        $dto->hideScoreTotal = null; // This should be excluded
        $dto->hidePoints = true;
        $dto->hideOutcomeResults = null; // This should be excluded
        $dto->bookmarked = false;

        $result = $dto->toApiArray();

        $this->assertCount(4, $result);

        // Check included fields
        $this->assertContains([
            'name' => 'rubric_association[use_for_grading]',
            'contents' => '0'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[purpose]',
            'contents' => 'bookmark'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[hide_points]',
            'contents' => '1'
        ], $result);

        $this->assertContains([
            'name' => 'rubric_association[bookmarked]',
            'contents' => '0'
        ], $result);

        // Ensure null fields are not included
        foreach ($result as $item) {
            $this->assertNotEquals('rubric_association[hide_score_total]', $item['name']);
            $this->assertNotEquals('rubric_association[hide_outcome_results]', $item['name']);
        }
    }
}