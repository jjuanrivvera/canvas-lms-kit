<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\MediaTrack;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\MediaTrack
 */
class MediaTrackTest extends TestCase
{
    /**
     * Test creating MediaTrack with all properties
     */
    public function testCreateWithAllProperties(): void
    {
        $data = [
            'id' => 42,
            'user_id' => 100,
            'media_object_id' => 'm-test123',
            'kind' => 'subtitles',
            'locale' => 'en',
            'content' => 'Test subtitle content',
            'webvtt_content' => 'WEBVTT\n\n1\n00:00:00.000 --> 00:00:05.000\nTest',
            'url' => 'https://example.com/track',
            'created_at' => '2024-01-15T10:00:00Z',
            'updated_at' => '2024-01-15T11:00:00Z'
        ];

        $track = new MediaTrack($data);

        $this->assertEquals(42, $track->id);
        $this->assertEquals(100, $track->userId);
        $this->assertEquals('m-test123', $track->mediaObjectId);
        $this->assertEquals('subtitles', $track->kind);
        $this->assertEquals('en', $track->locale);
        $this->assertEquals('Test subtitle content', $track->content);
        $this->assertEquals('WEBVTT\n\n1\n00:00:00.000 --> 00:00:05.000\nTest', $track->webvttContent);
        $this->assertEquals('https://example.com/track', $track->url);
        $this->assertEquals('2024-01-15T10:00:00Z', $track->createdAt);
        $this->assertEquals('2024-01-15T11:00:00Z', $track->updatedAt);
    }

    /**
     * Test creating MediaTrack with empty data
     */
    public function testCreateWithEmptyData(): void
    {
        $track = new MediaTrack();

        $this->assertNull($track->id);
        $this->assertNull($track->userId);
        $this->assertNull($track->mediaObjectId);
        $this->assertNull($track->kind);
        $this->assertNull($track->locale);
        $this->assertNull($track->content);
        $this->assertNull($track->webvttContent);
        $this->assertNull($track->url);
        $this->assertNull($track->createdAt);
        $this->assertNull($track->updatedAt);
    }

    /**
     * Test snake_case to camelCase conversion
     */
    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'user_id' => 200,
            'media_object_id' => 'm-abc456',
            'webvtt_content' => 'WEBVTT content',
            'created_at' => '2024-01-01',
            'updated_at' => '2024-01-02'
        ];

        $track = new MediaTrack($data);

        $this->assertEquals(200, $track->userId);
        $this->assertEquals('m-abc456', $track->mediaObjectId);
        $this->assertEquals('WEBVTT content', $track->webvttContent);
        $this->assertEquals('2024-01-01', $track->createdAt);
        $this->assertEquals('2024-01-02', $track->updatedAt);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $track = new MediaTrack([
            'id' => 10,
            'user_id' => 50,
            'media_object_id' => 'm-xyz',
            'kind' => 'captions',
            'locale' => 'es',
            'content' => 'Spanish captions',
            'url' => 'https://example.com/es'
        ]);

        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(10, $array['id']);
        $this->assertEquals(50, $array['user_id']);
        $this->assertEquals('m-xyz', $array['media_object_id']);
        $this->assertEquals('captions', $array['kind']);
        $this->assertEquals('es', $array['locale']);
        $this->assertEquals('Spanish captions', $array['content']);
        $this->assertEquals('https://example.com/es', $array['url']);
        
        // Should not include null values
        $this->assertArrayNotHasKey('webvtt_content', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    /**
     * Test toArray with all null values
     */
    public function testToArrayWithAllNullValues(): void
    {
        $track = new MediaTrack();

        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    /**
     * Test handling null values in constructor
     */
    public function testHandlingNullValuesInConstructor(): void
    {
        $data = [
            'id' => 1,
            'user_id' => null,
            'kind' => 'subtitles',
            'locale' => null
        ];

        $track = new MediaTrack($data);

        $this->assertEquals(1, $track->id);
        $this->assertNull($track->userId);
        $this->assertEquals('subtitles', $track->kind);
        $this->assertNull($track->locale);
    }

    /**
     * Test unknown properties are ignored
     */
    public function testUnknownPropertiesAreIgnored(): void
    {
        $data = [
            'id' => 5,
            'unknown_property' => 'should be ignored',
            'another_unknown' => 123
        ];

        $track = new MediaTrack($data);

        $this->assertEquals(5, $track->id);
        $this->assertObjectNotHasProperty('unknownProperty', $track);
        $this->assertObjectNotHasProperty('anotherUnknown', $track);
    }

    /**
     * Test all track kinds
     */
    public function testAllTrackKinds(): void
    {
        $kinds = ['subtitles', 'captions', 'descriptions', 'chapters', 'metadata'];

        foreach ($kinds as $kind) {
            $track = new MediaTrack(['kind' => $kind]);
            $this->assertEquals($kind, $track->kind);
        }
    }

    /**
     * Test various locale formats
     */
    public function testVariousLocaleFormats(): void
    {
        $locales = ['en', 'es', 'fr', 'de', 'ja', 'ko', 'zh', 'ar', 'en-US', 'es-MX', 'pt-BR'];

        foreach ($locales as $locale) {
            $track = new MediaTrack(['locale' => $locale]);
            $this->assertEquals($locale, $track->locale);
        }
    }

    /**
     * Test complex WEBVTT content
     */
    public function testComplexWebvttContent(): void
    {
        $webvttContent = "WEBVTT\n\n" .
            "NOTE This is a comment\n\n" .
            "1\n" .
            "00:00:00.000 --> 00:00:05.000\n" .
            "Welcome to this video\n\n" .
            "2\n" .
            "00:00:05.000 --> 00:00:10.000\n" .
            "This is the second subtitle\n" .
            "with multiple lines";

        $track = new MediaTrack(['webvtt_content' => $webvttContent]);

        $this->assertEquals($webvttContent, $track->webvttContent);
        
        $array = $track->toArray();
        $this->assertEquals($webvttContent, $array['webvtt_content']);
    }

    /**
     * Test SRT format content
     */
    public function testSrtFormatContent(): void
    {
        $srtContent = "1\n" .
            "00:00:00,000 --> 00:00:05,000\n" .
            "This is SRT format with commas\n\n" .
            "2\n" .
            "00:00:05,000 --> 00:00:10,000\n" .
            "Instead of dots for milliseconds";

        $track = new MediaTrack(['content' => $srtContent]);

        $this->assertEquals($srtContent, $track->content);
    }
}