@extends('layouts.chairperson')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Academic Terms</h2>
                <a href="{{ route('chairperson.academic-terms.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Term
                </a>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($academicTerms as $term)
                                    <tr class="{{ $term->is_archived ? 'table-secondary' : '' }}">
                                        <td>{{ $term->school_year }}</td>
                                        <td>
                                            @php
                                                // Extract semester part from full string (e.g., "2024-2025 First Semester" -> "First Semester")
                                                $semesterDisplay = $term->semester;
                                                if (strpos($semesterDisplay, 'First Semester') !== false) {
                                                    $semesterDisplay = 'First Semester';
                                                } elseif (strpos($semesterDisplay, 'Second Semester') !== false) {
                                                    $semesterDisplay = 'Second Semester';
                                                } elseif (strpos($semesterDisplay, 'Summer') !== false) {
                                                    $semesterDisplay = 'Summer';
                                                }
                                            @endphp
                                            {{ $semesterDisplay }}
                                        </td>
                                        <td>
                                            @if($term->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @elseif($term->is_archived)
                                                <span class="badge bg-secondary">Archived</span>
                                            @else
                                                <span class="badge bg-warning">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('chairperson.academic-terms.edit', $term) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                @if(!$term->is_active && !$term->is_archived)
                                                    <form action="{{ route('chairperson.academic-terms.toggle-active', $term) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-check"></i> Activate
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($term->is_active)
                                                    <form action="{{ route('chairperson.academic-terms.toggle-active', $term) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-pause"></i> Deactivate
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!$term->is_active && !$term->is_archived)
                                                    <form action="{{ route('chairperson.academic-terms.toggle-archived', $term) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-archive"></i> Archive
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($term->is_archived)
                                                    <form action="{{ route('chairperson.academic-terms.toggle-archived', $term) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-unarchive"></i> Unarchive
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(!$term->is_active)
                                                    <form action="{{ route('chairperson.academic-terms.destroy', $term) }}" 
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this academic term?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No academic terms found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endsection
