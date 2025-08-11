@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
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
                                <div class="col-6">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h3>{{ $offering->students->count() }}</h3>
                                            <small>Enrolled Students</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h3>{{ $availableStudents->count() }}</h3>
                                            <small>Available Students</small>
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

        <!-- Side Panel - Student Management -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Enrolled Students
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
                                    <form action="{{ route('chairperson.offerings.remove-student', ['offeringId' => $offering->id, 'studentId' => $student->id]) }}" 
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

                    <!-- Add Students Section -->
                    @if($availableStudents->count() > 0)
                        <hr>
                        <h6>Add Students</h6>
                        <form action="{{ route('chairperson.offerings.add-students', $offering->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <select name="student_ids[]" class="form-select" multiple size="5">
                                    @foreach($availableStudents as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->name }} ({{ $student->student_id }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple students</small>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-plus"></i> Add Selected Students
                            </button>
                        </form>
                    @else
                        <hr>
                        <p class="text-muted text-center small">All students are already enrolled in this offering.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
