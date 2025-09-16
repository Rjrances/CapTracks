@extends('layouts.student')

@section('title', 'Submit Task - ' . $task->milestoneTask->name)

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('student.milestones') }}">Milestones</a></li>
            <li class="breadcrumb-item"><a href="{{ route('student.milestones.show', $task->groupMilestone->id) }}">{{ $task->groupMilestone->milestoneTemplate->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Submit Task</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Submit Task: {{ $task->milestoneTask->name }}
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Task Information -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Task Description:</h6>
                        <p class="text-muted">{{ $task->milestoneTask->description }}</p>
                        
                        @if($task->notes)
                            <h6 class="fw-bold mt-3">Additional Notes:</h6>
                            <p class="text-muted">{{ $task->notes }}</p>
                        @endif
                    </div>

                    <!-- Submission Form -->
                    <form action="{{ route('student.task-submission.store', $task->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="submission_type" class="form-label">
                                <i class="fas fa-tag me-2"></i>Submission Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('submission_type') is-invalid @enderror" 
                                    id="submission_type" 
                                    name="submission_type" 
                                    required>
                                <option value="">Select submission type</option>
                                <option value="document" {{ old('submission_type') == 'document' ? 'selected' : '' }}>
                                    📄 Document (Chapter 1&2 or Chapter 3&4)
                                </option>
                                <option value="screenshots" {{ old('submission_type') == 'screenshots' ? 'selected' : '' }}>
                                    📸 Screenshots (60% or 100% Progress)
                                </option>
                                <option value="progress_notes" {{ old('submission_type') == 'progress_notes' ? 'selected' : '' }}>
                                    📝 Progress Notes & Updates
                                </option>
                            </select>
                            @error('submission_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Choose the type of submission based on your milestone phase
                            </div>
                        </div>

                        <!-- Progress Percentage (for screenshots) -->
                        <div class="mb-4" id="progress_percentage_field" style="display: none;">
                            <label for="progress_percentage" class="form-label">
                                <i class="fas fa-percentage me-2"></i>Progress Percentage
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('progress_percentage') is-invalid @enderror" 
                                       id="progress_percentage" 
                                       name="progress_percentage" 
                                       min="0" 
                                       max="100" 
                                       value="{{ old('progress_percentage', 0) }}"
                                       placeholder="Enter progress percentage (0-100)">
                                <span class="input-group-text">%</span>
                            </div>
                            @error('progress_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                For screenshots: 60% for first milestone, 100% for final milestone
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file" class="form-label">
                                <i class="fas fa-file me-2"></i>Upload File <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   id="file" 
                                   name="file" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Accepted formats: PDF, DOC, DOCX, JPG, PNG, ZIP (Max: 10MB)
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2"></i>Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Describe what you've accomplished and what you're submitting..."
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Provide a detailed description of your work and submission
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note me-2"></i>Additional Notes
                            </label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Any additional notes, challenges faced, or next steps...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-2"></i>Submit Task
                            </button>
                            <a href="{{ route('student.milestones.show', $task->groupMilestone->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Milestone Phase Guide -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-route me-2"></i>Milestone Phases
                    </h6>
                </div>
                <div class="card-body">
                    <div class="milestone-phase mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2">1</span>
                            <div>
                                <strong>Must Haves</strong>
                                <br><small class="text-muted">Initial requirements & setup</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="milestone-phase mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">2</span>
                            <div>
                                <strong>Document (Chapter 1&2)</strong>
                                <br><small class="text-muted">Introduction & Literature Review</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="milestone-phase mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-warning me-2">3</span>
                            <div>
                                <strong>Screenshots (60%)</strong>
                                <br><small class="text-muted">Mid-progress implementation</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="milestone-phase mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-warning me-2">4</span>
                            <div>
                                <strong>Screenshots (100%)</strong>
                                <br><small class="text-muted">Complete implementation</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="milestone-phase">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">5</span>
                            <div>
                                <strong>Document (Chapter 3&4)</strong>
                                <br><small class="text-muted">Methodology & Results</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission Guidelines -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Submission Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">For Documents:</h6>
                    <ul class="list-unstyled small mb-3">
                        <li>• Use clear, professional formatting</li>
                        <li>• Include proper citations</li>
                        <li>• Follow academic writing standards</li>
                    </ul>

                    <h6 class="fw-bold">For Screenshots:</h6>
                    <ul class="list-unstyled small mb-3">
                        <li>• Show key functionality</li>
                        <li>• Include UI/UX elements</li>
                        <li>• Demonstrate progress clearly</li>
                    </ul>

                    <h6 class="fw-bold">For Progress Notes:</h6>
                    <ul class="list-unstyled small">
                        <li>• Be specific about accomplishments</li>
                        <li>• Mention any challenges faced</li>
                        <li>• Outline next steps</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submissionType = document.getElementById('submission_type');
    const progressField = document.getElementById('progress_percentage_field');
    
    submissionType.addEventListener('change', function() {
        if (this.value === 'screenshots') {
            progressField.style.display = 'block';
            document.getElementById('progress_percentage').required = true;
        } else {
            progressField.style.display = 'none';
            document.getElementById('progress_percentage').required = false;
        }
    });
    
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
</script>
@endpush
