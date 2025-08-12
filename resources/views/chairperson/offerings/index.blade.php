@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Current Offerings</h2>
                    @if($activeTerm)
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-alt me-1"></i>
                            @if($showAllTerms)
                                Showing offerings for: <strong>All Terms</strong>
                                <span class="badge bg-info ms-2">All Terms</span>
                            @else
                                Showing offerings for: <strong>{{ $activeTerm->full_name }}</strong>
                                <span class="badge bg-success ms-2">Active Term</span>
                            @endif
                        </p>
                    @else
                        <p class="text-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            No active academic term set. Please set an active term to view offerings.
                        </p>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if($activeTerm)
                        @if($showAllTerms)
                            <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-outline-success">
                                <i class="fas fa-calendar-check"></i> Show Active Term Only
                            </a>
                        @else
                            <a href="{{ route('chairperson.offerings.index', ['show_all' => true]) }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar-alt"></i> Show All Terms
                            </a>
                        @endif
                    @endif


                    <a href="{{ route('chairperson.offerings.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Offering
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Subject Title</th>
                                    <th>Subject Code</th>
                                    <th>Teacher</th>
                                    <th>Academic Term</th>
                                    <th>Enrolled Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($offerings as $offering)
                                    <tr>
                                        <td>{{ $offering->subject_title }}</td>
                                        <td>{{ $offering->subject_code }}</td>
                                        <td>
                                            @if($offering->teacher)
                                                <span class="badge bg-info">{{ $offering->teacher->name }}</span>
                                            @else
                                                <span class="text-muted">No teacher assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($offering->academicTerm)
                                                <span class="badge bg-secondary">{{ $offering->academicTerm->full_name }}</span>
                                            @else
                                                <span class="text-muted">No term assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $offering->enrolled_students_count }} students</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('chairperson.offerings.show', $offering->id) }}" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="{{ route('chairperson.offerings.edit', $offering->id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="{{ route('chairperson.upload-form') }}?offering_id={{ $offering->id }}" 
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-upload"></i> Import
                                                </a>
                                                <form action="{{ route('chairperson.offerings.delete', $offering->id) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this offering?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No offerings available yet.</td>
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
