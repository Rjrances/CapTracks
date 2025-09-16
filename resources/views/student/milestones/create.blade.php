@extends('layouts.student')

@section('title', 'Create New Milestone')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle me-2"></i>Create New Milestone
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Milestones
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('student.milestones.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label for="milestone_template_id" class="form-label">
                                        <i class="fas fa-template me-2"></i>Choose Milestone Template
                                    </label>
                                    <select class="form-select @error('milestone_template_id') is-invalid @enderror" 
                                            id="milestone_template_id" 
                                            name="milestone_template_id" 
                                            required>
                                        <option value="">Select a template...</option>
                                        @foreach($milestoneTemplates as $template)
                                            <option value="{{ $template->id }}" 
                                                    data-description="{{ $template->description }}"
                                                    data-tasks="{{ $template->tasks->count() }}"
                                                    {{ old('milestone_template_id') == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }} 
                                                <span class="text-muted">({{ $template->tasks->count() }} tasks)</span>
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('milestone_template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Milestone Title
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title') }}" 
                                           placeholder="Enter milestone title..."
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              placeholder="Enter milestone description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="due_date" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Due Date
                                    </label>
                                    <input type="date" 
                                           class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" 
                                           name="due_date" 
                                           value="{{ old('due_date') }}"
                                           min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>Template Preview
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="template-preview">
                                            <p class="text-muted">Select a template to see its details...</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-lightbulb me-2"></i>Tips
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Choose a template that matches your project type
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                You can customize the title and description
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Set a realistic due date for your milestone
                                            </li>
                                            <li class="mb-0">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Tasks will be automatically created from the template
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Milestone
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('milestone_template_id');
    const templatePreview = document.getElementById('template-preview');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');

    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const description = selectedOption.dataset.description;
            const taskCount = selectedOption.dataset.tasks;
            const templateTitle = selectedOption.textContent.split(' (')[0];
            
            // Update preview
            templatePreview.innerHTML = `
                <h6 class="text-primary">${templateTitle}</h6>
                <p class="text-muted small mb-2">${description || 'No description available'}</p>
                <div class="badge bg-info">${taskCount} tasks included</div>
            `;
            
            // Auto-fill title if empty
            if (!titleInput.value) {
                titleInput.value = templateTitle;
            }
            
            // Auto-fill description if empty
            if (!descriptionInput.value && description) {
                descriptionInput.value = description;
            }
        } else {
            templatePreview.innerHTML = '<p class="text-muted">Select a template to see its details...</p>';
        }
    });
});
</script>
@endsection
