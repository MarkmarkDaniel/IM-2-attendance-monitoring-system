<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_session_id',
        'class_id',
        'student_id',
        'attendance_date',
        'time_in',
        'time_out',
        'status',
        'remarks',
        'recorded_by',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    // ---------------- Relationships ----------------

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ---------------- Display helper ----------------

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Present' => 'success',
            'Late'    => 'warning',
            'Absent'  => 'danger',
            default   => 'secondary',
        };
    }

    // ---------------- Query Scopes ----------------

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date'] ?? null,
                fn (Builder $q, $v) => $q->whereDate('attendance_date', $v))
            ->when($filters['class_id'] ?? null,
                fn (Builder $q, $v) => $q->where('class_id', $v))
            ->when($filters['status'] ?? null,
                fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['student_id'] ?? null,
                fn (Builder $q, $v) => $q->where('student_id', $v))
            ->when($filters['date_from'] ?? null,
                fn (Builder $q, $v) => $q->whereDate('attendance_date', '>=', $v))
            ->when($filters['date_to'] ?? null,
                fn (Builder $q, $v) => $q->whereDate('attendance_date', '<=', $v))
            ->when($filters['subject_id'] ?? null,
                fn (Builder $q, $v) => $q->whereHas('classModel',
                    fn (Builder $c) => $c->where('subject_id', $v)))
            ->when($filters['department_id'] ?? null,
                fn (Builder $q, $v) => $q->whereHas('student',
                    fn (Builder $s) => $s->where('department_id', $v)));
    }

    /**
     * ((Present + Late) / Total) * 100 for any set of records.
     */
    public static function calculateRate(int $present, int $late, int $total): float
    {
        if ($total <= 0) {
            return 0.0; // prevents division by zero
        }

        return round((($present + $late) / $total) * 100, 2);
    }
}
