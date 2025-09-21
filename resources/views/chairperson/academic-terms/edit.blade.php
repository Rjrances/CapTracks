@extends('layouts.chairperson')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Academic Term</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('chairperson.academic-terms.update', $academicTerm) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <input type="text" class="form-control @error('school_year') is-invalid @enderror" 
                                   id="school_year" name="school_year" 
                                   value="{{ old('school_year', $academicTerm->school_year) }}" 
                                   placeholder="e.g., 2024-2025" required>
                            @error('school_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select @error('semester') is-invalid @enderror" 
                                    id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                @php
                                    $currentSemester = $academicTerm->semester;
                                    // Extract semester part from full string (e.g., "2024-2025 First Semester" -> "First Semester")
                                    if (strpos($currentSemester, 'First Semester') !== false) {
                                        $currentSemester = 'First Semester';
                                    } elseif (strpos($currentSemester, 'Second Semester') !== false) {
                                        $currentSemester = 'Second Semester';
                                    } elseif (strpos($currentSemester, 'Summer') !== false) {
                                        $currentSemester = 'Summer';
                                    }
                                @endphp
                                <option value="First Semester" 
                                    {{ old('semester', $currentSemester) == 'First Semester' ? 'selected' : '' }}>
                                    First Semester
                                </option>
                                <option value="Second Semester" 
                                    {{ old('semester', $currentSemester) == 'Second Semester' ? 'selected' : '' }}>
                                    Second Semester
                                </option>
                                <option value="Summer" 
                                    {{ old('semester', $currentSemester) == 'Summer' ? 'selected' : '' }}>
                                    Summer
                                </option>
                            </select>
                            @error('semester')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active', $academicTerm->is_active) ? 'checked' : '' }}
                                       {{ $academicTerm->is_archived ? 'disabled' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Set as Active Term
                                </label>
                            </div>
                            @if($academicTerm->is_archived)
                                <small class="form-text text-muted text-danger">
                                    Cannot activate an archived term. Please unarchive first.
                                </small>
                            @else
                                <small class="form-text text-muted">
                                    Only one academic term can be active at a time. Setting this as active will deactivate any currently active term.
                                </small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_archived" name="is_archived" 
                                       value="1" {{ old('is_archived', $academicTerm->is_archived) ? 'checked' : '' }}
                                       {{ $academicTerm->is_active ? 'disabled' : '' }}>
                                <label class="form-check-label" for="is_archived">
                                    Archive Term
                                </label>
                            </div>
                            @if($academicTerm->is_active)
                                <small class="form-text text-muted text-danger">
                                    Cannot archive the active term. Please deactivate first.
                                </small>
                            @else
                                <small class="form-text text-muted">
                                    Archived terms are hidden from normal operations but can be restored later.
                                </small>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Academic Term
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
