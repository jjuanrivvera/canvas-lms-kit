<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Courses;

use CanvasLMS\Dto\Courses\CreateCourseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

class CreateCourseDTOTest extends TestCase
{
    // -----------------------------------------------------------------------
    // toApiArray() key format
    // -----------------------------------------------------------------------

    public function testToApiArrayKeysUseCourseBracketSnakeCase(): void
    {
        $dto = new CreateCourseDTO(['name' => 'Physics 101', 'course_code' => 'PHY-101']);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[name]', $result);
        $this->assertArrayHasKey('course[course_code]', $result);
        $this->assertArrayHasKey('course[license]', $result);
        $this->assertArrayHasKey('course[time_zone]', $result);
        $this->assertArrayHasKey('course[default_view]', $result);
    }

    public function testToApiArrayKeyForCamelCasePropertyIsSnakeCased(): void
    {
        $dto = new CreateCourseDTO(['name' => 'Bio']);
        $result = $this->indexByName($dto->toApiArray());

        // isPublicToAuthUsers → course[is_public_to_auth_users]
        $this->assertArrayHasKey('course[is_public_to_auth_users]', $result);
        $this->assertArrayHasKey('course[allow_student_wiki_edits]', $result);
        $this->assertArrayHasKey('course[restrict_enrollments_to_course_dates]', $result);
    }

    // -----------------------------------------------------------------------
    // toApiArray() null / empty string omission
    // -----------------------------------------------------------------------

    public function testNullPropertiesAreOmittedFromToApiArray(): void
    {
        $dto = new CreateCourseDTO(['name' => 'Test']);

        // Nullable properties that default to null
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayNotHasKey('course[start_at]', $result);
        $this->assertArrayNotHasKey('course[end_at]', $result);
        $this->assertArrayNotHasKey('course[public_description]', $result);
        $this->assertArrayNotHasKey('course[term_id]', $result);
        $this->assertArrayNotHasKey('course[sis_course_id]', $result);
        $this->assertArrayNotHasKey('course[integration_id]', $result);
        $this->assertArrayNotHasKey('course[syllabus_body]', $result);
        $this->assertArrayNotHasKey('course[grading_standard_id]', $result);
        $this->assertArrayNotHasKey('course[grade_passback_setting]', $result);
        $this->assertArrayNotHasKey('course[course_format]', $result);
    }

    public function testEmptyStringPropertyIsOmitted(): void
    {
        // courseCode defaults to '' and must be omitted
        $dto = new CreateCourseDTO(['name' => 'No Code']);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayNotHasKey('course[course_code]', $result);
    }

    // -----------------------------------------------------------------------
    // toApiArray() boolean handling
    // -----------------------------------------------------------------------

    public function testBooleanFalseIsIncludedAsNativePhpBool(): void
    {
        // CreateCourseDTO::toApiArray() does NOT use formatMultipartValue,
        // so booleans remain native PHP bool (not 'true'/'false' strings).
        $dto = new CreateCourseDTO(['name' => 'Test', 'is_public' => false]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[is_public]', $result);
        $this->assertSame('false', $result['course[is_public]']);
    }

    public function testBooleanTrueIsIncludedAsNativePhpBool(): void
    {
        $dto = new CreateCourseDTO(['name' => 'Test', 'is_public' => true]);
        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[is_public]', $result);
        $this->assertSame('true', $result['course[is_public]']);
    }

    public function testAllDefaultFalseBoolsAppearInOutput(): void
    {
        // All bool properties default to false, and false is NOT filtered out.
        $dto = new CreateCourseDTO(['name' => 'Bool Defaults']);
        $result = $this->indexByName($dto->toApiArray());

        foreach ([
            'course[is_public]',
            'course[is_public_to_auth_users]',
            'course[public_syllabus]',
            'course[public_syllabus_to_auth]',
            'course[allow_student_wiki_edits]',
            'course[allow_wiki_comments]',
            'course[allow_student_forum_attachments]',
            'course[open_enrollment]',
            'course[self_enrollment]',
            'course[restrict_enrollments_to_course_dates]',
            'course[hide_final_grades]',
            'course[apply_assignment_group_weights]',
            'course[offer]',
            'course[enroll_me]',
            'course[enable_sis_reactivation]',
            'course[post_manually]',
        ] as $key) {
            $this->assertArrayHasKey($key, $result, "Expected bool key '$key' to be present");
            $this->assertSame('false', $result[$key], "Expected '$key' to be 'false' by default");
        }
    }

    // -----------------------------------------------------------------------
    // toApiArray() DateTime serialization
    // -----------------------------------------------------------------------

    public function testDateTimePropertiesSerializeAsIso8601(): void
    {
        $startAt = new DateTime('2024-01-15T08:00:00Z');
        $endAt = new DateTime('2024-06-15T17:00:00Z');

        $dto = new CreateCourseDTO([
            'name' => 'Date Test',
            'restrict_enrollments_to_course_dates' => true,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $result = $this->indexByName($dto->toApiArray());

        $this->assertArrayHasKey('course[start_at]', $result);
        $this->assertArrayHasKey('course[end_at]', $result);

        // format('c') produces ISO 8601 with offset, e.g. 2024-01-15T08:00:00+00:00
        $this->assertSame($startAt->format('c'), $result['course[start_at]']);
        $this->assertSame($endAt->format('c'), $result['course[end_at]']);
    }

    // -----------------------------------------------------------------------
    // toApiArray() string / int content values
    // -----------------------------------------------------------------------

    public function testStringValuesArePreserved(): void
    {
        $dto = new CreateCourseDTO([
            'name' => 'My Course',
            'license' => 'cc_by',
            'time_zone' => 'America/New_York',
        ]);

        $result = $this->indexByName($dto->toApiArray());

        $this->assertSame('My Course', $result['course[name]']);
        $this->assertSame('cc_by', $result['course[license]']);
        $this->assertSame('America/New_York', $result['course[time_zone]']);
    }

    public function testIntValuesArePreserved(): void
    {
        $dto = new CreateCourseDTO([
            'name' => 'Graded Course',
            'term_id' => 42,
            'grading_standard_id' => 7,
        ]);

        $result = $this->indexByName($dto->toApiArray());

        $this->assertSame(42, $result['course[term_id]']);
        $this->assertSame(7, $result['course[grading_standard_id]']);
    }

    // -----------------------------------------------------------------------
    // Constructor / defaults
    // -----------------------------------------------------------------------

    public function testDefaultNameIsUnnamedCourse(): void
    {
        $dto = new CreateCourseDTO([]);
        $this->assertSame('Unnamed Course', $dto->name);
    }

    public function testConstructorPopulatesSnakeCaseKeys(): void
    {
        $dto = new CreateCourseDTO([
            'name' => 'Chem 101',
            'course_code' => 'CHEM-101',
            'license' => 'cc_by_nc',
            'is_public' => true,
            'time_zone' => 'UTC',
        ]);

        $this->assertSame('Chem 101', $dto->name);
        $this->assertSame('CHEM-101', $dto->courseCode);
        $this->assertSame('cc_by_nc', $dto->license);
        $this->assertTrue($dto->isPublic);
        $this->assertSame('UTC', $dto->timeZone);
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
