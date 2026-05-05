@extends('layouts.coordinator')
@section('title', 'Create Milestone Template')
@section('content')
<div class="container-fluid mb-3">
        <x-coordinator.intro description="Add a reusable milestone phase with tasks for groups you coordinate.">
            <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to templates
            </a>
        </x-coordinator.intro>
</div>
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 800px;">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Template Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('coordinator.milestones.store') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    Template Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g., 60% Defense Milestone Template"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Enter a descriptive name for this milestone template</div>
                            </div>
                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          placeholder="Describe what this milestone template covers and its purpose">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Provide a detailed description of this milestone template</div>
                            </div>
                            <div class="mb-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Set the initial status for this template</div>
                            </div>
                            <div class="mb-4">
                                <label for="sequence_order" class="form-label">Sequence step</label>
                                <input type="number"
                                       min="1"
                                       max="255"
                                       class="form-control @error('sequence_order') is-invalid @enderror"
                                       id="sequence_order"
                                       name="sequence_order"
                                       value="{{ old('sequence_order') }}"
                                       placeholder="e.g. 1 = Proposal, 2 = 60%, 3 = 100%">
                                @error('sequence_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave blank if this template is not part of the main proposal → 60% → 100% sequence. Each active template in the sequence should have a unique step number.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Template
                                </button>
                                <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle me-2"></i>Creating Milestone Templates
                        </h6>
                        <p class="card-text mb-2">
                            <strong>Template Name:</strong> Choose a clear, descriptive name that identifies the purpose of this milestone template.
                        </p>
                        <p class="card-text mb-2">
                            <strong>Description:</strong> Provide details about what this template covers, when it should be used, and any specific requirements.
                        </p>
                        <p class="card-text mb-0">
                            <strong>Status:</strong> Set to "Active" to make it available for groups to use, "Inactive" to hide it, or "Draft" for templates still being developed.
                        </p>
                        <hr>
                        <p class="card-text mb-0">
                            <strong>Note:</strong> After creating the template, you can add specific tasks to it by editing the template from the main templates list.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        $('#description').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>
@endpush
