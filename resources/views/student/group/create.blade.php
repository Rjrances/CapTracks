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
                                            // Get current active term
                                            $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
                                            $availableFaculty = \App\Models\User::where('role', 'adviser')
                                                ->where('semester', $activeTerm ? $activeTerm->semester : null)
                                                ->get();
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
                                You will be automatically added as the group leader. You can optionally select up to 2 additional members now, or invite them later after creating the group.
                                <br><strong>Note:</strong> Only students enrolled in the same capstone offering can be added to your group.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="student_search" class="form-label fw-semibold">
                                        <i class="fas fa-search me-2"></i>Search Students
                                    </label>
                                    <input type="text" id="student_search" class="form-control" placeholder="Search for student name..." onkeyup="filterStudents()">
                                </div>
                                <div class="col-md-6">
                                    <label for="member1" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2"></i>Select First Member (Optional)
                                    </label>
                                    <select name="members[]" id="member1" class="form-select" onchange="updateMember2Options()">
                                        <option value="">Select a student...</option>
                                        {{-- Available students are now passed from the controller with consistent filtering --}}
                                        @foreach($availableStudents as $student)
                                            @if($student->email !== (Auth::guard('student')->check() ? Auth::guard('student')->user()->email : ''))
                                                <option value="{{ $student->student_id }}" data-name="{{ strtolower($student->name) }}">
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="member2" class="form-label fw-semibold">
                                        <i class="fas fa-user me-2"></i>Select Second Member (Optional)
                                    </label>
                                    <select name="members[]" id="member2" class="form-select">
                                        <option value="">Select a student...</option>
                                        @foreach($availableStudents as $student)
                                            @if($student->email !== (Auth::guard('student')->check() ? Auth::guard('student')->user()->email : ''))
                                                <option value="{{ $student->student_id }}" data-name="{{ strtolower($student->name) }}">
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="selection-count">0</span> of 2 members selected (3 total including you) - <strong>Members are optional, you can invite them later</strong>
                                </small>
                            </div>
                            @php
                                // Use the count from the controller's filtered students
                                $availableStudentsCount = $availableStudents->count();
                            @endphp
                            @if($availableStudentsCount <= 0)
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
const originalMember1Options = Array.from(document.getElementById('member1').options);
const originalMember2Options = Array.from(document.getElementById('member2').options);

function filterStudents() {
    const searchTerm = document.getElementById('student_search').value.toLowerCase();
    const member1Select = document.getElementById('member1');
    const member2Select = document.getElementById('member2');
    
    filterSelectOptions(member1Select, originalMember1Options, searchTerm);
    
    filterSelectOptions(member2Select, originalMember2Options, searchTerm);
}

function filterSelectOptions(selectElement, originalOptions, searchTerm) {
    selectElement.innerHTML = '<option value="">Select a student...</option>';
    
    originalOptions.forEach(option => {
        if (option.value === '') return;
        
        const studentName = option.getAttribute('data-name') || '';
        if (studentName.includes(searchTerm)) {
            selectElement.appendChild(option.cloneNode(true));
        }
    });
    
    if (selectElement.value && !Array.from(selectElement.options).some(opt => opt.value === selectElement.value)) {
        selectElement.value = '';
    }
}

function updateMember2Options() {
    const member1Select = document.getElementById('member1');
    const member2Select = document.getElementById('member2');
    const selectedMember1 = member1Select.value;
    
    member2Select.innerHTML = '<option value="">Select a student...</option>';
    
    originalMember2Options.forEach(option => {
        if (option.value === '') return;
        if (option.value !== selectedMember1) {
            member2Select.appendChild(option.cloneNode(true));
        }
    });
    
    if (member2Select.value === selectedMember1) {
        member2Select.value = '';
    }
    
    updateSelectionCount();
}

function updateSelectionCount() {
    const member1Select = document.getElementById('member1');
    const member2Select = document.getElementById('member2');
    const countSpan = document.getElementById('selection-count');
    
    let count = 0;
    if (member1Select.value) count++;
    if (member2Select.value) count++;
    
    countSpan.textContent = count;
}

document.addEventListener('DOMContentLoaded', function() {
    updateSelectionCount();
    
    document.getElementById('member1').addEventListener('change', updateMember2Options);
    document.getElementById('member2').addEventListener('change', updateSelectionCount);
});
</script>
@endsection 
