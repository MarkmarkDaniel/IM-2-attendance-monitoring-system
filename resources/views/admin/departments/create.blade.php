@extends('layouts.app')

@section('title', 'Add Department')
@section('page-title', 'Add Department')
@section('page-subtitle', 'Create a new academic department')

@section('content')

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.departments.store') }}" novalidate>
                    @csrf
                    @include('admin.departments._form', ['submitLabel' => 'Create Department'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
