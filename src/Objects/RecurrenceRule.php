<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * RRULE (Recurrence Rule) builder and parser
 * Follows RFC 5545 specification
 *
 * @package CanvasLMS\Objects
 */
class RecurrenceRule
{
    /**
     * Frequency constants
     */
    public const FREQ_DAILY = 'DAILY';
    public const FREQ_WEEKLY = 'WEEKLY';
    public const FREQ_MONTHLY = 'MONTHLY';
    public const FREQ_YEARLY = 'YEARLY';

    /**
     * Day constants
     */
    public const DAY_MONDAY = 'MO';
    public const DAY_TUESDAY = 'TU';
    public const DAY_WEDNESDAY = 'WE';
    public const DAY_THURSDAY = 'TH';
    public const DAY_FRIDAY = 'FR';
    public const DAY_SATURDAY = 'SA';
    public const DAY_SUNDAY = 'SU';

    /**
     * All valid frequencies
     * @var array<string>
     */
    public const VALID_FREQUENCIES = [
        self::FREQ_DAILY,
        self::FREQ_WEEKLY,
        self::FREQ_MONTHLY,
        self::FREQ_YEARLY
    ];

    /**
     * All valid days
     * @var array<string>
     */
    public const VALID_DAYS = [
        self::DAY_MONDAY,
        self::DAY_TUESDAY,
        self::DAY_WEDNESDAY,
        self::DAY_THURSDAY,
        self::DAY_FRIDAY,
        self::DAY_SATURDAY,
        self::DAY_SUNDAY
    ];

    /**
     * Build a daily recurrence rule
     * @param int|null $count Number of occurrences
     * @param \DateTime|null $until End date
     * @param int $interval Interval between occurrences
     * @return string
     */
    public static function daily(?int $count = null, ?\DateTime $until = null, int $interval = 1): string
    {
        return self::build(self::FREQ_DAILY, $count, $until, $interval);
    }

    /**
     * Build a weekly recurrence rule
     * @param array<string> $days Days of the week (use DAY_* constants)
     * @param int|null $count Number of occurrences
     * @param \DateTime|null $until End date
     * @param int $interval Interval between occurrences
     * @return string
     */
    public static function weekly(
        array $days = [],
        ?int $count = null,
        ?\DateTime $until = null,
        int $interval = 1
    ): string {
        $rule = self::build(self::FREQ_WEEKLY, $count, $until, $interval);

        if (!empty($days)) {
            $validDays = array_filter($days, fn($day) => in_array($day, self::VALID_DAYS, true));
            if (!empty($validDays)) {
                $rule .= ';BYDAY=' . implode(',', $validDays);
            }
        }

        return $rule;
    }

    /**
     * Build a monthly recurrence rule
     * @param int|null $dayOfMonth Day of the month (1-31)
     * @param int|null $count Number of occurrences
     * @param \DateTime|null $until End date
     * @param int $interval Interval between occurrences
     * @return string
     */
    public static function monthly(
        ?int $dayOfMonth = null,
        ?int $count = null,
        ?\DateTime $until = null,
        int $interval = 1
    ): string {
        $rule = self::build(self::FREQ_MONTHLY, $count, $until, $interval);

        if ($dayOfMonth !== null && $dayOfMonth >= 1 && $dayOfMonth <= 31) {
            $rule .= ";BYMONTHDAY={$dayOfMonth}";
        }

        return $rule;
    }

    /**
     * Build a yearly recurrence rule
     * @param int|null $count Number of occurrences
     * @param \DateTime|null $until End date
     * @param int $interval Interval between occurrences
     * @return string
     */
    public static function yearly(?int $count = null, ?\DateTime $until = null, int $interval = 1): string
    {
        return self::build(self::FREQ_YEARLY, $count, $until, $interval);
    }

    /**
     * Build a recurrence rule string
     * @param string $frequency Frequency (DAILY, WEEKLY, MONTHLY, YEARLY)
     * @param int|null $count Number of occurrences
     * @param \DateTime|null $until End date
     * @param int $interval Interval between occurrences
     * @return string
     */
    public static function build(
        string $frequency,
        ?int $count = null,
        ?\DateTime $until = null,
        int $interval = 1
    ): string {
        if (!in_array($frequency, self::VALID_FREQUENCIES, true)) {
            throw new \InvalidArgumentException("Invalid frequency: {$frequency}");
        }

        if ($count !== null && $until !== null) {
            throw new \InvalidArgumentException("Cannot specify both COUNT and UNTIL");
        }

        if ($interval < 1) {
            throw new \InvalidArgumentException("Interval must be at least 1");
        }

        $parts = ["FREQ={$frequency}"];

        if ($interval > 1) {
            $parts[] = "INTERVAL={$interval}";
        }

        if ($count !== null && $count > 0) {
            $parts[] = "COUNT={$count}";
        }

        if ($until !== null) {
            $parts[] = "UNTIL=" . $until->format('Ymd\THis\Z');
        }

        return implode(';', $parts);
    }

    /**
     * Parse an RRULE string into components
     * @param string $rrule
     * @return array{
     *     frequency: string,
     *     interval: int,
     *     count: int|null,
     *     until: \DateTime|null,
     *     byday: array<string>|null,
     *     bymonthday: int|null
     * }
     */
    public static function parse(string $rrule): array
    {
        $result = [
            'frequency' => '',
            'interval' => 1,
            'count' => null,
            'until' => null,
            'byday' => null,
            'bymonthday' => null
        ];

        $parts = explode(';', $rrule);

        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $part, 2);

            switch ($key) {
                case 'FREQ':
                    $result['frequency'] = $value;
                    break;

                case 'INTERVAL':
                    $result['interval'] = (int) $value;
                    break;

                case 'COUNT':
                    $result['count'] = (int) $value;
                    break;

                case 'UNTIL':
                    try {
                        $result['until'] = new \DateTime($value);
                    } catch (\Exception $e) {
                        // Invalid date format
                    }
                    break;

                case 'BYDAY':
                    $result['byday'] = explode(',', $value);
                    break;

                case 'BYMONTHDAY':
                    $result['bymonthday'] = (int) $value;
                    break;
            }
        }

        return $result;
    }

    /**
     * Get a human-readable description of an RRULE
     * @param string $rrule
     * @return string
     */
    public static function toHumanReadable(string $rrule): string
    {
        $parsed = self::parse($rrule);

        if (empty($parsed['frequency'])) {
            return 'Invalid recurrence rule';
        }

        $description = match ($parsed['frequency']) {
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_YEARLY => 'Yearly',
            default => $parsed['frequency']
        };

        if ($parsed['interval'] > 1) {
            $description = "Every {$parsed['interval']} " . strtolower($description) . 's';
        }

        if ($parsed['byday'] !== null && !empty($parsed['byday'])) {
            $days = array_map(fn($day) => self::getDayName($day), $parsed['byday']);
            $description .= ' on ' . implode(', ', $days);
        }

        if ($parsed['bymonthday'] !== null) {
            $description .= ' on day ' . $parsed['bymonthday'];
        }

        if ($parsed['count'] !== null) {
            $description .= ", {$parsed['count']} times";
        } elseif ($parsed['until'] !== null) {
            $description .= ' until ' . $parsed['until']->format('Y-m-d');
        }

        return $description;
    }

    /**
     * Get full day name from abbreviation
     * @param string $day
     * @return string
     */
    private static function getDayName(string $day): string
    {
        return match ($day) {
            self::DAY_MONDAY => 'Monday',
            self::DAY_TUESDAY => 'Tuesday',
            self::DAY_WEDNESDAY => 'Wednesday',
            self::DAY_THURSDAY => 'Thursday',
            self::DAY_FRIDAY => 'Friday',
            self::DAY_SATURDAY => 'Saturday',
            self::DAY_SUNDAY => 'Sunday',
            default => $day
        };
    }
}
