<?php

declare(strict_types=1);

namespace Tests\Dto\MediaObjects;

use CanvasLMS\Dto\MediaObjects\UpdateMediaTracksDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\MediaObjects\UpdateMediaTracksDTO
 */
class UpdateMediaTracksDTOTest extends TestCase
{
    /**
     * Test creating DTO with valid tracks
     */
    public function testCreateWithValidTracks(): void
    {
        $tracks = [
            ['locale' => 'en', 'content' => 'English content', 'kind' => 'subtitles'],
            ['locale' => 'es', 'content' => 'Spanish content', 'kind' => 'captions'],
        ];

        $dto = new UpdateMediaTracksDTO($tracks);

        $this->assertEquals($tracks, $dto->tracks);
        $this->assertEquals($tracks, $dto->toArray());
    }

    /**
     * Test creating DTO with empty tracks array
     */
    public function testCreateWithEmptyTracks(): void
    {
        $dto = new UpdateMediaTracksDTO([]);

        $this->assertEmpty($dto->tracks);
        $this->assertEmpty($dto->toArray());
    }

    /**
     * Test creating DTO with locale-only tracks
     */
    public function testCreateWithLocaleOnlyTracks(): void
    {
        $tracks = [
            ['locale' => 'en'],
            ['locale' => 'fr'],
        ];

        $dto = new UpdateMediaTracksDTO($tracks);

        $this->assertEquals($tracks, $dto->tracks);
    }

    /**
     * Test creating DTO without locale throws exception
     */
    public function testCreateWithoutLocaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 must have a locale');

        new UpdateMediaTracksDTO([
            ['content' => 'Some content', 'kind' => 'subtitles'],
        ]);
    }

    /**
     * Test creating DTO with empty locale throws exception
     */
    public function testCreateWithEmptyLocaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 must have a locale');

        new UpdateMediaTracksDTO([
            ['locale' => '', 'content' => 'Content'],
        ]);
    }

    /**
     * Test creating DTO with invalid track kind throws exception
     */
    public function testCreateWithInvalidKindThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 has invalid kind');

        new UpdateMediaTracksDTO([
            ['locale' => 'en', 'kind' => 'invalid_kind'],
        ]);
    }

    /**
     * Test all valid track kinds
     */
    public function testAllValidTrackKinds(): void
    {
        $validKinds = ['subtitles', 'captions', 'descriptions', 'chapters', 'metadata'];

        foreach ($validKinds as $kind) {
            $dto = new UpdateMediaTracksDTO([
                ['locale' => 'en', 'kind' => $kind],
            ]);

            $this->assertEquals($kind, $dto->tracks[0]['kind']);
        }
    }

    /**
     * Test creating DTO with empty content throws exception
     */
    public function testCreateWithEmptyContentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 has empty content');

        new UpdateMediaTracksDTO([
            ['locale' => 'en', 'content' => ''],
        ]);
    }

    /**
     * Test creating DTO with whitespace-only content throws exception
     */
    public function testCreateWithWhitespaceContentThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 has empty content');

        new UpdateMediaTracksDTO([
            ['locale' => 'en', 'content' => '   '],
        ]);
    }

    /**
     * Test locale format validation - valid formats
     */
    public function testValidLocaleFormats(): void
    {
        $validLocales = [
            'en',
            'es',
            'fr',
            'de',
            'ja',
            'zh',
            'en-US',
            'es-MX',
            'fr-CA',
            'pt-BR',
        ];

        foreach ($validLocales as $locale) {
            $dto = new UpdateMediaTracksDTO([
                ['locale' => $locale],
            ]);

            $this->assertEquals($locale, $dto->tracks[0]['locale']);
        }
    }

    /**
     * Test locale format validation - invalid formats
     */
    public function testInvalidLocaleFormats(): void
    {
        $invalidLocales = [
            'eng',      // Too long
            'e',        // Too short
            'EN',       // Wrong case
            'en_US',    // Wrong separator
            'en-us',    // Wrong case for country
            '123',      // Numbers
            'en-USA',   // Country code too long
        ];

        foreach ($invalidLocales as $locale) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('invalid locale format');

            new UpdateMediaTracksDTO([
                ['locale' => $locale],
            ]);
        }
    }

    /**
     * Test non-array track throws exception
     */
    public function testNonArrayTrackThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Track at index 0 must be an array');

        new UpdateMediaTracksDTO(['not_an_array']);
    }

    /**
     * Test fromArray with tracks key
     */
    public function testFromArrayWithTracksKey(): void
    {
        $data = [
            'tracks' => [
                ['locale' => 'en', 'content' => 'English'],
                ['locale' => 'es', 'content' => 'Spanish'],
            ],
        ];

        $dto = UpdateMediaTracksDTO::fromArray($data);

        $this->assertCount(2, $dto->tracks);
        $this->assertEquals('en', $dto->tracks[0]['locale']);
        $this->assertEquals('es', $dto->tracks[1]['locale']);
    }

    /**
     * Test fromArray without tracks key
     */
    public function testFromArrayWithoutTracksKey(): void
    {
        $data = [
            ['locale' => 'fr', 'content' => 'French'],
            ['locale' => 'de', 'content' => 'German'],
        ];

        $dto = UpdateMediaTracksDTO::fromArray($data);

        $this->assertCount(2, $dto->tracks);
        $this->assertEquals('fr', $dto->tracks[0]['locale']);
        $this->assertEquals('de', $dto->tracks[1]['locale']);
    }

    /**
     * Test addTrack method
     */
    public function testAddTrack(): void
    {
        $dto = new UpdateMediaTracksDTO();

        $dto->addTrack('en', 'English content', 'subtitles');
        $dto->addTrack('es', 'Spanish content', 'captions');
        $dto->addTrack('fr'); // No content or kind

        $this->assertCount(3, $dto->tracks);
        $this->assertEquals('en', $dto->tracks[0]['locale']);
        $this->assertEquals('English content', $dto->tracks[0]['content']);
        $this->assertEquals('subtitles', $dto->tracks[0]['kind']);
        $this->assertEquals('fr', $dto->tracks[2]['locale']);
        $this->assertArrayNotHasKey('content', $dto->tracks[2]);
    }

    /**
     * Test addTrack with invalid kind throws exception
     */
    public function testAddTrackWithInvalidKindThrowsException(): void
    {
        $dto = new UpdateMediaTracksDTO();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid kind');

        $dto->addTrack('en', 'Content', 'invalid_kind');
    }

    /**
     * Test clearTracks method
     */
    public function testClearTracks(): void
    {
        $dto = new UpdateMediaTracksDTO([
            ['locale' => 'en'],
            ['locale' => 'es'],
        ]);

        $this->assertCount(2, $dto->tracks);

        $dto->clearTracks();

        $this->assertEmpty($dto->tracks);
        $this->assertEmpty($dto->toArray());
    }

    /**
     * Test method chaining
     */
    public function testMethodChaining(): void
    {
        $dto = new UpdateMediaTracksDTO();

        $result = $dto
            ->addTrack('en', 'English')
            ->addTrack('es', 'Spanish')
            ->clearTracks()
            ->addTrack('fr', 'French');

        $this->assertInstanceOf(UpdateMediaTracksDTO::class, $result);
        $this->assertCount(1, $dto->tracks);
        $this->assertEquals('fr', $dto->tracks[0]['locale']);
    }

    /**
     * Test toArray returns direct tracks array
     */
    public function testToArrayReturnsDirectTracksArray(): void
    {
        $tracks = [
            ['locale' => 'en', 'content' => 'Test'],
            ['locale' => 'es'],
        ];

        $dto = new UpdateMediaTracksDTO($tracks);

        $array = $dto->toArray();

        $this->assertEquals($tracks, $array);
        $this->assertNotEquals(['tracks' => $tracks], $array); // Should not wrap in 'tracks' key
    }

    /**
     * Test complex WEBVTT content
     */
    public function testComplexWebvttContent(): void
    {
        $webvttContent = "WEBVTT\n\n1\n00:00:00.000 --> 00:00:05.000\nHello World\n\n2\n00:00:05.000 --> 00:00:10.000\nThis is a test";

        $dto = new UpdateMediaTracksDTO([
            ['locale' => 'en', 'content' => $webvttContent, 'kind' => 'captions'],
        ]);

        $this->assertEquals($webvttContent, $dto->tracks[0]['content']);
    }
}
