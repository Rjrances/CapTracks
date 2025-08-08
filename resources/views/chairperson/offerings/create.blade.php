@extends('layouts.chairperson')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Add New Offering</h4>
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
                            <input type="text" name="subject_title" id="subject_title" 
                                   class="form-control @error('subject_title') is-invalid @enderror" 
                                   value="{{ old('subject_title') }}" required>
                            @error('subject_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code</label>
                            <input type="text" name="subject_code" id="subject_code" 
                                   class="form-control @error('subject_code') is-invalid @enderror" 
                                   value="{{ old('subject_code') }}" required>
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
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }} ({{ ucfirst($teacher->role) }})
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
                                    <option value="{{ $term->id }}" {{ old('academic_term_id') == $term->id ? 'selected' : '' }}>
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
@endsection
