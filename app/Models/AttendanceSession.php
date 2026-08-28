<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

        protected $fillable = [
        'class_id',
        'qr_token',
        'qr_token_expires_at',
        'session_date',
        'start_time',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date'        => 'date',
            'qr_token_expires_at' => 'datetime',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'attendance_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'Open';
    }

    public function presentCount(): int
    {
        return $this->attendances()->where('status', 'Present')->count();
    }

    public function lateCount(): int
    {
        return $this->attendances()->where('status', 'Late')->count();
    }

    public function absentCount(): int
    {
        return $this->attendances()->where('status', 'Absent')->count();
    }

    public function checkedInCount(): int
    {
        return $this->attendances()->whereIn('status', ['Present', 'Late'])->count();
    }
}
