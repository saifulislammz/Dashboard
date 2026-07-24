<?php

namespace App\Config;

/**
 * Attendance threshold constants.
 * Centralised here so views, services, and future logic all share one source of truth.
 */
class AttendanceConfig
{
    /** Percentage at or above which attendance is considered "Good" */
    public const THRESHOLD_GOOD = 75;

    /** Percentage at or above which attendance is considered "Average" (below = "At Risk" / "Low") */
    public const THRESHOLD_AVERAGE = 50;
}
