@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Offering</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('chairperson.offerings.update', $offering->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="subject_title" class="form-label">Subject Title</label>
                            <input type="text" name="subject_title" id="subject_title" 
                                   class="form-control @error('subject_title') is-invalid @enderror" 
                                   value="{{ old('subject_title', $offering->subject_title) }}" required>
                            @error('subject_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code</label>
                            <input type="text" name="subject_code" id="subject_code" 
                                   class="form-control @error('subject_code') is-invalid @enderror" 
                                   value="{{ old('subject_code', $offering->subject_code) }}" required>
                            @error('subject_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select name="teacher_id" id="teacher_id" 
                                    class="form-select @error('teacher_id') is-invalid @enderror" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" 
                                        {{ old('teacher_id', $offering->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ ucfirst($teacher->roles->first()->name ?? 'N/A') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="academic_term_id" class="form-label">Academic Term</label>
                            <select name="academic_term_id" id="academic_term_id" 
                                    class="form-select @error('academic_term_id') is-invalid @enderror" required>
                                <option value="">Select Academic Term</option>
                                @foreach($academicTerms as $term)
                                    <option value="{{ $term->id }}" 
                                        {{ old('academic_term_id', $offering->academic_term_id) == $term->id ? 'selected' : '' }}>
                                        {{ $term->full_name }}
                                        @if($term->is_active)
                                            <span class="text-success">(Active)</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_term_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Offering
                            </button>
                        </div>
                    </form>
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
                    <hr>
                    <h6>Add Students</h6>
                    <form action="{{ route('chairperson.offerings.add-students', $offering->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <select name="student_ids[]" class="form-select" multiple size="5">
                                @foreach($students as $student)
                                    @if(!$offering->students->contains($student->id))
                                        <option value="{{ $student->id }}">
                                            {{ $student->name }} ({{ $student->student_id }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple students</small>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-plus"></i> Add Selected Students
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
