<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Courses;

use CanvasLMS\Dto\Courses\UpdateCourseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

class UpdateCourseDTOTest extends TestCase
{
    // -----------------------------------------------------------------------
    // toApiArray() key format
    // -----------------------------------------------------------------------

    public function testToApiArrayKeysUseCourseBracketSnakeCase(): void
    {
        $dto = new UpdateCourseDTO(['name' => 'Updated Course', 'course_code' => 'UPD-101']);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[name]', $result);
        $this->assertArrayHasKey('course[course_code]', $result);
        $this->assertArrayHasKey('course[default_view]', $result);
    }

    public function testToApiArrayCamelCasePropertiesAreSnakeCased(): void
    {
        $dto = new UpdateCourseDTO([
            'name' => 'Test',
            'hide_final_grades' => true,
            'apply_assignment_group_weights' => true,
            'storage_quota_mb' => 500,
        ]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[hide_final_grades]', $result);
        $this->assertArrayHasKey('course[apply_assignment_group_weights]', $result);
        $this->assertArrayHasKey('course[storage_quota_mb]', $result);
    }

    // -----------------------------------------------------------------------
    // toApiArray() null omission
    // -----------------------------------------------------------------------

    public function testNullPropertiesAreOmittedFromToApiArray(): void
    {
        // UpdateCourseDTO has all-nullable properties. With no values set, only
        // defaultView ('syllabus') should appear.
        $dto = new UpdateCourseDTO([]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayNotHasKey('course[name]', $result);
        $this->assertArrayNotHasKey('course[course_code]', $result);
        $this->assertArrayNotHasKey('course[account_id]', $result);
        $this->assertArrayNotHasKey('course[start_at]', $result);
        $this->assertArrayNotHasKey('course[end_at]', $result);
        $this->assertArrayNotHasKey('course[license]', $result);
        $this->assertArrayNotHasKey('course[is_public]', $result);
        $this->assertArrayNotHasKey('course[term_id]', $result);
        $this->assertArrayNotHasKey('course[sis_course_id]', $result);
        $this->assertArrayNotHasKey('course[image_id]', $result);
        $this->assertArrayNotHasKey('course[image_url]', $result);
    }

    public function testDefaultViewAlwaysAppearsEvenWithNoOtherProperties(): void
    {
        // defaultView has a non-null default ('syllabus') so it always appears.
        $dto = new UpdateCourseDTO([]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[default_view]', $result);
        $this->assertSame('syllabus', $result['course[default_view]']);
    }

    // -----------------------------------------------------------------------
    // toApiArray() boolean handling
    // -----------------------------------------------------------------------

    public function testBooleanTrueIsIncludedAsNativePhpBool(): void
    {
        $dto = new UpdateCourseDTO(['name' => 'Test', 'is_public' => true]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[is_public]', $result);
        $this->assertSame('true', $result['course[is_public]']);
    }

    public function testBooleanFalseIsIncludedAsNativePhpBool(): void
    {
        $dto = new UpdateCourseDTO(['name' => 'Test', 'hide_final_grades' => false]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[hide_final_grades]', $result);
        $this->assertSame('false', $result['course[hide_final_grades]']);
    }

    public function testNullBooleanIsOmitted(): void
    {
        // In UpdateCourseDTO all bool properties are nullable. If null → excluded.
        $dto = new UpdateCourseDTO(['name' => 'Test']);
        // is_public defaults to null
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayNotHasKey('course[is_public]', $result);
        $this->assertArrayNotHasKey('course[blueprint]', $result);
        $this->assertArrayNotHasKey('course[template]', $result);
    }

    // -----------------------------------------------------------------------
    // toApiArray() DateTime serialization
    // -----------------------------------------------------------------------

    public function testDateTimePropertiesSerializeAsIso8601(): void
    {
        $startAt = new DateTime('2024-03-01T09:00:00Z');
        $endAt = new DateTime('2024-12-15T23:59:59Z');

        $dto = new UpdateCourseDTO([
            'name' => 'Dated Course',
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[start_at]', $result);
        $this->assertArrayHasKey('course[end_at]', $result);
        $this->assertSame($startAt->format('c'), $result['course[start_at]']);
        $this->assertSame($endAt->format('c'), $result['course[end_at]']);
    }

    // -----------------------------------------------------------------------
    // toApiArray() array property
    // -----------------------------------------------------------------------

    public function testArrayPropertyIsIncludedDirectlyAsArray(): void
    {
        // UpdateCourseDTO::toApiArray() puts array values directly into
        // 'contents' without flattening — unlike AbstractBaseDto::toApiArray().
        $restrictions = ['assignment' => ['content' => true], 'quiz' => ['content' => false]];
        $dto = new UpdateCourseDTO([
            'name' => 'Blueprint',
            'blueprint_restrictions_by_object_type' => $restrictions,
        ]);

        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[blueprint_restrictions_by_object_type]', $result);
        $this->assertSame($restrictions, $result['course[blueprint_restrictions_by_object_type]']);
    }

    // -----------------------------------------------------------------------
    // Constructor / defaults
    // -----------------------------------------------------------------------

    public function testDefaultViewIsSetToSyllabus(): void
    {
        $dto = new UpdateCourseDTO([]);
        $this->assertSame('syllabus', $dto->defaultView);
    }

    public function testAllNullablePropertiesDefaultToNull(): void
    {
        $dto = new UpdateCourseDTO([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->courseCode);
        $this->assertNull($dto->startAt);
        $this->assertNull($dto->endAt);
        $this->assertNull($dto->license);
        $this->assertNull($dto->isPublic);
        $this->assertNull($dto->termId);
        $this->assertNull($dto->sisCourseId);
        $this->assertNull($dto->imageId);
        $this->assertNull($dto->imageUrl);
        $this->assertNull($dto->blueprint);
        $this->assertNull($dto->template);
        $this->assertNull($dto->postManually);
    }

    public function testConstructorPopulatesSnakeCaseKeys(): void
    {
        $dto = new UpdateCourseDTO([
            'name' => 'Updated',
            'course_code' => 'UPD-001',
            'license' => 'cc_by_sa',
            'is_public' => true,
            'term_id' => 5,
            'storage_quota_mb' => 1024,
            'event' => 'offer',
            'default_view' => 'modules',
        ]);

        $this->assertSame('Updated', $dto->name);
        $this->assertSame('UPD-001', $dto->courseCode);
        $this->assertSame('cc_by_sa', $dto->license);
        $this->assertTrue($dto->isPublic);
        $this->assertSame(5, $dto->termId);
        $this->assertSame(1024, $dto->storageQuotaMb);
        $this->assertSame('offer', $dto->event);
        $this->assertSame('modules', $dto->defaultView);
    }

    // -----------------------------------------------------------------------
    // setCourseColor validation
    // -----------------------------------------------------------------------

    public function testSetCourseColorAcceptsValidHexWithHash(): void
    {
        $dto = new UpdateCourseDTO([]);
        $dto->setCourseColor('#ff0000');

        $this->assertSame('#ff0000', $dto->courseColor);
    }

    public function testSetCourseColorAcceptsValidHexWithoutHash(): void
    {
        $dto = new UpdateCourseDTO([]);
        $dto->setCourseColor('aabbcc');

        $this->assertSame('aabbcc', $dto->courseColor);
    }

    public function testSetCourseColorThrowsOnInvalidFormat(): void
    {
        $dto = new UpdateCourseDTO([]);

        $this->expectException(\InvalidArgumentException::class);
        $dto->setCourseColor('not-a-color');
    }

    public function testSetCourseColorAcceptsNull(): void
    {
        $dto = new UpdateCourseDTO([]);
        $dto->setCourseColor(null);

        $this->assertNull($dto->courseColor);
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    /**
     * Index a multipart-format array by name for easy keyed lookup.
     *
     * @param array<int, array{name: string, contents: mixed}> $apiArray
     *
     * @return array<string, mixed>
     */
    private function indexByName(array $apiArray): array
    {
        $indexed = [];
        foreach ($apiArray as $item) {
            $indexed[$item['name']] = $item['contents'];
        }

        return $indexed;
    }
}
