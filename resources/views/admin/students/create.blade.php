@extends('layouts.app')

@section('title', 'Add Student')
@section('page-title', 'Add Student')
@section('page-subtitle', 'Create a student record and login account')

@section('content')

<div class="row">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.students.store') }}" novalidate>
                    @csrf
                    @include('admin.students._form', ['submitLabel' => 'Create Student'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
