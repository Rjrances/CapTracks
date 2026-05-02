<form action="{{ $formAction }}" method="POST" class="mb-4">
    @csrf
    <label class="form-label" for="task-comment-body-thread">Add comment</label>
    <textarea
        id="task-comment-body-thread"
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
        <i class="fas fa-paper-plane me-1"></i>Post comment
    </button>
</form>

@if($comments->count())
    <div class="d-flex flex-column gap-3">
        @foreach($comments as $comment)
            <div class="border rounded p-3 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>
                            {{ $comment->user->name ?? $comment->studentAuthor->name ?? ('Student ' . ($comment->student_id ?? 'Unknown')) }}
                        </strong>
                        <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                <p class="mb-3">{{ $comment->body }}</p>

                <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#task-reply-{{ $comment->id }}">
                    Reply
                </button>

                <div class="collapse" id="task-reply-{{ $comment->id }}">
                    <form action="{{ $formAction }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <textarea name="body" class="form-control" rows="2" placeholder="Write a reply..." required></textarea>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Post reply</button>
                    </form>
                </div>

                @if($comment->children->count())
                    <div class="ms-md-4 mt-3 d-flex flex-column gap-2">
                        @foreach($comment->children as $reply)
                            <div class="border rounded p-2 bg-light">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>
                                            {{ $reply->user->name ?? $reply->studentAuthor->name ?? ('Student ' . ($reply->student_id ?? 'Unknown')) }}
                                        </strong>
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
