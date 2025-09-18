@extends('layouts.chairperson')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Add New Offering</h4>
                    @if($activeTerm)
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            New offerings will be assigned to the current active term: <strong>{{ $activeTerm->full_name }}</strong>
                        </small>
                    @endif
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
                    <form action="{{ route('chairperson.offerings.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="subject_title" class="form-label">Subject Title</label>
                            <select name="subject_title" id="subject_title" 
                                    class="form-select @error('subject_title') is-invalid @enderror" required>
                                <option value="">Select Subject Title</option>
                                <option value="Capstone 1" {{ old('subject_title') == 'Capstone 1' ? 'selected' : '' }}>Capstone 1</option>
                                <option value="Capstone 2" {{ old('subject_title') == 'Capstone 2' ? 'selected' : '' }}>Capstone 2</option>
                                <option value="Thesis 1" {{ old('subject_title') == 'Thesis 1' ? 'selected' : '' }}>Thesis 1</option>
                                <option value="Thesis 2" {{ old('subject_title') == 'Thesis 2' ? 'selected' : '' }}>Thesis 2</option>
                            </select>
                            @error('subject_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code</label>
                            <input type="text" name="subject_code" id="subject_code" 
                                   class="form-control @error('subject_code') is-invalid @enderror" 
                                   value="{{ old('subject_code') }}" 
                                   placeholder="Will be auto-filled based on subject title" 
                                   readonly required>
                            @error('subject_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Subject code will be automatically set based on the selected subject title.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="offer_code" class="form-label">Offer Code</label>
                            <input type="text" name="offer_code" id="offer_code" 
                                   class="form-control @error('offer_code') is-invalid @enderror" 
                                   value="{{ old('offer_code') }}" 
                                   placeholder="e.g., 1101, 1102, 1103, 1104" required>
                            @error('offer_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select name="teacher_id" id="teacher_id" 
                                    class="form-select @error('teacher_id') is-invalid @enderror" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ ucfirst($teacher->role ?? 'N/A') }})
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
                                        {{ old('academic_term_id', $activeTerm ? $activeTerm->id : '') == $term->id ? 'selected' : '' }}>
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
                                <i class="fas fa-save"></i> Create Offering
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectTitleSelect = document.getElementById('subject_title');
    const subjectCodeInput = document.getElementById('subject_code');
    
    // Mapping of subject titles to subject codes
    const subjectCodeMapping = {
        'Capstone 1': 'CT1',
        'Capstone 2': 'CT2',
        'Thesis 1': 'T1',
        'Thesis 2': 'T2'
    };
    
    // Handle subject title change
    subjectTitleSelect.addEventListener('change', function() {
        const selectedTitle = this.value;
        
        if (selectedTitle && subjectCodeMapping[selectedTitle]) {
            subjectCodeInput.value = subjectCodeMapping[selectedTitle];
        } else {
            subjectCodeInput.value = '';
        }
    });
    
    // Set initial value if form has old input (for validation errors)
    const initialTitle = subjectTitleSelect.value;
    if (initialTitle && subjectCodeMapping[initialTitle]) {
        subjectCodeInput.value = subjectCodeMapping[initialTitle];
    }
});
</script>
@endsection
