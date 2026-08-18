<div class="row g-3">

    <div class="col-md-4">
        <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control @error('subject_code') is-invalid @enderror"
               id="subject_code" name="subject_code"
               value="{{ old('subject_code', $subject->subject_code ?? '') }}"
               placeholder="ITP104" maxlength="30" required>
        @error('subject_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control @error('subject_name') is-invalid @enderror"
               id="subject_name" name="subject_name"
               value="{{ old('subject_name', $subject->subject_name ?? '') }}"
               placeholder="Information Management 2" maxlength="150" required>
        @error('subject_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="units" class="form-label">Units <span class="text-danger">*</span></label>
        <input type="number"
               class="form-control @error('units') is-invalid @enderror"
               id="units" name="units"
               value="{{ old('units', $subject->units ?? 3) }}"
               min="1" max="12" required>
        @error('units')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
        <select class="form-select @error('department_id') is-invalid @enderror"
                id="department_id" name="department_id" required>
            <option value="">-- Select Department --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    {{ old('department_id', $subject->department_id ?? '') == $department->id ? 'selected' : '' }}>
                    {{ $department->department_code }} &mdash; {{ $department->department_name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror"
                  id="description" name="description"
                  rows="3">{{ old('description', $subject->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 d-flex gap-2 mt-4">
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-brand">
            <i class="bi bi-save me-1"></i>{{ $submitLabel }}
        </button>
    </div>

</div>
