@extends('layouts.app')

@section('title', 'Edit Subject')
@section('page-title', 'Edit Subject')
@section('page-subtitle', $subject->subject_code)

@section('content')

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.subjects._form', ['submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
