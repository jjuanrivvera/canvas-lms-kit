<?php

declare(strict_types=1);

namespace Tests\Dto\Quizzes;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Quizzes\CreateQuizSubmissionDTO;

/**
 * @covers \CanvasLMS\Dto\Quizzes\CreateQuizSubmissionDTO
 */
class CreateQuizSubmissionDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateQuizSubmissionDTO([]);

        $this->assertNull($dto->getAccessCode());
        $this->assertNull($dto->getPreview());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'access_code' => 'secret123',
            'preview' => true
        ];

        $dto = new CreateQuizSubmissionDTO($data);

        $this->assertEquals('secret123', $dto->getAccessCode());
        $this->assertTrue($dto->getPreview());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateQuizSubmissionDTO([]);

        $dto->setAccessCode('password456');
        $this->assertEquals('password456', $dto->getAccessCode());

        $dto->setPreview(false);
        $this->assertFalse($dto->getPreview());

        $dto->setAccessCode(null);
        $this->assertNull($dto->getAccessCode());

        $dto->setPreview(null);
        $this->assertNull($dto->getPreview());
    }

    public function testToApiArray(): void
    {
        $dto = new CreateQuizSubmissionDTO([
            'access_code' => 'secret123',
            'preview' => true
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertNotEmpty($apiArray);

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[access_code]', $names);
        $this->assertContains('quiz_submission[preview]', $names);

        // Find the preview entry and check boolean conversion
        $previewEntry = null;
        foreach ($apiArray as $entry) {
            if ($entry['name'] === 'quiz_submission[preview]') {
                $previewEntry = $entry;
                break;
            }
        }
        $this->assertNotNull($previewEntry);
        $this->assertEquals('1', $previewEntry['contents']); // true converts to '1'
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateQuizSubmissionDTO(['access_code' => 'test']);
        $apiArray = $dto->toApiArray();

        // Verify that all entries use the 'quiz_submission' prefix
        foreach ($apiArray as $entry) {
            $this->assertStringStartsWith('quiz_submission[', $entry['name']);
        }
    }

    public function testNullValuesAreFilteredOut(): void
    {
        $dto = new CreateQuizSubmissionDTO([
            'access_code' => 'secret123',
            'preview' => null
        ]);

        $apiArray = $dto->toApiArray();

        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[access_code]', $names);
        $this->assertNotContains('quiz_submission[preview]', $names);
    }

    public function testEmptyStringValues(): void
    {
        $dto = new CreateQuizSubmissionDTO([
            'access_code' => '',
            'preview' => false
        ]);

        $apiArray = $dto->toApiArray();

        // Empty strings should be included (they're not null)
        $names = array_column($apiArray, 'name');
        $this->assertContains('quiz_submission[access_code]', $names);
        $this->assertContains('quiz_submission[preview]', $names);

        // Find the access_code entry and verify empty string
        $accessCodeEntry = null;
        foreach ($apiArray as $entry) {
            if ($entry['name'] === 'quiz_submission[access_code]') {
                $accessCodeEntry = $entry;
                break;
            }
        }
        $this->assertNotNull($accessCodeEntry);
        $this->assertEquals('', $accessCodeEntry['contents']);
    }

    public function testBooleanValuesConvertedCorrectly(): void
    {
        $dto = new CreateQuizSubmissionDTO([
            'preview' => true
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('1', $entryMap['quiz_submission[preview]']);

        // Test false value
        $dto = new CreateQuizSubmissionDTO([
            'preview' => false
        ]);

        $apiArray = $dto->toApiArray();

        $entryMap = [];
        foreach ($apiArray as $entry) {
            $entryMap[$entry['name']] = $entry['contents'];
        }

        $this->assertEquals('0', $entryMap['quiz_submission[preview]']);
    }
}