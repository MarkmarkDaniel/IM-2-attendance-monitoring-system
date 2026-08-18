<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late Grace Period (minutes)
    |--------------------------------------------------------------------------
    |
    | A student is PRESENT if they check in at or before:
    |     class start_time + late_grace_minutes
    | Otherwise they are marked LATE.
    |
    | With 0 minutes and a class starting 8:00 AM:
    |     7:55 AM -> Present
    |     8:00 AM -> Present
    |     8:01 AM -> Late
    |
    */
    'late_grace_minutes' => (int) env('ATTENDANCE_LATE_GRACE_MINUTES', 0),

    /*
    |--------------------------------------------------------------------------
    | QR Token Length
    |--------------------------------------------------------------------------
    */
    'qr_token_length' => 40,

    /*
    |--------------------------------------------------------------------------
    | Minimum Passing Attendance Rate (%)
    |--------------------------------------------------------------------------
    | Used to colour badges and trigger low-attendance warnings.
    */
    'minimum_rate' => (int) env('ATTENDANCE_MIN_RATE', 75),

    /*
    |--------------------------------------------------------------------------
    | Auto-Absent On Close
    |--------------------------------------------------------------------------
    | When TRUE, closing a session marks every enrolled student without a
    | record as Absent.
    */
    'auto_absent_on_close' => true,

        /*
    |--------------------------------------------------------------------------
    | Low Attendance Warning Cooldown (days)
    |--------------------------------------------------------------------------
    | How long to wait before sending the same student the same low-attendance
    | warning again. Without this, a warning would fire on every single scan.
    */
    'warning_cooldown_days' => (int) env('ATTENDANCE_WARNING_COOLDOWN_DAYS', 7),

];
