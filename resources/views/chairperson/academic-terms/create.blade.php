@extends('layouts.chairperson')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Create New Academic Term</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('chairperson.academic-terms.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <input type="text" class="form-control @error('school_year') is-invalid @enderror" 
                                   id="school_year" name="school_year" value="{{ old('school_year') }}" 
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
                                <option value="First Semester" {{ old('semester') == 'First Semester' ? 'selected' : '' }}>
                                    First Semester
                                </option>
                                <option value="Second Semester" {{ old('semester') == 'Second Semester' ? 'selected' : '' }}>
                                    Second Semester
                                </option>
                                <option value="Summer" {{ old('semester') == 'Summer' ? 'selected' : '' }}>
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
                                       value="1" {{ old('is_active') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Set as Active Term
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Only one academic term can be active at a time. Setting this as active will deactivate any currently active term.
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Academic Term
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
