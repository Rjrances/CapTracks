@extends('layouts.chairperson')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Academic Term Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>School Year</h5>
                            <p class="text-muted">{{ $academicTerm->school_year }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Semester</h5>
                            <p class="text-muted">{{ $academicTerm->semester }}</p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5>Status</h5>
                            @if($academicTerm->is_active)
                                <span class="badge bg-success">Active</span>
                            @elseif($academicTerm->is_archived)
                                <span class="badge bg-secondary">Archived</span>
                            @else
                                <span class="badge bg-warning">Inactive</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h5>Created</h5>
                            <p class="text-muted">{{ $academicTerm->created_at->format('F j, Y g:i A') }}</p>
                        </div>
                    </div>

                    @if($academicTerm->updated_at != $academicTerm->created_at)
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Last Updated</h5>
                                <p class="text-muted">{{ $academicTerm->updated_at->format('F j, Y g:i A') }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('chairperson.academic-terms.edit', $academicTerm) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Term
                        </a>
                        <a href="{{ route('chairperson.academic-terms.index') }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
