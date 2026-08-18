<?php

namespace App\Console\Commands;

use App\Models\ClassModel;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckAttendanceWarnings extends Command
{
    protected $signature = 'attendance:check-warnings
                            {--class= : Only check one class by ID}';

    protected $description = 'Warn students (and their instructors) whose attendance rate is below the minimum';

    public function handle(NotificationService $notifications): int
    {
        $minimum = config('attendance.minimum_rate', 75);

        $this->info('Checking attendance rates below ' . $minimum . '%...');
        $this->newLine();

        $classes = ClassModel::with(['subject', 'instructor'])
            ->where('status', 'Active')
            ->when($this->option('class'), fn ($q, $v) => $q->where('id', $v))
            ->get();

        if ($classes->isEmpty()) {
            $this->warn('No active classes found.');

            return self::SUCCESS;
        }

        $totalWarned = 0;

        foreach ($classes as $class) {
            $warned = $notifications->checkClass($class);
            $totalWarned += $warned;

            $this->line(sprintf(
                '  %-12s %-6s  %d warning(s) sent',
                $class->subject->subject_code,
                $class->section,
                $warned
            ));
        }

        $this->newLine();

        if ($totalWarned === 0) {
            $this->info('No new warnings — everyone is above the minimum, or already warned recently.');
        } else {
            $this->info($totalWarned . ' warning(s) sent across ' . $classes->count() . ' class(es).');
        }

        return self::SUCCESS;
    }
}
