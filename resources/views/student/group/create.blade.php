@extends('layouts.student')
@section('title', 'Create Group')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Create New Group</h2>
            @if(isset($offering))
                <p class="text-muted mb-0">
                    <i class="fas fa-book me-1"></i>
                    For: <strong>{{ $offering->offer_code }} - {{ $offering->subject_code }} - {{ $offering->subject_title }}</strong>
                </p>
            @endif
        </div>
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Return to Dashboard
        </a>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Create New Group
                    </h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <form action="{{ route('student.group.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">Group Name *</label>
                                    <input type="text" name="name" id="name" class="form-control" required 
                                           placeholder="Enter group name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label fw-bold">Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="3"
                                              placeholder="Brief description of your project/group"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Select Adviser *
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Choose a faculty member to serve as your group's adviser. This person will guide your project and provide mentorship.
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="adviser_id" id="adviser_id" class="form-select" required>
                                        <option value="">Choose an adviser...</option>
                                        @php
                                            $availableFaculty = \App\Models\User::whereIn('role', ['adviser', 'panelist', 'teacher'])->get();
                                        @endphp
                                        @foreach($availableFaculty as $faculty)
                                            <option value="{{ $faculty->id }}">
                                                {{ $faculty->name }} 
                                                <span class="text-muted">({{ ucfirst($faculty->role) }}{{ $faculty->department ? ' - ' . $faculty->department : '' }})</span>
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="adviser_message" class="form-label fw-bold">Message to Adviser (Optional)</label>
                                <textarea name="adviser_message" id="adviser_message" class="form-control" rows="3"
                                          placeholder="Add a personal message explaining why you'd like this faculty member to be your adviser..."></textarea>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-user-plus me-1"></i>Select Group Members (Max 2 additional members)
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                You will be automatically added as the group leader. Select up to 2 additional members (3 total members per group).
                                <br><strong>Note:</strong> Only students enrolled in the same capstone offering can be added to your group.
                            </div>
                            <div class="row">
                                @php
                                    $availableStudents = \App\Models\Student::whereNotIn('id', function($query) {
                                        $query->select('student_id')
                                              ->from('group_members');
                                    })
                                    ->whereHas('offerings', function($query) use ($offering) {
                                        $query->where('offering_id', $offering->id);
                                    })
                                    ->get();
                                @endphp
                                @foreach($availableStudents as $student)
                                    @if($student->email !== (Auth::check() ? Auth::user()->email : session('student_email')))
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input member-checkbox" type="checkbox" 
                                                       name="members[]" value="{{ $student->student_id }}" 
                                                       id="student_{{ $student->student_id }}" 
                                                       onchange="limitSelections()">
                                                <label class="form-check-label" for="student_{{ $student->student_id }}">
                                                    <strong>{{ $student->name }}</strong><br>
                                                    <small class="text-muted">
                                                        {{ $student->student_id }} • {{ $student->email }}
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="selection-count">0</span> of 2 additional members selected (3 total including you)
                                </small>
                            </div>
                            @if($availableStudents->count() <= 1)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    No other students available to add to your group.
                                </div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Create Group
                            </button>
                            <a href="{{ route('student.group') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function limitSelections() {
    const checkboxes = document.querySelectorAll('.member-checkbox:checked');
    const maxSelections = 2;
    const countSpan = document.getElementById('selection-count');
    countSpan.textContent = checkboxes.length;
    if (checkboxes.length > maxSelections) {
        checkboxes[checkboxes.length - 1].checked = false;
        countSpan.textContent = maxSelections;
    }
    const allCheckboxes = document.querySelectorAll('.member-checkbox');
    allCheckboxes.forEach(checkbox => {
        if (!checkbox.checked && checkboxes.length >= maxSelections) {
            checkbox.disabled = true;
        } else {
            checkbox.disabled = false;
        }
    });
}
</script>
@endsection 
