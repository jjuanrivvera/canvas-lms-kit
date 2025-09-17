<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\CalendarLink;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\CalendarLink
 */
class CalendarLinkTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $calendarLink = new CalendarLink();

        $this->assertNull($calendarLink->ics);
        $this->assertNull($calendarLink->getIcs());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'ics' => 'https://canvas.example.com/feeds/calendars/course_123.ics',
        ];

        $calendarLink = new CalendarLink($data);

        $this->assertEquals($data['ics'], $calendarLink->ics);
        $this->assertEquals($data['ics'], $calendarLink->getIcs());
    }

    public function testGettersAndSetters(): void
    {
        $calendarLink = new CalendarLink();

        $icsUrl = 'https://canvas.example.com/feeds/calendars/course_456.ics';
        $calendarLink->setIcs($icsUrl);

        $this->assertEquals($icsUrl, $calendarLink->getIcs());
        $this->assertEquals($icsUrl, $calendarLink->ics);
    }

    public function testSetIcsWithNull(): void
    {
        $calendarLink = new CalendarLink(['ics' => 'https://example.com/calendar.ics']);

        $calendarLink->setIcs(null);

        $this->assertNull($calendarLink->getIcs());
        $this->assertNull($calendarLink->ics);
    }

    public function testIsAvailable(): void
    {
        $calendarLink = new CalendarLink();

        // Test with null
        $this->assertFalse($calendarLink->isAvailable());

        // Test with empty string
        $calendarLink->setIcs('');
        $this->assertFalse($calendarLink->isAvailable());

        // Test with valid URL
        $calendarLink->setIcs('https://example.com/calendar.ics');
        $this->assertTrue($calendarLink->isAvailable());
    }

    public function testToArray(): void
    {
        // Test with null value
        $calendarLink = new CalendarLink();
        $this->assertEquals([], $calendarLink->toArray());

        // Test with ICS URL
        $icsUrl = 'https://canvas.example.com/feeds/calendars/course_789.ics';
        $calendarLink->setIcs($icsUrl);

        $expected = [
            'ics' => $icsUrl,
        ];

        $this->assertEquals($expected, $calendarLink->toArray());
    }

    public function testConstructorTypeCoercion(): void
    {
        // Test that non-string values are converted to string
        $data = [
            'ics' => 12345,  // numeric value
        ];

        $calendarLink = new CalendarLink($data);

        $this->assertIsString($calendarLink->ics);
        $this->assertEquals('12345', $calendarLink->ics);
    }
}
