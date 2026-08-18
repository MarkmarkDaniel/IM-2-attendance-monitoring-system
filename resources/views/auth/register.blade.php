@extends('layouts.auth')

@section('title', 'Student Registration')
@section('subtitle', 'Create your student account')
@section('column-width', 'col-md-10 col-lg-8')

@section('content')

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        {{-- ===================== Personal Information ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-3">
            <i class="bi bi-person me-1"></i>Personal Information
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('first_name') is-invalid @enderror"
                       id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="middle_name" class="form-label">Middle Name</label>
                <input type="text"
                       class="form-control @error('middle_name') is-invalid @enderror"
                       id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('last_name') is-invalid @enderror"
                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text"
                       class="form-control @error('phone') is-invalid @enderror"
                       id="phone" name="phone" value="{{ old('phone') }}" placeholder="09171234567">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ===================== Academic Information ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-3">
            <i class="bi bi-mortarboard me-1"></i>Academic Information
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="student_number" class="form-label">Student Number <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('student_number') is-invalid @enderror"
                       id="student_number" name="student_number" value="{{ old('student_number') }}"
                       placeholder="2026-00016" required>
                @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                <select class="form-select @error('department_id') is-invalid @enderror"
                        id="department_id" name="department_id" required>
                    <option value="">-- Select Department --</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ old('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->department_code }} &mdash; {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
                <select class="form-select @error('year_level') is-invalid @enderror"
                        id="year_level" name="year_level" required>
                    <option value="">-- Select Year Level --</option>
                    @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $level)
                        <option value="{{ $level }}" {{ old('year_level') === $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
                @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('section') is-invalid @enderror"
                       id="section" name="section" value="{{ old('section') }}"
                       placeholder="A" required>
                @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ===================== Account Security ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-3">
            <i class="bi bi-shield-lock me-1"></i>Account Security
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" required>
                <div class="form-text">At least 8 characters, with letters and numbers.</div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control"
                       id="password_confirmation" name="password_confirmation" required>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
            <button type="submit" class="btn btn-brand flex-grow-1 py-2">
                <i class="bi bi-person-plus me-1"></i> Create Account
            </button>
        </div>
    </form>

@endsection
