@extends('layouts.coordinator')

@section('title', 'Create Milestone Template')

@section('content')
<div class="container" style="max-width: 600px;">
    <div class="card shadow-sm mt-5">
        <div class="card-body">
            <h1 class="card-title mb-4 fw-bold fs-3">Create Milestone Template</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>There were some problems with your input:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('coordinator.milestones.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Milestone Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-link text-secondary">&larr; Back to List</a>
                    <button type="submit" class="btn btn-primary px-4">Save Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
