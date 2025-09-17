@extends('layouts.student')
@section('title', 'Student Dashboard')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : session('student_name') }}!</h1>
                        <p class="text-muted mb-0">Track your capstone project progress</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('student.project') }}" class="btn btn-primary">
                            <i class="fas fa-file-alt me-2"></i>My Submissions
                        </a>
                        <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>My Group
                        </a>
                        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-gavel me-2"></i>Defense Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Current Academic Term Context
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($activeTerm ?? null)
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3">{{ $activeTerm->full_name }}</h4>
                                        <span class="badge bg-success fs-6">Active</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Current term for all academic operations and project work
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @else
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3 text-warning">No Active Term</h4>
                                        <span class="badge bg-warning fs-6">Inactive</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Please contact your coordinator about the current academic term
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Overall Progress</h5>
                        <h3 class="mb-0">{{ $overallProgress ?? 25 }}%</h3>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-white" style="width: {{ $overallProgress ?? 25 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Completed Tasks</h5>
                        <h3 class="mb-0">{{ $taskStats['completed'] ?? 3 }}</h3>
                        <small>of {{ $taskStats['total'] ?? 12 }} total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">In Progress</h5>
                        <h3 class="mb-0">{{ $taskStats['doing'] ?? 2 }}</h3>
                        <small>currently working</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Pending Tasks</h5>
                        <h3 class="mb-0">{{ $taskStats['pending'] ?? 7 }}</h3>
                        <small>needs attention</small>
                    </div>
                </div>
            </div>
        </div>
        @if($group && ($defenseInfo['scheduled_defenses']->count() > 0 || $defenseInfo['pending_requests']->count() > 0))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>60% Defense Readiness Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                @if($defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->count() > 0)
                                    @php
                                        $defense = $defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->first();
                                        $daysUntilDefense = $defense->start_at ? now()->diffInDays($defense->start_at, false) : null;
                                    @endphp
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3 text-success">Defense Scheduled!</h4>
                                        <span class="badge bg-success fs-6">Ready</span>
                                    </div>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-calendar me-1"></i>
                                        Your 60% defense is scheduled for {{ $defense->start_at ? $defense->start_at->format('M d, Y h:i A') : 'TBA' }}
                                    </p>
                                    @if($daysUntilDefense !== null)
                                        @if($daysUntilDefense > 0)
                                            <p class="text-warning mb-0">
                                                <i class="fas fa-clock me-1"></i>
                                                <strong>{{ $daysUntilDefense }} days</strong> until your defense
                                            </p>
                                        @elseif($daysUntilDefense === 0)
                                            <p class="text-danger mb-0">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <strong>Your defense is TODAY!</strong>
                                            </p>
                                        @else
                                            <p class="text-success mb-0">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <strong>Defense completed!</strong>
                                            </p>
                                        @endif
                                    @endif
                                @elseif($defenseInfo['pending_requests']->where('defense_type', '60_percent')->count() > 0)
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3 text-warning">Defense Request Pending</h4>
                                        <span class="badge bg-warning fs-6">Awaiting Approval</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-clock me-1"></i>
                                        Your 60% defense request is being reviewed by the coordinator
                                    </p>
                                @endif
                            </div>
                            <div class="col-md-4 text-end">
                                @if($defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->count() > 0)
                                    <a href="{{ route('student.defense-requests.index') }}" class="btn btn-success">
                                        <i class="fas fa-eye me-2"></i>View Defense Details
                                    </a>
                                @elseif($defenseInfo['pending_requests']->where('defense_type', '60_percent')->count() > 0)
                                    <a href="{{ route('student.defense-requests.index') }}" class="btn btn-warning">
                                        <i class="fas fa-clock me-2"></i>Check Status
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($group)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>60% Defense Requirements Checklist
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    @php
                                        $requirements = [
                                            'proposal_approved' => [
                                                'title' => 'Proposal Approved',
                                                'description' => 'Your project proposal has been approved by your adviser',
                                                'icon' => 'check-circle',
                                                'color' => 'success'
                                            ],
                                            'progress_report' => [
                                                'title' => 'Progress Report',
                                                'description' => 'Submit a detailed progress report of your project',
                                                'icon' => 'file-alt',
                                                'color' => 'info'
                                            ],
                                            'demo_ready' => [
                                                'title' => 'Demo/Prototype Ready',
                                                'description' => 'Have a working demo or prototype to present',
                                                'icon' => 'laptop-code',
                                                'color' => 'primary'
                                            ],
                                            'presentation_ready' => [
                                                'title' => 'Presentation Ready',
                                                'description' => 'Prepare your defense presentation slides',
                                                'icon' => 'presentation',
                                                'color' => 'warning'
                                            ],
                                            'defense_requested' => [
                                                'title' => 'Defense Requested',
                                                'description' => 'Submit a formal request for 60% defense',
                                                'icon' => 'calendar-plus',
                                                'color' => 'secondary'
                                            ]
                                        ];
                                        $proposalStatus = $existingProposal ?? null;
                                        $hasProgressReport = $submissionsCount > 1;
                                        $hasDemo = true; // This would need to be tracked in your system
                                        $hasPresentation = true; // This would need to be tracked in your system
                                        $defenseRequested = $defenseInfo['pending_requests']->where('defense_type', '60_percent')->count() > 0;
                                        $defenseScheduled = $defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->count() > 0;
                                    @endphp
                                    @foreach($requirements as $key => $requirement)
                                        @php
                                            $isCompleted = match($key) {
                                                'proposal_approved' => $proposalStatus && $proposalStatus->status === 'approved',
                                                'progress_report' => $hasProgressReport,
                                                'demo_ready' => $hasDemo,
                                                'presentation_ready' => $hasPresentation,
                                                'defense_requested' => $defenseRequested || $defenseScheduled,
                                                default => false
                                            };
                                            $statusClass = $isCompleted ? 'success' : 'secondary';
                                            $iconClass = $isCompleted ? 'check-circle' : 'circle';
                                        @endphp
                                        <div class="col-md-6 mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="fas fa-{{ $iconClass }} text-{{ $statusClass }} fa-lg"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 {{ $isCompleted ? 'text-success' : 'text-muted' }}">
                                                        {{ $requirement['title'] }}
                                                    </h6>
                                                    <small class="text-muted">{{ $requirement['description'] }}</small>
                                                    @if($isCompleted)
                                                        <br><small class="text-success"><i class="fas fa-check me-1"></i>Completed</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    @php
                                        $completedCount = 0;
                                        if($proposalStatus && $proposalStatus->status === 'approved') $completedCount++;
                                        if($hasProgressReport) $completedCount++;
                                        if($hasDemo) $completedCount++;
                                        if($hasPresentation) $completedCount++;
                                        if($defenseRequested || $defenseScheduled) $completedCount++;
                                        $totalRequirements = 5;
                                        $readinessPercentage = round(($completedCount / $totalRequirements) * 100);
                                    @endphp
                                    <div class="mb-3">
                                        <h3 class="text-{{ $readinessPercentage >= 80 ? 'success' : ($readinessPercentage >= 60 ? 'warning' : 'danger') }}">
                                            {{ $readinessPercentage }}%
                                        </h3>
                                        <p class="text-muted mb-0">60% Defense Ready</p>
                                    </div>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $readinessPercentage >= 80 ? 'success' : ($readinessPercentage >= 60 ? 'warning' : 'danger') }}" 
                                             style="width: {{ $readinessPercentage }}%"></div>
                                    </div>
                                    @if($readinessPercentage >= 80)
                                        <div class="alert alert-success small">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <strong>Ready for Defense!</strong><br>
                                            You have completed all major requirements.
                                        </div>
                                    @elseif($readinessPercentage >= 60)
                                        <div class="alert alert-warning small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <strong>Almost Ready!</strong><br>
                                            Complete remaining requirements to proceed.
                                        </div>
                                    @else
                                        <div class="alert alert-danger small">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <strong>Not Ready Yet</strong><br>
                                            Focus on completing basic requirements first.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($group && ($defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->count() > 0 || $defenseInfo['pending_requests']->where('defense_type', '60_percent')->count() > 0))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>60% Defense Timeline & Deadlines
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->count() > 0)
                                @php
                                    $defense = $defenseInfo['scheduled_defenses']->where('defense_type', '60_percent')->first();
                                    $defenseDate = $defense->start_at;
                                    $daysUntilDefense = $defenseDate ? now()->diffInDays($defenseDate, false) : null;
                                    $weeksUntilDefense = $defenseDate ? now()->diffInWeeks($defenseDate, false) : null;
                                @endphp
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-primary mb-2">
                                                <i class="fas fa-calendar-check me-2"></i>Defense Date
                                            </h6>
                                            <p class="mb-1">
                                                <strong>{{ $defenseDate ? $defenseDate->format('l, F d, Y') : 'TBA' }}</strong>
                                            </p>
                                            <p class="mb-0 text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $defenseDate ? $defenseDate->format('h:i A') : 'Time TBA' }}
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6 class="text-warning mb-2">
                                                <i class="fas fa-hourglass-half me-2"></i>Time Remaining
                                            </h6>
                                            @if($daysUntilDefense !== null)
                                                @if($daysUntilDefense > 0)
                                                    <p class="mb-1">
                                                        <strong class="text-{{ $daysUntilDefense <= 7 ? 'danger' : ($daysUntilDefense <= 14 ? 'warning' : 'success') }}">
                                                            {{ $daysUntilDefense }} days
                                                        </strong>
                                                    </p>
                                                    <p class="mb-0 text-muted">
                                                        {{ $weeksUntilDefense }} weeks remaining
                                                    </p>
                                                @elseif($daysUntilDefense === 0)
                                                    <p class="mb-0 text-danger">
                                                        <strong><i class="fas fa-exclamation-triangle me-1"></i>DEFENSE IS TODAY!</strong>
                                                    </p>
                                                @else
                                                    <p class="mb-0 text-success">
                                                        <strong><i class="fas fa-check-circle me-1"></i>Defense Completed</strong>
                                                    </p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    @if($daysUntilDefense > 0)
                                        <div class="mt-3">
                                            <h6 class="text-danger mb-2">
                                                <i class="fas fa-exclamation-triangle me-2"></i>Critical Deadlines
                                            </h6>
                                            <div class="row">
                                                @php
                                                    $criticalDeadlines = [];
                                                    if($daysUntilDefense <= 7) {
                                                        $criticalDeadlines[] = [
                                                            'title' => 'Final Presentation Rehearsal',
                                                            'deadline' => 'Today',
                                                            'status' => 'urgent',
                                                            'icon' => 'presentation'
                                                        ];
                                                    }
                                                    if($daysUntilDefense <= 14) {
                                                        $criticalDeadlines[] = [
                                                            'title' => 'Demo Testing',
                                                            'deadline' => 'This Week',
                                                            'status' => 'warning',
                                                            'icon' => 'laptop-code'
                                                        ];
                                                    }
                                                    if($daysUntilDefense <= 21) {
                                                        $criticalDeadlines[] = [
                                                            'title' => 'Progress Report Submission',
                                                            'deadline' => 'Next Week',
                                                            'status' => 'info',
                                                            'icon' => 'file-alt'
                                                        ];
                                                    }
                                                @endphp
                                                @foreach($criticalDeadlines as $deadline)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                                <i class="fas fa-{{ $deadline['icon'] }} text-{{ $deadline['status'] === 'urgent' ? 'danger' : ($deadline['status'] === 'warning' ? 'warning' : 'info') }}"></i>
                                                            </div>
                                                            <div>
                                                                <small class="fw-semibold">{{ $deadline['title'] }}</small><br>
                                                                <small class="text-muted">{{ $deadline['deadline'] }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        @if($daysUntilDefense > 0)
                                            <div class="mb-3">
                                                <h2 class="text-{{ $daysUntilDefense <= 7 ? 'danger' : ($daysUntilDefense <= 14 ? 'warning' : 'success') }}">
                                                    {{ $daysUntilDefense }}
                                                </h2>
                                                <p class="text-muted mb-0">Days Until Defense</p>
                                            </div>
                                            @if($daysUntilDefense <= 7)
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-fire me-2"></i>
                                                    <strong>Final Week!</strong><br>
                                                    <small>Focus on presentation and demo preparation.</small>
                                                </div>
                                            @elseif($daysUntilDefense <= 14)
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-clock me-2"></i>
                                                    <strong>Two Weeks Left!</strong><br>
                                                    <small>Complete all deliverables and start rehearsing.</small>
                                                </div>
                                            @else
                                                <div class="alert alert-info">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <strong>On Track!</strong><br>
                                                    <small>Continue working on your project deliverables.</small>
                                                </div>
                                            @endif
                                        @endif
                                        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>View Defense Details
                                        </a>
                                    </div>
                                </div>
                            @elseif($defenseInfo['pending_requests']->where('defense_type', '60_percent')->count() > 0)
                                <div class="col-12 text-center">
                                    <div class="py-4">
                                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                        <h5 class="text-warning">Defense Request Pending</h5>
                                        <p class="text-muted mb-3">Your 60% defense request is being reviewed by the coordinator.</p>
                                        <p class="text-muted small mb-3">While waiting for approval, focus on:</p>
                                        <div class="row justify-content-center">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <i class="fas fa-file-alt text-info mb-2"></i>
                                                    <p class="small text-muted">Progress Report</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <i class="fas fa-laptop-code text-primary mb-2"></i>
                                                    <p class="small text-muted">Demo/Prototype</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <i class="fas fa-presentation text-warning mb-2"></i>
                                                    <p class="small text-muted">Presentation</p>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-warning">
                                            <i class="fas fa-clock me-2"></i>Check Request Status
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($group)
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Adviser Information
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($adviserInfo['has_adviser'])
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                                <h5 class="mb-2">{{ $adviserInfo['adviser']->name }}</h5>
                                <p class="text-muted mb-2">{{ $adviserInfo['adviser']->email }}</p>
                                <span class="badge bg-success fs-6">Assigned</span>
                            </div>
                        @elseif($adviserInfo['invitations']->count() > 0)
                            <div class="text-center">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Invitations</h5>
                                <p class="text-muted mb-2">{{ $adviserInfo['invitations']->count() }} invitation(s) sent</p>
                                <span class="badge bg-warning fs-6">Awaiting Response</span>
                                <div class="mt-3">
                                    @foreach($adviserInfo['invitations'] as $invitation)
                                        <div class="border rounded p-2 mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $invitation->faculty->name }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $invitation->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Adviser Assigned</h5>
                                <p class="text-muted mb-3">You need an adviser to proceed with your project</p>
                                @if($adviserInfo['can_invite'])
                                    <a href="{{ route('student.group') }}" class="btn btn-primary">
                                        <i class="fas fa-envelope me-2"></i>Invite Adviser
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Defense Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($defenseInfo['scheduled_defenses']->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                <h5 class="mb-2">Scheduled Defenses</h5>
                            </div>
                            @foreach($defenseInfo['scheduled_defenses'] as $defense)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $defense->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $defense->start_at ? $defense->start_at->format('M d, Y') : 'TBA' }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $defense->start_at ? $defense->start_at->format('h:i A') : 'TBA' }}
                                            </small>
                                        </div>
                                        <span class="badge bg-success">Scheduled</span>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($defenseInfo['pending_requests']->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Requests</h5>
                            </div>
                            @foreach($defenseInfo['pending_requests'] as $request)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $request->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Requested {{ $request->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <span class="badge bg-warning">Pending</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Defense Scheduled</h5>
                                <p class="text-muted mb-3">Defense schedules will appear here when scheduled</p>
                                @if($defenseInfo['can_request'])
                                    <a href="{{ route('student.group') }}" class="btn btn-warning">
                                        <i class="fas fa-rocket me-2"></i>Request Defense
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-flag me-2"></i>Current Milestone
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $milestoneInfo['name'] ?? 'Proposal Development' }}</h6>
                                <p class="text-muted mb-0">{{ $milestoneInfo['description'] ?? 'Working on initial project proposal' }}</p>
                                @if(isset($milestoneInfo['status']))
                                    <span class="badge bg-{{ $milestoneInfo['status'] === 'completed' ? 'success' : ($milestoneInfo['status'] === 'in_progress' ? 'warning' : 'secondary') }} mt-2">
                                        {{ ucfirst(str_replace('_', ' ', $milestoneInfo['status'])) }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="progress mb-2" style="width: 150px; height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $milestoneInfo['progress'] ?? 60 }}%"></div>
                                </div>
                                <small class="text-muted">{{ $milestoneInfo['progress'] ?? 60 }}% complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>Recent Tasks
                            </h5>
                            <a href="{{ route('student.milestones') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-columns me-1"></i>Kanban Board
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($recentTasks) && $recentTasks->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentTasks as $task)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $task->name }}</h6>
                                            <small class="text-muted">{{ $task->description }}</small>
                                            @if($task->assigned_to)
                                                <br><small class="text-info"><i class="fas fa-user me-1"></i>{{ $task->assigned_to }}</small>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($task->status === 'done')
                                                <span class="badge bg-success">Done</span>
                                            @elseif($task->status === 'doing')
                                                <span class="badge bg-warning">Doing</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No tasks assigned yet</h6>
                                <p class="text-muted small">Tasks will appear here when your adviser assigns them.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('student.project.create') }}" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Document
                            </a>
                            <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>View Group
                            </a>
                            <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-columns me-2"></i>Kanban Board
                            </a>
                            <a href="{{ route('student.proposal') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-alt me-2"></i>Proposal & Endorsement
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($recentActivities) && $recentActivities->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentActivities as $activity)
                                    <div class="list-group-item px-0 border-0">
                                        <div class="d-flex align-items-start">
                                            <div class="me-2">
                                                <i class="fas fa-{{ $activity->icon ?? 'circle' }} text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $activity->title }}</h6>
                                                <small class="text-muted">{{ $activity->description }}</small>
                                                <br><small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                <p class="text-muted small mb-0">No recent activities</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Upcoming Deadlines
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($upcomingDeadlines) && $upcomingDeadlines->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Task/Milestone</th>
                                            <th>Type</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($upcomingDeadlines as $deadline)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $deadline->title }}</div>
                                                    <small class="text-muted">{{ $deadline->description }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $deadline->type === 'milestone' ? 'primary' : 'info' }}">
                                                        {{ ucfirst($deadline->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $deadline->is_overdue ? 'danger' : 'primary' }}">
                                                        {{ $deadline->due_date ? $deadline->due_date->format('M d, Y') : 'TBA' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($deadline->is_overdue)
                                                        <span class="badge bg-danger">Overdue</span>
                                                    @elseif($deadline->is_due_soon)
                                                        <span class="badge bg-warning">Due Soon</span>
                                                    @else
                                                        <span class="badge bg-success">On Track</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No upcoming deadlines</h6>
                                <p class="text-muted small">Deadlines will appear here when they are set.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
