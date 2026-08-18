<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin      = User::where('email', 'admin@example.com')->first();
        $instructor = User::where('email', 'instructor@example.com')->first();
        $student    = User::where('email', 'student@example.com')->first();

        // ---------------- Student notifications ----------------
        if ($student) {
            $this->make($student->id, 'Attendance Recorded',
                'You were marked PRESENT in ITP104 - Information Management 2 on August 10, 2026 at 7:52 AM.',
                'success', true, '2026-08-10 08:05:00');

            $this->make($student->id, 'Late Attendance',
                'You were marked LATE in ITP104 - Information Management 2 on August 3, 2026 at 8:14 AM.',
                'warning', true, '2026-08-03 08:20:00');

            $this->make($student->id, 'Absence Recorded',
                'You were marked ABSENT in ITP111 - System Administration and Maintenance on July 28, 2026.',
                'danger', false, '2026-07-28 16:10:00');

            $this->make($student->id, 'Attendance Session Open',
                'ITP104 - Information Management 2 has an open attendance session. Scan the QR code to check in.',
                'info', false, '2026-08-11 07:45:00');
        }

        // ---------------- Instructor notifications ----------------
        if ($instructor) {
            $this->make($instructor->id, 'Session Closed',
                'Attendance session for ITP104 - 3A on August 10, 2026 was closed. 6 Present, 1 Late, 1 Absent.',
                'info', true, '2026-08-10 11:05:00');

            $this->make($instructor->id, 'Low Attendance Warning',
                'Mark Anthony Delos Reyes has fallen below the 75% attendance rate in ITP104 - 3A.',
                'warning', false, '2026-08-11 09:30:00');

            $this->make($instructor->id, 'New Enrollment',
                'Angelica Marie Torres was enrolled in ITP111 - 3A.',
                'success', false, '2026-08-12 10:15:00');
        }

        // ---------------- Admin notifications ----------------
        if ($admin) {
            $this->make($admin->id, 'New Student Registration',
                'Nathaniel Ryan Ocampo (2026-00015) completed registration under BSCE.',
                'success', true, '2026-08-05 14:20:00');

            $this->make($admin->id, 'Department Attendance Report',
                'BSIT department attendance rate for August 2026 is 87.5%.',
                'info', false, '2026-08-12 17:00:00');

            $this->make($admin->id, 'Class Without Sessions',
                'CPE101 - 1A has no recorded attendance sessions this semester.',
                'warning', false, '2026-08-13 08:00:00');
        }
    }

    private function make(int $userId, string $title, string $message, string $type, bool $isRead, string $createdAt): void
    {
        Notification::updateOrCreate(
            [
                'user_id' => $userId,
                'title'   => $title,
                'message' => $message,
            ],
            [
                'type'       => $type,
                'is_read'    => $isRead,
                'read_at'    => $isRead ? $createdAt : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]
        );
    }
}
