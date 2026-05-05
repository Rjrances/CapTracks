@extends('layouts.coordinator')

@section('title', 'Proposal review')
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Review submission details, attached files, and status for this student proposal.">
            <a href="{{ route('coordinator.proposals.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to proposals
            </a>
        </x-coordinator.intro>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Proposal Details
                            </h5>
                        </div>
                        <div class="card-body">
                            @php
                                $student = $proposal->getStudentData();
                            @endphp
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <strong>Student:</strong>
                                    <p class="mb-0">{{ $student ? $student->name : 'Unknown' }}</p>
                                    <small class="text-muted">{{ $student ? $student->student_id : 'N/A' }}</small>
                                </div>
                                <div class="col-md-6">
                                    <strong>Group:</strong>
                                    <p class="mb-0">{{ $studentGroup->name ?? 'No Group' }}</p>
                                    <small class="text-muted">{{ $offering->subject_code ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <strong>Proposal Title:</strong>
                                <h4 class="text-primary">{{ $proposal->title ?? 'Untitled Proposal' }}</h4>
                            </div>

                            @if($proposal->description)
                                <div class="mb-4">
                                    <strong>Description:</strong>
                                    <div class="border rounded p-3 bg-light">
                                        {{ $proposal->description }}
                                    </div>
                                </div>
                            @endif

                            @if($proposal->file_path)
                                <div class="mb-4">
                                    <strong>Attached File:</strong>
                                    <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('coordinator.proposals.preview', $proposal->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i>Preview
                                        </a>
                                        <a href="{{ Storage::url($proposal->file_path) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-download me-1"></i>Download File
                                        </a>
                                        <small class="text-muted">
                                            {{ basename($proposal->file_path) }}
                                        </small>
                                    </div>
                                </div>
                            @endif

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <strong>Submitted:</strong>
                                    <p class="mb-0">{{ $proposal->submitted_at ? $proposal->submitted_at->format('M d, Y H:i') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p class="mb-0">
                                        @switch($proposal->status)
                                            @case('pending')
                                                <span class="badge bg-warning fs-6">Pending Review</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-success fs-6">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger fs-6">Rejected</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary fs-6">{{ ucfirst($proposal->status) }}</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>

                            @if($proposal->teacher_comment)
                                <div class="mb-4">
                                    <strong>Review Comments:</strong>
                                    <div class="alert alert-info">
                                        <i class="fas fa-comment me-2"></i>{{ $proposal->teacher_comment }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Proposal Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Offering:</strong>
                                <p class="mb-0">{{ $offering->subject_code }} - {{ $offering->subject_title }}</p>
                                <small class="text-muted">{{ $offering->offer_code }}</small>
                            </div>

                            <div class="mb-3">
                                <strong>Academic Term:</strong>
                                <p class="mb-0">{{ $offering->academicTerm->semester ?? 'N/A' }}</p>
                            </div>

                            @if($studentGroup)
                                <div class="mb-3">
                                    <strong>Group Members:</strong>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($studentGroup->members as $member)
                                            <li class="mb-1">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $member->name }}
                                                @if($member->pivot->role === 'leader')
                                                    <span class="badge bg-primary ms-1">Leader</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-3">
                                <strong>Proposal ID:</strong>
                                <p class="mb-0 text-muted">#{{ $proposal->id }}</p>
                            </div>

                            <div class="mb-3">
                                <strong>Version:</strong>
                                <p class="mb-0 text-muted">v{{ $proposal->version ?? 1 }}</p>
                            </div>

                            @if(isset($versionHistory) && $versionHistory->isNotEmpty())
                                @php
                                    $coordProposalCompareTemplate = str_replace(
                                        ['11111111', '22222222'],
                                        ['__L__', '__R__'],
                                        route('coordinator.proposals.versions.compare', ['left' => 11111111, 'right' => 22222222])
                                    );
                                @endphp
                                <div class="mb-3">
                                    <strong>Version History:</strong>
                                    @if($versionHistory->count() >= 2)
                                        <p class="small text-muted mb-2">Compare two versions side by side.</p>
                                        <div class="row g-2 align-items-end mb-3">
                                            <div class="col-5">
                                                <label class="form-label small mb-0" for="coord-prop-cmp-a">Version A</label>
                                                <select id="coord-prop-cmp-a" class="form-select form-select-sm">
                                                    @foreach($versionHistory as $version)
                                                        <option value="{{ $version->id }}">v{{ $version->version ?? 1 }} ({{ $version->submitted_at ? $version->submitted_at->format('M d, Y') : 'N/A' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label small mb-0" for="coord-prop-cmp-b">Version B</label>
                                                <select id="coord-prop-cmp-b" class="form-select form-select-sm">
                                                    @foreach($versionHistory as $version)
                                                        <option value="{{ $version->id }}">v{{ $version->version ?? 1 }} ({{ $version->submitted_at ? $version->submitted_at->format('M d, Y') : 'N/A' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="coord-proposal-compare-go">
                                                    <i class="fas fa-columns me-1"></i>Compare
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="list-group mt-2">
                                        @foreach($versionHistory as $version)
                                            <div class="list-group-item d-flex flex-column gap-2 @if($version->id === $proposal->id) active @endif">
                                                <div class="d-flex justify-content-between align-items-center w-100">
                                                    <div>
                                                        <div>v{{ $version->version ?? 1 }}</div>
                                                        <small class="@if($version->id === $proposal->id) text-white-50 @else text-muted @endif">
                                                            {{ $version->submitted_at ? $version->submitted_at->format('M d, Y') : 'N/A' }}
                                                        </small>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('coordinator.proposals.preview', $version->id) }}" class="btn @if($version->id === $proposal->id) btn-light @else btn-outline-info @endif">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ Storage::url($version->file_path) }}" target="_blank" class="btn @if($version->id === $proposal->id) btn-light @else btn-outline-primary @endif">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @if($versionHistory->count() >= 2)
                                    @push('scripts')
                                    <script>
                                        (function () {
                                            var tpl = @json($coordProposalCompareTemplate);
                                            document.getElementById('coord-proposal-compare-go').addEventListener('click', function () {
                                                var l = document.getElementById('coord-prop-cmp-a').value;
                                                var r = document.getElementById('coord-prop-cmp-b').value;
                                                if (!l || !r || l === r) {
                                                    alert('Choose two different versions.');
                                                    return;
                                                }
                                                window.location.href = tpl.replace('__L__', l).replace('__R__', r);
                                            });
                                        })();
                                    </script>
                                    @endpush
                                @endif
                            @endif

                            @if($proposal->created_at)
                                <div class="mb-3">
                                    <strong>Created:</strong>
                                    <p class="mb-0 text-muted">{{ $proposal->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif

                            @if($proposal->updated_at && $proposal->updated_at != $proposal->created_at)
                                <div class="mb-3">
                                    <strong>Last Updated:</strong>
                                    <p class="mb-0 text-muted">{{ $proposal->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($proposal->status === 'pending')
                        <div class="card mt-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Action Required
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">This proposal is waiting for your review and decision.</p>
                            </div>
                        </div>
                    @elseif($proposal->status === 'approved')
                        <div class="card mt-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>Approved
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">This proposal has been approved and the student has been notified.</p>
                            </div>
                        </div>
                    @elseif($proposal->status === 'rejected')
                        <div class="card mt-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-times-circle me-2"></i>Rejected
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">This proposal has been rejected and the student has been notified.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Discussion
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('coordinator.proposals.comments.store', $proposal->id) }}" method="POST" class="mb-4">
                        @csrf
                        <label for="comment-body" class="form-label">Add comment</label>
                        <textarea
                            id="comment-body"
                            name="body"
                            class="form-control @error('body') is-invalid @enderror"
                            rows="3"
                            placeholder="Write your comment here..."
                            required
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-primary mt-2">
                            <i class="fas fa-paper-plane me-1"></i>Post Comment
                        </button>
                    </form>

                    @if(isset($comments) && $comments->count())
                        <div class="d-flex flex-column gap-3">
                            @foreach($comments as $comment)
                                <div class="border rounded p-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>{{ $comment->user->name ?? ('Student ' . ($comment->student_id ?? 'Unknown')) }}</strong>
                                            <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                    <p class="mb-3">{{ $comment->body }}</p>

                                    <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#reply-{{ $comment->id }}">
                                        Reply
                                    </button>

                                    <div class="collapse" id="reply-{{ $comment->id }}">
                                        <form action="{{ route('coordinator.proposals.comments.store', $proposal->id) }}" method="POST" class="mb-3">
                                            @csrf
                                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                            <textarea name="body" class="form-control" rows="2" placeholder="Write a reply..." required></textarea>
                                            <button type="submit" class="btn btn-sm btn-primary mt-2">Post Reply</button>
                                        </form>
                                    </div>

                                    @if($comment->children->count())
                                        <div class="ms-4 mt-3 d-flex flex-column gap-2">
                                            @foreach($comment->children as $reply)
                                                <div class="border rounded p-2 bg-light">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $reply->user->name ?? ('Student ' . ($reply->student_id ?? 'Unknown')) }}</strong>
                                                            <small class="text-muted ms-2">{{ $reply->created_at->diffForHumans() }}</small>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0 mt-1">{{ $reply->body }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No comments yet.</p>
                    @endif
                </div>
            </div>
</div>
@endsection
