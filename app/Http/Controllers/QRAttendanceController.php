<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QRAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    /* ==================================================================
     | INSTRUCTOR SIDE
     ================================================================== */

    /**
     * Class picker for QR attendance.
     */
    public function index(Request $request): View
    {
        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        $today = now()->toDateString();

        $classes = ClassModel::with('subject')
            ->where('instructor_id', $instructor->id)
            ->where('status', 'Active')
            ->withCount('activeEnrollments')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get();

        $sessions = AttendanceSession::whereIn('class_id', $classes->pluck('id'))
            ->whereDate('session_date', $today)
            ->get()
            ->keyBy('class_id');

        return view('instructor.qr.index', compact('classes', 'sessions'));
    }

    /**
     * The live QR display for one class.
     */
    public function show(Request $request, ClassModel $class): View
    {
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $class->load('subject');

        $date = $request->query('date', now()->toDateString());

        $session = AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', $date)
            ->first();

        $enrolledCount = Enrollment::where('class_id', $class->id)
            ->where('enrollment_status', 'Enrolled')
            ->count();

        // The QR encodes ONLY this URL — no names, no student numbers,
        // no class IDs. Just a random token.
        $scanUrl = $session && $session->qr_token
            ? route('student.qr.scan', $session->qr_token)
            : null;

        return view('instructor.qr.show', compact(
            'class', 'session', 'date', 'enrolledCount', 'scanUrl'
        ));
    }

    /**
     * Live counters, polled by JavaScript every few seconds.
     */
    public function stats(Request $request, AttendanceSession $session): JsonResponse
    {
        abort_unless($session->classModel->belongsToInstructor($request->user()), 403);

        $enrolled = Enrollment::where('class_id', $session->class_id)
            ->where('enrollment_status', 'Enrolled')
            ->count();

        $present = $session->presentCount();
        $late    = $session->lateCount();
        $checked = $present + $late;

        return response()->json([
            'status'    => $session->status,
            'present'   => $present,
            'late'      => $late,
            'absent'    => $session->absentCount(),
            'checkedIn' => $checked,
            'remaining' => max(0, $enrolled - $checked),
            'enrolled'  => $enrolled,
            'recent'    => $session->attendances()
                ->with('student')
                ->whereIn('status', ['Present', 'Late'])
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'name'   => $row->student->full_name,
                    'number' => $row->student->student_number,
                    'time'   => $row->time_in
                        ? \Carbon\Carbon::parse($row->time_in)->format('g:i A')
                        : '—',
                    'status' => $row->status,
                ]),
        ]);
    }

    /* ==================================================================
     | STUDENT SIDE
     ================================================================== */

    /**
     * The camera scanner page.
     */
    public function scanner(Request $request): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        return view('student.qr.scanner');
    }

    /**
     * Landing route for a scanned QR code. Phone cameras open this URL
     * directly, so it must work as a plain GET request too.
     */
    public function scan(Request $request, string $token): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        $result = $this->attendance->processScan($token, $student);

        return view('student.qr.result', compact('result'));
    }

    /**
     * AJAX endpoint used by the in-page camera scanner.
     */
    public function submit(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return response()->json([
                'ok'      => false,
                'message' => 'No student profile is linked to this account.',
            ], 403);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        // A scanned QR may contain the full URL, so pull out the last segment
        $token = $this->extractToken($validated['token']);

        $result = $this->attendance->processScan($token, $student);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Accepts either a bare token or a full scan URL.
     */
    private function extractToken(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '/')) {
            $path = parse_url($raw, PHP_URL_PATH) ?? $raw;

            return basename(rtrim($path, '/'));
        }

        return $raw;
    }
}
