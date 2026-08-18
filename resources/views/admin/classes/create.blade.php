@extends('layouts.app')

@section('title', 'Add Class')
@section('page-title', 'Add Class')
@section('page-subtitle', 'Create a new class offering')

@section('content')

<div class="row">
    <div class="col-lg-11">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.classes.store') }}" novalidate>
                    @csrf
                    @include('admin.classes._form', ['submitLabel' => 'Create Class'])
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
