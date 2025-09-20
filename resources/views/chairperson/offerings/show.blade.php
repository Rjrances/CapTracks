@extends('layouts.chairperson')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Offering Details</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Subject Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Subject Title:</strong></td>
                                    <td>{{ $offering->subject_title }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Subject Code:</strong></td>
                                    <td>{{ $offering->subject_code }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Teacher:</strong></td>
                                    <td>
                                        @if($offering->teacher)
                                            <span class="badge bg-info">{{ $offering->teacher->name }}</span>
                                            <small class="text-muted d-block">{{ ucfirst($offering->teacher->roles->first()->name ?? 'N/A') }}</small>
                                        @else
                                            <span class="text-muted">No teacher assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Academic Term:</strong></td>
                                    <td>
                                        @if($offering->academicTerm)
                                            <span class="badge bg-secondary">{{ $offering->academicTerm->full_name }}</span>
                                            @if($offering->academicTerm->is_active)
                                                <span class="badge bg-success ms-1">Active</span>
                                            @endif
                                        @else
                                            <span class="text-muted">No term assigned</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Enrollment Statistics</h5>
                            <div class="row text-center">
                                <div class="col-12">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h3>{{ $offering->students->count() }}</h3>
                                            <small>Total Students</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('chairperson.offerings.edit', $offering->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Offering
                        </a>
                        <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Total Students
                        <span class="badge bg-primary ms-2">{{ $offering->students->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($offering->students->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($offering->students as $student)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $student->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $student->student_id }} - {{ $student->course }}</small>
                                    </div>
                                    <form action="{{ route('chairperson.offerings.remove-student', ['offeringId' => $offering->id, 'studentId' => $student->student_id]) }}" 
                                          method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Remove this student from the offering?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No students enrolled yet.</p>
                    @endif
                    <hr>
                    <h6>Add Students</h6>
                    <div class="d-grid gap-2 mb-3">
                        <a href="{{ route('chairperson.upload-form', ['offering_id' => $offering->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-2"></i>Import Students
                        </a>
                        <a href="{{ route('chairperson.offerings.unenrolled-students', $offering->id) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-user-plus me-2"></i>Add Existing Students
                        </a>
                    </div>
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-1"></i>Single Enrollment System
                        </h6>
                        <p class="mb-0 small">
                            Students can only be enrolled in one offering at a time. 
                            <strong>Import Students:</strong> Upload new students from Excel/CSV files.
                            <strong>Add Existing Students:</strong> Select from students already in the system who are not enrolled in any offering.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
