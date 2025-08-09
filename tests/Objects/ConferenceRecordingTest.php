<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Objects;

use CanvasLMS\Objects\ConferenceRecording;
use DateTime;
use PHPUnit\Framework\TestCase;

class ConferenceRecordingTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $recording = new ConferenceRecording();

        $this->assertNull($recording->id);
        $this->assertNull($recording->title);
        $this->assertNull($recording->duration);
        $this->assertNull($recording->created_at);
        $this->assertNull($recording->playback_url);
        $this->assertNull($recording->playback_formats);
        $this->assertNull($recording->recording_id);
        $this->assertNull($recording->updated_at);
    }

    public function testConstructorWithBasicData(): void
    {
        $data = [
            'id' => 123,
            'title' => 'Test Recording',
            'duration' => 3600,
            'playback_url' => 'https://example.com/recording/123',
            'recording_id' => 'REC-123-ABC'
        ];

        $recording = new ConferenceRecording($data);

        $this->assertEquals(123, $recording->id);
        $this->assertEquals('Test Recording', $recording->title);
        $this->assertEquals(3600, $recording->duration);
        $this->assertEquals('https://example.com/recording/123', $recording->playback_url);
        $this->assertEquals('REC-123-ABC', $recording->recording_id);
    }

    public function testConstructorWithDateTimeFields(): void
    {
        $data = [
            'id' => 456,
            'title' => 'Recording with Dates',
            'created_at' => '2024-01-15T10:30:00Z',
            'updated_at' => '2024-01-16T14:45:00Z'
        ];

        $recording = new ConferenceRecording($data);

        $this->assertInstanceOf(DateTime::class, $recording->created_at);
        $this->assertEquals('2024-01-15', $recording->created_at->format('Y-m-d'));
        $this->assertEquals('10:30:00', $recording->created_at->format('H:i:s'));

        $this->assertInstanceOf(DateTime::class, $recording->updated_at);
        $this->assertEquals('2024-01-16', $recording->updated_at->format('Y-m-d'));
        $this->assertEquals('14:45:00', $recording->updated_at->format('H:i:s'));
    }

    public function testConstructorWithPlaybackFormats(): void
    {
        $data = [
            'id' => 789,
            'title' => 'Multi-format Recording',
            'playback_formats' => [
                [
                    'format' => 'video',
                    'url' => 'https://example.com/video/789',
                    'length' => 3600
                ],
                [
                    'format' => 'audio',
                    'url' => 'https://example.com/audio/789',
                    'length' => 3600
                ],
                [
                    'format' => 'transcript',
                    'url' => 'https://example.com/transcript/789'
                ]
            ]
        ];

        $recording = new ConferenceRecording($data);

        $this->assertIsArray($recording->playback_formats);
        $this->assertCount(3, $recording->playback_formats);
        $this->assertEquals('video', $recording->playback_formats[0]['format']);
        $this->assertEquals('audio', $recording->playback_formats[1]['format']);
        $this->assertEquals('transcript', $recording->playback_formats[2]['format']);
    }

    public function testConstructorIgnoresUnknownProperties(): void
    {
        $data = [
            'id' => 999,
            'title' => 'Recording with Extra Data',
            'unknown_field' => 'should be ignored',
            'another_unknown' => 12345
        ];

        $recording = new ConferenceRecording($data);

        $this->assertEquals(999, $recording->id);
        $this->assertEquals('Recording with Extra Data', $recording->title);
        
        $reflection = new \ReflectionObject($recording);
        $this->assertFalse($reflection->hasProperty('unknown_field'));
        $this->assertFalse($reflection->hasProperty('another_unknown'));
    }

    public function testConstructorWithNullDateTimeValues(): void
    {
        $data = [
            'id' => 111,
            'title' => 'Recording without dates',
            'created_at' => null,
            'updated_at' => null
        ];

        $recording = new ConferenceRecording($data);

        $this->assertNull($recording->created_at);
        $this->assertNull($recording->updated_at);
    }

    public function testConstructorWithCompleteData(): void
    {
        $data = [
            'id' => 222,
            'title' => 'Complete Recording',
            'duration' => 7200,
            'created_at' => '2024-02-01T09:00:00Z',
            'updated_at' => '2024-02-01T11:00:00Z',
            'playback_url' => 'https://example.com/play/222',
            'recording_id' => 'REC-222-XYZ',
            'playback_formats' => [
                ['format' => 'presentation', 'url' => 'https://example.com/pres/222']
            ]
        ];

        $recording = new ConferenceRecording($data);

        $this->assertEquals(222, $recording->id);
        $this->assertEquals('Complete Recording', $recording->title);
        $this->assertEquals(7200, $recording->duration);
        $this->assertInstanceOf(DateTime::class, $recording->created_at);
        $this->assertInstanceOf(DateTime::class, $recording->updated_at);
        $this->assertEquals('https://example.com/play/222', $recording->playback_url);
        $this->assertEquals('REC-222-XYZ', $recording->recording_id);
        $this->assertCount(1, $recording->playback_formats);
    }

    public function testDateTimeParsingWithDifferentFormats(): void
    {
        $data = [
            'id' => 333,
            'title' => 'Recording with various date formats',
            'created_at' => '2024-03-15 16:30:45',
            'updated_at' => '2024-03-16T08:15:30+02:00'
        ];

        $recording = new ConferenceRecording($data);

        $this->assertInstanceOf(DateTime::class, $recording->created_at);
        $this->assertEquals('2024-03-15', $recording->created_at->format('Y-m-d'));

        $this->assertInstanceOf(DateTime::class, $recording->updated_at);
        $this->assertEquals('2024-03-16', $recording->updated_at->format('Y-m-d'));
    }

    public function testPropertyTypes(): void
    {
        $data = [
            'id' => 444,
            'title' => 'Type Test Recording',
            'duration' => 5400,
            'playback_url' => 'https://example.com/444',
            'playback_formats' => ['format1', 'format2'],
            'recording_id' => '12345'
        ];

        $recording = new ConferenceRecording($data);

        $this->assertSame(444, $recording->id);
        $this->assertSame('Type Test Recording', $recording->title);
        $this->assertSame(5400, $recording->duration);
        $this->assertSame('https://example.com/444', $recording->playback_url);
        $this->assertSame(['format1', 'format2'], $recording->playback_formats);
        $this->assertSame('12345', $recording->recording_id);
    }
}