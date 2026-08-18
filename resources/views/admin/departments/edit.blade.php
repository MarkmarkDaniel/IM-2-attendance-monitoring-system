@extends('layouts.app')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')
@section('page-subtitle', $department->department_code)

@section('content')

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.departments.update', $department) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.departments._form', ['submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
