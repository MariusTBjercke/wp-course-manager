<?php

namespace CourseManager\Helpers;

use DateTime;

/**
 * Helper class for formatting course dates and times.
 */
class DateFormatter {

    /**
     * Format a course date's date(s) for display.
     *
     * @param array $courseDate The course date data array.
     * @return string Formatted date string.
     */
    public static function formatCourseDateDate(array $courseDate): string {
        $startDate = $courseDate['start_date'] ?? '';
        $endDate = $courseDate['end_date'] ?? '';

        if ($startDate && $endDate && $startDate !== $endDate) {
            $startDateFormatted = DateTime::createFromFormat('Y-m-d', $startDate);
            $endDateFormatted = DateTime::createFromFormat('Y-m-d', $endDate);
            if ($startDateFormatted && $endDateFormatted) {
                // Check if dates are in the same year for a more compact format
                if ($startDateFormatted->format('Y') === $endDateFormatted->format('Y')) {
                    return $startDateFormatted->format('d.m.') . ' - ' . $endDateFormatted->format('d.m.Y');
                } else {
                    return $startDateFormatted->format('d.m.Y') . ' - ' . $endDateFormatted->format('d.m.Y');
                }
            }
        } elseif ($startDate) {
            $startDateFormatted = DateTime::createFromFormat('Y-m-d', $startDate);
            if ($startDateFormatted) {
                return $startDateFormatted->format('d.m.Y');
            }
        }
        return 'Uspesifisert dato';
    }

    /**
     * Format a course date's time(s) for display.
     *
     * @param array $courseDate The course date data array.
     * @return string Formatted time string.
     */
    public static function formatCourseDateTime(array $courseDate): string {
        $startTime = $courseDate['start_time'] ?? '';
        $endTime = $courseDate['end_time'] ?? '';

        if ($startTime && $endTime) {
            return $startTime . ' - ' . $endTime;
        } elseif ($startTime) {
            return 'Start: ' . $startTime;
        } elseif ($endTime) {
            return 'Slutt: ' . $endTime;
        }
        return 'Uspesifisert tid';
    }

    /**
     * Format a course date's date and time for display (combined).
     *
     * @param array $courseDate The course date data array.
     * @return string Formatted date and time string.
     */
    public static function formatCourseDateDisplay(array $courseDate): string {
        $dateDisplay = self::formatCourseDateDate($courseDate);
        $timeDisplay = self::formatCourseDateTime($courseDate);

        $courseDateDisplay = [];
        if ($dateDisplay !== 'Uspesifisert dato') {
            $courseDateDisplay[] = $dateDisplay;
        }
        if ($timeDisplay !== 'Uspesifisert tid') {
            $courseDateDisplay[] = $timeDisplay;
        }

        if (empty($courseDateDisplay)) {
            return 'Uspesifisert dato/tid';
        }

        return implode(', ', $courseDateDisplay);
    }
}
