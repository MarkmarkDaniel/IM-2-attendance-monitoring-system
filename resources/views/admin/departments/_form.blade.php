<div class="mb-3">
    <label for="department_code" class="form-label">Department Code <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control @error('department_code') is-invalid @enderror"
           id="department_code"
           name="department_code"
           value="{{ old('department_code', $department->department_code ?? '') }}"
           placeholder="BSIT"
           maxlength="20"
           required>
    @error('department_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="department_name" class="form-label">Department Name <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control @error('department_name') is-invalid @enderror"
           id="department_name"
           name="department_name"
           value="{{ old('department_name', $department->department_name ?? '') }}"
           placeholder="Bachelor of Science in Information Technology"
           maxlength="150"
           required>
    @error('department_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror"
              id="description"
              name="description"
              rows="3">{{ old('description', $department->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2">
    <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-brand">
        <i class="bi bi-save me-1"></i>{{ $submitLabel }}
    </button>
</div>
