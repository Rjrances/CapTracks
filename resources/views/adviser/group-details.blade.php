@extends('layouts.adviser')
@section('title', 'Group Details')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">{{ $group->name }}</div>
            @if(($viewerMode ?? 'adviser') === 'panel')
                <div class="text-muted text-center small mt-1">Panel view: mentoring discussions are hidden.</div>
            @endif
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-info-circle me-2"></i>Group Information
            </div>
            <div class="bg-light rounded-3 p-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Group Name:</strong><br>
                            <span class="text-muted">{{ $group->name }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <span class="text-muted">{{ $group->description ?: 'No description provided' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Created:</strong><br>
                            <span class="text-muted">{{ $group->created_at->format('F j, Y') }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Total Members:</strong><br>
                            <span class="text-muted">{{ $group->members->count() }} members</span>
                        </div>
                        <div class="mb-3">
                            <strong>Assigned Adviser:</strong><br>
                            @if($group->adviser)
                                <span class="text-muted">{{ $group->adviser->name }}</span>
                                @if($group->adviser->email)
                                    <br><small class="text-muted">{{ $group->adviser->email }}</small>
                                @endif
                            @else
                                <span class="text-muted">No adviser assigned</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-users me-2"></i>Group Members
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($group->members->count() > 0)
                    @foreach($group->members as $member)
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-secondary border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-user text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $member->name }}</div>
                                <div class="text-muted small">
                                    <i class="fas fa-id-card me-1"></i>
                                    {{ $member->student_id }}
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $member->email }}
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    {{ $member->course }} - {{ $member->semester }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-{{ $member->pivot->role === 'leader' ? 'primary' : 'secondary' }}">
                                    {{ ucfirst($member->pivot->role) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        No members found
                    </div>
                @endif
            </div>
        </div>
        @if($canViewMilestoneDiscussions ?? false)
            {{-- Milestone Kanban Boards --}}
            <div class="mb-4">
                <div class="fw-bold mb-2" style="font-size:1.2rem;">
                    <i class="fas fa-columns me-2"></i>Milestone Kanban Boards
                </div>
                <div class="bg-light rounded-3 p-3">
                    @php $milestones = $group->groupMilestoneTasks->pluck('groupMilestone')->unique('id')->filter(); @endphp
                    @forelse($group->groupMilestones ?? collect() as $gm)
                        <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded mb-2 border">
                            <div>
                                <strong>{{ $gm->milestoneTemplate->name ?? $gm->title ?? 'Milestone' }}</strong>
                                <div class="small text-muted">
                                    Progress: {{ $gm->progress_percentage }}%
                                    &nbsp;|&nbsp;
                                    <span class="badge bg-{{ $gm->progress_percentage >= 100 ? 'success' : ($gm->progress_percentage >= 50 ? 'warning text-dark' : 'secondary') }}">
                                        {{ $gm->status_text }}
                                    </span>
                                </div>
                                <div class="progress mt-1" style="height: 6px; width: 200px;">
                                    <div class="progress-bar bg-{{ $gm->progress_percentage >= 100 ? 'success' : ($gm->progress_percentage >= 50 ? 'warning' : 'secondary') }}"
                                         style="width: {{ $gm->progress_percentage }}%"></div>
                                </div>
                            </div>
                            <a href="{{ route('adviser.groups.milestone-kanban', [$group, $gm]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-columns me-1"></i>View Kanban
                            </a>
                        </div>
                    @empty
                        <div class="text-muted text-center">
                            <i class="fas fa-columns fa-2x mb-2 d-block"></i>
                            No milestones assigned to this group yet.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Milestone Task Discussions --}}
            <div class="mb-4">
                <div class="fw-bold mb-2" style="font-size:1.2rem;">
                    <i class="fas fa-tasks me-2"></i>Milestone tasks (discussion)
                </div>
                <div class="bg-light rounded-3 p-3">
                    @if($group->groupMilestoneTasks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bg-white mb-0">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Milestone</th>
                                        <th>Status</th>
                                        <th class="text-end">Discussion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group->groupMilestoneTasks as $gmt)
                                        <tr>
                                            <td>{{ $gmt->milestoneTask->name ?? 'Task' }}</td>
                                            <td><span class="text-muted small">{{ $gmt->groupMilestone->milestoneTemplate->name ?? '—' }}</span></td>
                                            <td><span class="badge bg-secondary">{{ $gmt->status ?? 'pending' }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('adviser.groups.milestone-task-comments', [$group, $gmt]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-comments me-1"></i>
                                                    Open
                                                    @if(($gmt->task_comments_count ?? 0) > 0)
                                                        <span class="badge bg-primary ms-1">{{ $gmt->task_comments_count }}</span>
                                                    @endif
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted text-center">
                            <i class="fas fa-tasks fa-2x mb-2"></i><br>
                            No milestone tasks for this group yet.
                        </div>
                    @endif
                </div>
            </div>
        @endif
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('adviser.groups') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Groups
            </a>
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
    </div>
</div>
@endsection 
