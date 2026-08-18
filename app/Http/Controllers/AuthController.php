<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ---------------- LOGIN ----------------

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'The email or password you entered is incorrect.');
        }

        $user = Auth::user();

        // Block students whose record is no longer active
        if ($user->isStudent() && $user->student && $user->student->status !== 'Active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Your student record is not active. Please contact the registrar.');
        }

        // Block instructors who are marked inactive
        if ($user->isInstructor() && $user->instructor && $user->instructor->status !== 'Active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Your instructor account is inactive. Please contact the administrator.');
        }

        // Prevents session fixation attacks
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    // ---------------- REGISTRATION ----------------

    public function showRegister(): View
    {
        return view('auth.register', [
            'departments' => Department::orderBy('department_name')->get(),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // A transaction means: if ANY step fails, none of them are saved.
        // Without this you could end up with a user account and no student row.
        DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => User::ROLE_STUDENT,
            ]);

            Student::create([
                'user_id'        => $user->id,
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'middle_name'    => $data['middle_name'] ?? null,
                'last_name'      => $data['last_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'department_id'  => $data['department_id'],
                'year_level'     => $data['year_level'],
                'section'        => $data['section'],
                'status'         => 'Active',
            ]);

            // Welcome notification for the new student
            Notification::send(
                $user->id,
                'Welcome to the Attendance System',
                'Your account has been created. You can now scan QR codes to record your attendance.',
                'success'
            );

            // Tell every admin about the new registration
            $admins = User::where('role', User::ROLE_ADMIN)->get();

            foreach ($admins as $admin) {
                Notification::send(
                    $admin->id,
                    'New Student Registration',
                    $user->name . ' (' . $data['student_number'] . ') completed registration.',
                    'info'
                );
            }
        });

        return redirect()->route('login')
            ->with('success', 'Registration successful. You may now log in.');
    }

    // ---------------- LOGOUT ----------------

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out.');
    }
}
