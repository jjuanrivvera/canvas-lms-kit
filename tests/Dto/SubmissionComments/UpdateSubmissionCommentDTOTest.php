<?php

namespace Tests\Dto\SubmissionComments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\SubmissionComments\UpdateSubmissionCommentDTO;

/**
 * @covers \CanvasLMS\Dto\SubmissionComments\UpdateSubmissionCommentDTO
 */
class UpdateSubmissionCommentDTOTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'text_comment' => 'Updated comment text with more details'
        ];

        $dto = new UpdateSubmissionCommentDTO($data);

        $this->assertEquals('Updated comment text with more details', $dto->getTextComment());
    }

    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateSubmissionCommentDTO([]);

        $this->assertNull($dto->getTextComment());
    }

    public function testGettersAndSetters(): void
    {
        $dto = new UpdateSubmissionCommentDTO([]);

        // Test text comment
        $dto->setTextComment('This is an updated comment');
        $this->assertEquals('This is an updated comment', $dto->getTextComment());

        // Test setting to null
        $dto->setTextComment(null);
        $this->assertNull($dto->getTextComment());

        // Test setting another value
        $dto->setTextComment('Another updated comment');
        $this->assertEquals('Another updated comment', $dto->getTextComment());
    }

    public function testSettersWithNullValues(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => 'Initial comment text'
        ]);

        // Verify initial value
        $this->assertEquals('Initial comment text', $dto->getTextComment());

        // Set to null
        $dto->setTextComment(null);
        $this->assertNull($dto->getTextComment());
    }

    public function testToApiArrayWithTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => 'Revised feedback after review'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Revised feedback after review'], $apiArray);
    }

    public function testToApiArrayWithNullTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertEmpty($apiArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => null
        ]);

        $apiArray = $dto->toApiArray();

        // Should be empty since text_comment is null
        $this->assertEmpty($apiArray);
    }

    public function testToArrayWithTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => 'Updated comment for array conversion'
        ]);

        $array = $dto->toArray();

        $this->assertEquals('Updated comment for array conversion', $array['textComment']);
    }

    public function testToArrayWithNullTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([]);

        $array = $dto->toArray();

        // Since textComment is null, it should not be included in the array
        $this->assertArrayNotHasKey('textComment', $array);
    }

    public function testSnakeCaseToApiPropertyConversion(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => 'Testing snake case conversion'
        ]);

        $apiArray = $dto->toApiArray();
        $fieldNames = array_column($apiArray, 'name');

        // Verify snake_case conversion in API property names
        $this->assertContains('comment[text_comment]', $fieldNames);
    }

    public function testEmptyStringTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => ''
        ]);

        $apiArray = $dto->toApiArray();

        // Empty string should still be included (different from null)
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => ''], $apiArray);
    }

    public function testWhitespaceOnlyTextComment(): void
    {
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => '   '
        ]);

        $apiArray = $dto->toApiArray();

        // Whitespace-only string should still be included
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => '   '], $apiArray);
    }

    public function testLongTextComment(): void
    {
        $longComment = str_repeat('This is a long comment. ', 100);
        
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $longComment
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $longComment], $apiArray);
        $this->assertEquals($longComment, $dto->getTextComment());
    }

    public function testSpecialCharactersInTextComment(): void
    {
        $specialComment = 'Comment with special chars: @#$%^&*()_+{}|:"<>?[];\'\\,./~`';
        
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $specialComment
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $specialComment], $apiArray);
        $this->assertEquals($specialComment, $dto->getTextComment());
    }

    public function testUnicodeCharactersInTextComment(): void
    {
        $unicodeComment = 'Comment with unicode: 🎉📚✅ español française 中文';
        
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $unicodeComment
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $unicodeComment], $apiArray);
        $this->assertEquals($unicodeComment, $dto->getTextComment());
    }

    public function testHtmlInTextComment(): void
    {
        $htmlComment = '<p>This is <strong>HTML</strong> content with <em>formatting</em></p>';
        
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $htmlComment
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $htmlComment], $apiArray);
        $this->assertEquals($htmlComment, $dto->getTextComment());
    }

    public function testNewlinesInTextComment(): void
    {
        $multilineComment = "First line\nSecond line\r\nThird line\n\nFifth line";
        
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $multilineComment
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $multilineComment], $apiArray);
        $this->assertEquals($multilineComment, $dto->getTextComment());
    }

    public function testUpdateScenario(): void
    {
        // Simulate updating a comment
        $originalComment = 'Original comment text';
        $updatedComment = 'This is the updated comment with corrections';
        
        // Create DTO with original text
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => $originalComment
        ]);
        
        $this->assertEquals($originalComment, $dto->getTextComment());
        
        // Update the comment
        $dto->setTextComment($updatedComment);
        
        $this->assertEquals($updatedComment, $dto->getTextComment());
        
        // Verify API array has updated content
        $apiArray = $dto->toApiArray();
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => $updatedComment], $apiArray);
    }
}