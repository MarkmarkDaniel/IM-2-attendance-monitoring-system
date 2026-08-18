@extends('layouts.app')

@section('title', 'Add Instructor')
@section('page-title', 'Add Instructor')
@section('page-subtitle', 'Create an instructor record and login account')

@section('content')

<div class="row">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.instructors.store') }}" novalidate>
                    @csrf
                    @include('admin.instructors._form', ['submitLabel' => 'Create Instructor'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
