<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\MediaSource;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\MediaSource
 */
class MediaSourceTest extends TestCase
{
    /**
     * Test creating MediaSource with all properties
     */
    public function testCreateWithAllProperties(): void
    {
        $data = [
            'height' => '720',
            'width' => '1280',
            'content_type' => 'video/mp4',
            'containerFormat' => 'isom',
            'url' => 'https://example.com/video.mp4',
            'bitrate' => '2500',
            'size' => '10485760',
            'isOriginal' => '1',
            'fileExt' => 'mp4'
        ];

        $source = new MediaSource($data);

        $this->assertEquals('720', $source->height);
        $this->assertEquals('1280', $source->width);
        $this->assertEquals('video/mp4', $source->contentType);
        $this->assertEquals('isom', $source->containerFormat);
        $this->assertEquals('https://example.com/video.mp4', $source->url);
        $this->assertEquals('2500', $source->bitrate);
        $this->assertEquals('10485760', $source->size);
        $this->assertEquals('1', $source->isOriginal);
        $this->assertEquals('mp4', $source->fileExt);
    }

    /**
     * Test creating MediaSource with empty data
     */
    public function testCreateWithEmptyData(): void
    {
        $source = new MediaSource();

        $this->assertNull($source->height);
        $this->assertNull($source->width);
        $this->assertNull($source->contentType);
        $this->assertNull($source->containerFormat);
        $this->assertNull($source->url);
        $this->assertNull($source->bitrate);
        $this->assertNull($source->size);
        $this->assertNull($source->isOriginal);
        $this->assertNull($source->fileExt);
    }

    /**
     * Test snake_case to camelCase conversion
     */
    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'content_type' => 'video/webm',
            'file_ext' => 'webm'
        ];

        $source = new MediaSource($data);

        $this->assertEquals('video/webm', $source->contentType);
        $this->assertEquals('webm', $source->fileExt);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $source = new MediaSource([
            'height' => '480',
            'width' => '854',
            'content_type' => 'video/webm',
            'containerFormat' => 'webm',
            'url' => 'https://example.com/video.webm',
            'bitrate' => '1500',
            'size' => '5242880',
            'isOriginal' => '0',
            'fileExt' => 'webm'
        ]);

        $array = $source->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('480', $array['height']);
        $this->assertEquals('854', $array['width']);
        $this->assertEquals('video/webm', $array['content_type']);
        $this->assertEquals('webm', $array['container_format']);
        $this->assertEquals('https://example.com/video.webm', $array['url']);
        $this->assertEquals('1500', $array['bitrate']);
        $this->assertEquals('5242880', $array['size']);
        $this->assertEquals('0', $array['is_original']);
        $this->assertEquals('webm', $array['file_ext']);
    }

    /**
     * Test toArray with partial data
     */
    public function testToArrayWithPartialData(): void
    {
        $source = new MediaSource([
            'height' => '1080',
            'width' => '1920',
            'content_type' => 'video/mp4',
            'url' => 'https://example.com/hd.mp4'
        ]);

        $array = $source->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('1080', $array['height']);
        $this->assertEquals('1920', $array['width']);
        $this->assertEquals('video/mp4', $array['content_type']);
        $this->assertEquals('https://example.com/hd.mp4', $array['url']);
        
        // Should not include null values
        $this->assertArrayNotHasKey('containerFormat', $array);
        $this->assertArrayNotHasKey('bitrate', $array);
        $this->assertArrayNotHasKey('size', $array);
        $this->assertArrayNotHasKey('isOriginal', $array);
        $this->assertArrayNotHasKey('fileExt', $array);
    }

    /**
     * Test toArray with all null values
     */
    public function testToArrayWithAllNullValues(): void
    {
        $source = new MediaSource();

        $array = $source->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    /**
     * Test handling null values in constructor
     */
    public function testHandlingNullValuesInConstructor(): void
    {
        $data = [
            'height' => '360',
            'width' => null,
            'content_type' => 'video/flv',
            'url' => null
        ];

        $source = new MediaSource($data);

        $this->assertEquals('360', $source->height);
        $this->assertNull($source->width);
        $this->assertEquals('video/flv', $source->contentType);
        $this->assertNull($source->url);
    }

    /**
     * Test unknown properties are ignored
     */
    public function testUnknownPropertiesAreIgnored(): void
    {
        $data = [
            'height' => '240',
            'unknown_property' => 'should be ignored',
            'another_unknown' => 456
        ];

        $source = new MediaSource($data);

        $this->assertEquals('240', $source->height);
        $this->assertObjectNotHasProperty('unknownProperty', $source);
        $this->assertObjectNotHasProperty('anotherUnknown', $source);
    }

    /**
     * Test various video formats
     */
    public function testVariousVideoFormats(): void
    {
        $formats = [
            ['content_type' => 'video/mp4', 'fileExt' => 'mp4', 'containerFormat' => 'isom'],
            ['content_type' => 'video/webm', 'fileExt' => 'webm', 'containerFormat' => 'webm'],
            ['content_type' => 'video/x-flv', 'fileExt' => 'flv', 'containerFormat' => 'flash video'],
            ['content_type' => 'video/ogg', 'fileExt' => 'ogv', 'containerFormat' => 'ogg'],
            ['content_type' => 'video/quicktime', 'fileExt' => 'mov', 'containerFormat' => 'quicktime']
        ];

        foreach ($formats as $format) {
            $source = new MediaSource($format);
            $this->assertEquals($format['content_type'], $source->contentType);
            $this->assertEquals($format['fileExt'], $source->fileExt);
            $this->assertEquals($format['containerFormat'], $source->containerFormat);
        }
    }

    /**
     * Test audio formats
     */
    public function testAudioFormats(): void
    {
        $formats = [
            ['content_type' => 'audio/mp3', 'fileExt' => 'mp3'],
            ['content_type' => 'audio/mpeg', 'fileExt' => 'mp3'],
            ['content_type' => 'audio/ogg', 'fileExt' => 'ogg'],
            ['content_type' => 'audio/wav', 'fileExt' => 'wav']
        ];

        foreach ($formats as $format) {
            $source = new MediaSource($format);
            $this->assertEquals($format['content_type'], $source->contentType);
            $this->assertEquals($format['fileExt'], $source->fileExt);
        }
    }

    /**
     * Test isOriginal values
     */
    public function testIsOriginalValues(): void
    {
        // Test original file
        $original = new MediaSource(['isOriginal' => '1']);
        $this->assertEquals('1', $original->isOriginal);

        // Test transcoded file
        $transcoded = new MediaSource(['isOriginal' => '0']);
        $this->assertEquals('0', $transcoded->isOriginal);
    }

    /**
     * Test various resolutions
     */
    public function testVariousResolutions(): void
    {
        $resolutions = [
            ['height' => '240', 'width' => '320'],   // 240p
            ['height' => '360', 'width' => '640'],   // 360p
            ['height' => '480', 'width' => '854'],   // 480p
            ['height' => '720', 'width' => '1280'],  // 720p HD
            ['height' => '1080', 'width' => '1920'], // 1080p Full HD
            ['height' => '1440', 'width' => '2560'], // 1440p 2K
            ['height' => '2160', 'width' => '3840']  // 2160p 4K
        ];

        foreach ($resolutions as $resolution) {
            $source = new MediaSource($resolution);
            $this->assertEquals($resolution['height'], $source->height);
            $this->assertEquals($resolution['width'], $source->width);
        }
    }

    /**
     * Test size values (in bytes)
     */
    public function testSizeValues(): void
    {
        $sizes = [
            '1024',         // 1 KB
            '1048576',      // 1 MB
            '104857600',    // 100 MB
            '1073741824',   // 1 GB
        ];

        foreach ($sizes as $size) {
            $source = new MediaSource(['size' => $size]);
            $this->assertEquals($size, $source->size);
        }
    }

    /**
     * Test bitrate values
     */
    public function testBitrateValues(): void
    {
        $bitrates = [
            '128',   // Low quality audio
            '320',   // High quality audio
            '500',   // Low quality video
            '1500',  // Medium quality video
            '5000',  // High quality video
            '10000', // Very high quality video
        ];

        foreach ($bitrates as $bitrate) {
            $source = new MediaSource(['bitrate' => $bitrate]);
            $this->assertEquals($bitrate, $source->bitrate);
        }
    }
}