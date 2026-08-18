@extends('layouts.app')

@section('title', 'Add Subject')
@section('page-title', 'Add Subject')
@section('page-subtitle', 'Create a new subject in the catalog')

@section('content')

<div class="row">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.subjects.store') }}" novalidate>
                    @csrf
                    @include('admin.subjects._form', ['submitLabel' => 'Create Subject'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
