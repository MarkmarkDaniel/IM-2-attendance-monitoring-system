<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Class" is a reserved word in PHP, so the model is ClassModel
 * while the database table stays "classes".
 */
class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'subject_id',
        'instructor_id',
        'school_year',
        'semester',
        'section',
        'room',
        'schedule_day',
        'start_time',
        'end_time',
        'status',
    ];

    // ---------------- Relationships ----------------

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    /**
     * Only students who are actually enrolled (not dropped).
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id')
                    ->where('enrollment_status', 'Enrolled');
    }

    // ---------------- Accessors ----------------

    public function getClassTitleAttribute(): string
    {
        $code = $this->subject?->subject_code ?? 'N/A';

        return $code . ' - ' . $this->section;
    }

    public function getScheduleTextAttribute(): string
    {
        return $this->schedule_day . ' '
             . Carbon::parse($this->start_time)->format('g:i A') . ' - '
             . Carbon::parse($this->end_time)->format('g:i A');
    }

    // ---------------- Late detection ----------------

    /**
     * Decide Present vs Late for a given check-in time.
     * This is the ONLY place the rule lives.
     */
    public function determineStatus(Carbon $timeIn): string
    {
        $grace = (int) config('attendance.late_grace_minutes', 0);

        $cutoff = Carbon::parse($timeIn->toDateString() . ' ' . $this->start_time)
                        ->addMinutes($grace);

        return $timeIn->lessThanOrEqualTo($cutoff) ? 'Present' : 'Late';
    }

    /**
     * Does this class meet today?
     */
    public function meetsOn(Carbon $date): bool
    {
        return $date->format('l') === $this->schedule_day;
    }

    /**
     * Is this class taught by the given user?
     * Used to stop an instructor from touching another instructor's class.
     */
    public function belongsToInstructor(?User $user): bool
    {
        if (! $user || ! $user->isInstructor()) {
            return false;
        }

        return $user->instructor
            && $this->instructor_id === $user->instructor->id;
    }
}
