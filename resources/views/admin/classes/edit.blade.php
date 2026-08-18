@extends('layouts.app')

@section('title', 'Edit Class')
@section('page-title', 'Edit Class')
@section('page-subtitle', $class->class_title)

@section('content')

<div class="row">
    <div class="col-lg-11">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.classes.update', $class) }}" novalidate>
                    @csrf
                    @method('PUT')
                    @include('admin.classes._form', ['submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
